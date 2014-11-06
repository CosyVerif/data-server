local _         = require "cosy.util.string"
local copas     = require "copas.timer"
local redis     = require "redis"
local json      = require "cjson"

local origin = "origin"
local db = 1

-- Redis
-- =====

local clients = {}

local function my_client ()
  local id = coroutine.running ()
  if not clients [id] then
    local result = redis.connect ({
      host = "127.0.0.1",
      port = 6379,
      use_copas = true,
      timeout   = 0.1
    })
    result:select (db)
    clients [id] = result
  end
  return clients [id]
end

-- Encode/Decoder
-- ==============

local Reference = {}

function Reference.new (resource)
  return setmetatable ({
    resource = resource
  }, Reference)
end

function Reference:__tostring ()
  return "=> ${resource}" % {
    resource = self.resource
  }
end

local Format = {}

function Format.decode (x)
  assert (type (x) == "string")
  local type  = x:sub (1, 1)
  local value = x:sub (3)
  if     type == "b" then
    local str = value:lower ()
    if     str == "true" then
      return true
    elseif str == "false" then
      return false
    end
  elseif type == "n" then
    return tonumber (value)
  elseif type == "s" then
    return value
  elseif type == "t" then
    return Reference.new (value)
  end
end

function Format.encode (x)
  local type  = type (x)
  if     type == "boolean" then
    return "b|" .. tostring (x)
  elseif type == "number" then
    return "n|" .. tostring (x)
  elseif type == "string" then
    return "s|" .. x
  elseif type == "table" then
    return "t|" .. x.resource
  end
end

-- Cache
-- =====

local Store = {
  __mode = "kv",
}

function Store:__index (resource)
  local client = my_client ()
  local result = {}
  local data   = client:hgetall (resource)
  local decode = Format.decode
  for k, v in pairs (data) do
    result [decode (k)] = decode (v)
  end
  self [resource] = result
  return result
end

local store = setmetatable ({}, Store)

-- Resource
-- ========

local channel = "updates"
local Resource = {}

function Resource.new (resource)
  return setmetatable ({
    resource = resource,
    data     = store [resource]
  }, Resource)
end

function Resource:__index (key)
  local result = self.data [key]
  if type (result) == "table" and getmetatable (result) == Reference then
    result = Resource.new (result.resource)
  end
  return result
end

function Resource:__newindex (key, value)
  local client   = my_client ()
  local resource = self.resource
  local encode   = Format.encode
  if type (value) == "table" then
    local subresource = "${resource}/${key}" % {
      resource = resource,
      key      = encode (key),
    }
    local t = {}
    local s = {}
    for k, v in pairs (value) do
      if type (v) == "table" then
        s [k] = v
      else
        t [#t + 1] = encode (k)
        t [#t + 1] = encode (v)
      end
    end
    if #t ~= 0 then
      client:hmset (subresource, table.unpack (t))
    end
    local sub = Resource.new (subresource)
    for k, v in pairs (s) do
      sub [k] = v
    end
    value = Reference.new (subresource)
  end
  client:hset    (resource, encode (key), encode (value))
  client:publish (channel, json.encode {
    origin   = origin,
    resource = resource,
    key      = key,
  })
  self.data [key] = value
end

function Resource:__tostring ()
  return "[ ${resource} ]" % {
    resource = self.resource
  }
end

-- Updater
-- =======

copas.addthread (function ()
  local c     = my_client ()
  local client = redis.connect {
    host      = "127.0.0.1",
    port      = 6379,
    use_copas = true,
    timeout   = 0,
  }
  client:select (db)
  local count  = 0
  local encode = Format.encode
  local decode = Format.decode
  for message, _ in client:pubsub { subscribe = { channel } } do
    if message.kind == "message" then
      count = count + 1
      if count % 10000 == 0 then
        print ("Received a lot of messages!")
      end
      local body     = json.decode (message.payload)
      if body.exit then
        copas.exitloop ()
        return
      end
      local resource = body.resource
      local key      = body.key
      local data     = rawget (store, resource)
      if data then
        local value = c:hget (resource, encode (key))
        data [key]  = decode (value)
      end
    end
  end
end)

-- Test
-- ====

local cosy      = "cosyverif.io"
local nb_create = 500
local nb_read   = 1000
local nb_write  = 500
local finished  = 0
local total     = 0

print ("# create: " .. tostring (nb_create))
print ("# write : " .. tostring (nb_write * nb_create))
print ("# read  : " .. tostring (nb_read  * nb_create))

local start_time = os.time ()

local function finish ()
  finished = finished + 1
  if finished == total then
    local client = my_client ()
    client:publish (channel, json.encode {
      exit = true,
    })
  end
end

local function do_read (i)
  local name = "user-${i}" % { i = i }
  local p = Resource.new (cosy) [i]
  for _ = 1, nb_read do
    assert (p.username == name)
    copas.sleep (0.0001)
  end
  finish ()
end

local function do_write (i)
  local p = Resource.new (cosy) [i]
  for _ = 1, nb_write do
    p.is_private = not p.is_private
    copas.sleep (0.0001)
  end
  finish ()
end

--[[
local function check_type ()
  do
    local p = Resource.new ("aaaa")
    p.a = "a"
    p.b = 1
    p.c = true
    p [1] = "1"
    p [true] = {}
  end
  collectgarbage ()
  do
    local p = Resource.new ("aaaa")
    assert (type (p.a) == "string")
    assert (type (p.b) == "number")
    assert (type (p.c) == "boolean")
    assert (type (p [1]) == "string")
    assert (type (p [true]) == "table")
  end
  os.exit (0)
end
copas.addthread (check_type)
--]]

local function do_create (i)
  local root = Resource.new (cosy)
  root [i] = {
    username = "user-${i}" % { i = i },
    password = "toto",
    fullname = "User ${i}" % { i = i },
    email    = "user.${i}@gmail.com" % { i = i },
  }
  total = total + 1
  copas.addthread (function () do_read  (i) end)
  total = total + 1
  copas.addthread (function () do_write (i) end)
  finish ()
end

do
  local client = redis.connect {
    host      = "127.0.0.1",
    port      = 6379,
    use_copas = false,
  }
  client:select (db)
  client:flushdb ()
end

copas.addthread (function ()
  local client = my_client ()
  client:flushdb ()
  total = total + nb_create
  for i = 1, nb_create do
    copas.addthread (function ()
      do_create (i)
    end)
  end
end)

-- Loop
-- ====

copas.loop ()


-- Statistics
-- ==========

do
  local client = redis.connect ({
    host = "127.0.0.1",
    port = 6379,
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
end
