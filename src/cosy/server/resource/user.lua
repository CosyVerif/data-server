local configuration = require "cosy.server.configuration"
local Resources     = require "cosy.server.resources"
local bcrypt        = require "bcrypt"

local rounds = configuration.server.password_rounds

local function generate_key ()
  local run = io.popen ("uuidgen", "r")
  local result = run:read ("*all")
  run:close ()
  return result
end

local User = {}
Resources.user = User

User.__index = User

function User:__check (t)
  local errors = {}
  -- TODO
  if #errors ~= 0 then
    return nil, errors
  end
  return true
end

local function create (t)
  return setmetatable ({
    username       = t.username,
    password       = bcrypt.digest (t.password, rounds),
    fullname       = t.fullname,
    email          = t.email,
    validation_key = generate_key (),
    is_active      = false,
    is_public      = true,
  }, User)
end

--local haricot = require "haricot"
--local bs = haricot.new("localhost", 11300)

-- Test:
local copas = require "copas.timer"

local nb_create =   50
local nb_read   = 1000
local nb_write  =  200

print ("# create: " .. tostring (nb_create))
print ("# write : " .. tostring (nb_write * nb_create))
print ("# read  : " .. tostring (nb_read  * nb_create))

local start_time = os.time ()
local finished = nb_create * 3

local function finish ()
  local finish_time = os.time ()
  local duration = finish_time - start_time
  print ("Time: " .. tostring (duration) .. " seconds.")
  local operations = nb_create + nb_write * nb_create + nb_read  * nb_create
  print ("Average operations: " .. tostring (operations / duration) .. " per second.")
  os.exit (0)
end

local function do_read (i)
  for k = 1, nb_read do
    local root = Resources {} [tostring (i)]
    assert (root ~= nil)
    for k, v in pairs (root) do
      local _ = root.is_public
    end
  end
  finished = finished - 1
  if finished == 0 then
    finish ()
  end
end

local function do_write (i)
  for k = 1, nb_write do
    local root = Resources {} [tostring (i)]
    for k, v in pairs (root) do
      root.is_private = true
    end
  end
  finished = finished - 1
  if finished == 0 then
    finish ()
  end
end

local function do_create (i)
  local root = Resources {}
  root [tostring (i)] = create {
    username = "user-${i}" % { i = i },
    password = "toto",
    fullname = "User ${i}" % { i = i },
    email    = "user.${i}@gmail.com" % { i = i },
  }
  copas.addthread (function ()
    do_read (i)
  end)
  copas.addthread (function ()
    do_write (i)
  end)
  finished = finished - 1
end

for i = 1, nb_create do
  copas.addthread (function ()
    do_create (i)
  end)
end
copas.loop ()

return User
