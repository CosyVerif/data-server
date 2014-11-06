local _             = require "cosy.util.string"
local configuration = require "cosy.server.configuration"
local copas         = require "copas"
local json          = require "cjson"
local redis         = require "redis"

local host = configuration.redis.host
local port = configuration.redis.port
local db   = configuration.redis.database
local root = configuration.server.root
local uuid = configuration.server.uuid

local channel = "cosy-updates"

-- Redis
-- =====

local Client = {}

function Client:get ()
  local id = coroutine.running ()
  if not self [id] then
    local result = redis.connect ({
      host      = host,
      port      = port,
      use_copas = true,
      timeout   = 0.1
    })
    result:select (db)
    self [id] = result
  end
  return self [id]
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
  local client = Client:get ()
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

local Resource = {}

function Resource.new (context, resource)
  return setmetatable ({
    context  = context,
    resource = resource,
    data     = store [resource]
  }, Resource)
end

function Resource:__index (key)
  local result = self.data [key]
  if type (result) == "table" and getmetatable (result) == Reference then
    result = Resource.new (self.context, result.resource)
  end
  return result
end

function Resource:__newindex (key, value)
  local client   = Client:get ()
  local context  = self.context
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
    local sub = Resource.new (context, subresource)
    for k, v in pairs (s) do
      sub [k] = v
    end
    value = Reference.new (subresource)
  end
  client:hset    (resource, encode (key), encode (value))
  client:publish (channel, json.encode {
    origin   = uuid,
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
  local c     = Client:get ()
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

return function (context)
  return Resource.new (context, root)
end
