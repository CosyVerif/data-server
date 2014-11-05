local _         = require "cosy.util.string"
local copas     = require "copas"
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

local proxy

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
    return proxy (value)
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

local Store_mt = {
  __mode = "v",
}

function Store_mt:__index (resource)
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

local store = setmetatable ({}, Store_mt)

-- Resource
-- ========

local channel = "updates"
local Resource_mt = {}

function proxy (resource)
  return setmetatable ({
    resource = resource,
  }, Resource_mt)
end

function Resource_mt:__index (key)
  if not rawget (self, "data") then
    rawset (self, "data", store [self.resource])
  end
  print (key)
  return self.data [key]
end

function Resource_mt:__newindex (key, value)
  if not rawget (self, "data") then
    rawset (self, "data", store [self.resource])
  end
  local client   = my_client ()
  local resource = self.resource
  local encode   = Format.encode
  if type (value) == "table" then
    local subresource = "${resource}/${key}" % {
      resource = resource,
      key      = encode (key),
    }
    value.resource = subresource
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
    local sub = proxy (subresource)
    for k, v in pairs (s) do
      sub [k] = v
    end
  end
  client:hset    (resource, encode (key), encode (value))
  client:publish (channel, json.encode {
    origin   = origin,
    resource = resource,
    key      = key,
  })
  self.data [key] = value
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
      local body = json.decode (message.payload)
      if body.origin ~= origin then
        local resource = body.resource
        local data     = store [resource]
        local key      = body.key
        data [key] = decode (c:hget (resource, encode (key)))
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
    print ("# keys: " .. tostring (client:dbsize ()))
    local finish_time = os.time ()
    local duration = finish_time - start_time
    print ("Time: " .. tostring (duration) .. " seconds.")
    local operations = nb_create + nb_write * nb_create + nb_read  * nb_create
    print ("Average operations: " .. tostring (operations / duration) .. " per second.")
    os.exit (0)
  end
end

local function do_read (i)
  local name = "user-${i}" % { i = i }
  local p = proxy (cosy) [i]
  for k = 1, nb_read do
    assert (p.username == name)
  end
  finish ()
end

local function do_write (i)
  local p = proxy (cosy) [i]
  for k = 1, nb_write do
    p.is_private = not p.is_private
  end
  finish ()
end

--[[
local function check_type ()
  do
    local p = proxy ("aaaa")
    p.a = "a"
    p.b = 1
    p.c = true
    p [1] = "1"
    p [true] = {}
  end
  collectgarbage ()
  do
    local p = proxy ("aaaa")
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
  local root = proxy (cosy)
  root [i] = {
    username = "user-${i}" % { i = i },
    password = "toto",
    fullname = "User ${i}" % { i = i },
    email    = "user.${i}@gmail.com" % { i = i },
  }
  total = total + 1
  copas.addthread (function ()
    do_read (i)
  end)
  total = total + 1
  copas.addthread (function ()
    do_write (i)
  end)
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

total = total + nb_create
for i = 1, nb_create do
  copas.addthread (function ()
    do_create (i)
  end)
end

-- Loop
-- ====

copas.loop ()
