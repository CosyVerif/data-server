local redis         = require "redis"
local copas         = require "copas"
local configuration = require "cosy.server.configuration"
local resource      = require "cosy.server.resource"
local User          = require "cosy.server.resource.user"

local host      = configuration.redis.host
local port      = configuration.redis.port
local db        = configuration.redis.database
local cosy      = configuration.server.root

local nb_create  =  100
local nb_read    = 1000
local nb_write   =  500
local nb_update  =  200
local nb_iterate =   50
local finished   = 0
local total      = 0

print ("# create : " .. tostring (nb_create))
print ("# iterate: " .. tostring (nb_iterate * nb_create))
print ("# update : " .. tostring (nb_update  * nb_create))
print ("# write  : " .. tostring (nb_write   * nb_create))
print ("# read   : " .. tostring (nb_read    * nb_create))

local start_time = os.time ()

local function finish ()
  io.write "."
  io.flush ()
  finished = finished + 1
  if finished == total then
    print ""
    local client = redis.connect ({
      host      = host,
      port      = port,
      use_copas = false,
      timeout   = 0.1
    })
    client:select (db)
    print ("# keys: " .. tostring (client:dbsize ()))
    local finish_time = os.time ()
    local duration = finish_time - start_time
    print ("Time: " .. tostring (duration) .. " seconds.")
    local operations = nb_create + nb_write * nb_create + nb_read  * nb_create
    print ("Average operations: " .. tostring (math.floor (operations / duration)) .. " per second.")
    local memory = collectgarbage "count" / 1024
    print ("Memory: " .. tostring (math.ceil (memory)) .. " Mbytes.")
    local redis_memory = client:info () .memory.used_memory
    print ("Redis memory: " .. tostring (math.ceil (redis_memory / 1024 / 1024)) .. " Mbytes.")
    os.exit (0)
  end
end

local function do_read (i)
  local name = "user-${i}" % { i = i }
  local p = resource {} [i]
  for _ = 1, nb_read do
    assert (p.username == name)
    copas.sleep (0)
  end
  finish ()
end

local function do_write (i)
  local p = resource {
    username = "user-${i}" % { i = i }
  } [i]
  for _ = 1, nb_write do
    p.is_private = not p.is_private
    copas.sleep (0)
  end
  finish ()
end

local function do_update (i)
  local p = resource {
    username = "user-${i}" % { i = i }
  } [i]
  for _ = 1, nb_update do
    p {
      password  = "pass",
      some_key  = true,
      some_data = {
        x = 1,
      },
    }
    copas.sleep (0)
  end
  finish ()
end

local function do_iterate (i)
  local p = resource {
    username = "user-${i}" % { i = i }
  } [i]
  for _ = 1, nb_iterate do
    for k, v in pairs (p) do
      copas.sleep (0)
    end
  end
  finish ()
end


local function do_create (i)
  local root = resource {
    username = "username",
  }
  root [i] = User.create {
    username = "user-${i}" % { i = i },
    password = "toto",
    fullname = "User ${i}" % { i = i },
    email    = "user.${i}@gmail.com" % { i = i },
  }
  total = total + 1
  copas.addthread (function () do_read    (i) end)
  total = total + 1
  copas.addthread (function () do_write   (i) end)
  total = total + 1
  copas.addthread (function () do_update  (i) end)
  total = total + 1
  copas.addthread (function () do_iterate (i) end)
  finish ()
end

copas.addthread (function ()
  total = total + nb_create
  for i = 1, nb_create do
    copas.addthread (function () do_create (i) end)
  end
end)

-- Loop
-- ====

copas.loop ()
