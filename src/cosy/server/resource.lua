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
  local type = result.type
  if type then
    local class = require ("cosy.server.resource." .. type)
    for k, v in pairs (class) do
      result [k] = v
    end
  end
  self [resource] = result
  return result
end

local store = setmetatable ({}, Store)

-- Resource
-- ========

local Resource = {}

function Resource.new (context, resource)
  local data        = store [resource]
  local new_context = {}
  for k, v in pairs (context) do
    new_context [k] = v
  end
  new_context.is_owner  = data.is_owner  and data:is_owner  (context) or context.is_owner
  new_context.can_read  = data.can_read  and data:can_read  (context) or context.can_read
  new_context.can_write = data.can_write and data:can_write (context) or context.can_write
  return setmetatable ({
    context   = new_context,
    resource  = resource,
    data      = data,
  }, Resource)
end

function Resource:__index (key)
  local context  = self.context
  local resource = self.resource
  local data     = self.data
  local result = data [key]
  if type (result) == "table" and getmetatable (result) == Reference then
    return Resource.new (context, result.resource)
  elseif not context.can_read then
      error {
        code     = 403,
        message  = "Forbidden",
        resource = resource,
      }
  end
  return result
end

function Resource:__newindex (key, value)
  local context  = self.context
  local resource = self.resource
  local data     = self.data
  if not context.can_write then
    error {
      code     = 403,
      message  = "Forbidden",
      resource = resource,
    }
  end
  local client   = Client:get ()
  local encode   = Format.encode
  local action
  if value == nil then
    action = "delete"
  elseif client:hexists (resource, encode (key)) then
    action = "update"
  else
    action = create
  end
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
--      client:transaction (function (c)
      client:del   (subresource)
      client:hmset (subresource, table.unpack (t))
--      end)
    end
    local sub = Resource.new (context, subresource)
    for k, v in pairs (s) do
      sub [k] = v
    end
    value = Reference.new (subresource)
  end
--  client:transaction (function (c)
  if action == "delete" then
    client:hdel (resource, encode (key))
  else
    client:hset (resource, encode (key), encode (value))
  end
  client:publish (channel, json.encode {
    origin   = uuid,
    resource = resource,
    action   = action,
    keys     = { key },
  })
--  end)
  data [key] = value
end

function Resource:__call (changes)
  local context  = self.context
  local resource = self.resource
  local data     = self.data
  if not context.can_write then
    error {
      code     = 403,
      message  = "Forbidden",
      resource = resource,
    }
  end
  local client   = Client:get ()
  local encode   = Format.encode
  assert (type (changes) == "table")
  local keys = {}
  local t = {}
  local s = {}
  for k, v in pairs (changes) do
    keys [#keys + 1] = encode (k)
    if type (v) == "table" then
      s [k] = v
    else
      t [#t + 1] = encode (k)
      t [#t + 1] = encode (v)
      data [k] = v
    end
  end
  if #t ~= 0 then
--    client:transaction (function (c)
    client:hmset (resource, table.unpack (t))
    client:publish (channel, json.encode {
      origin   = uuid,
      resource = resource,
      keys     = keys,
    })
--    end)
  end
  for k, v in pairs (s) do
    local sub = self [k]
    if sub == nil then
      self [k] = v
    elseif type (sub) == "table" then
      sub (v)
    else
      assert (false)
    end
  end
end

function Resource:__ipairs ()
  local data = self.data
  return coroutine.wrap (function ()
    for k, v in ipairs (data) do
      if type (v) ~= "function" then
        coroutine.yield (k, self [k])
      end
    end
  end)
end

function Resource:__pairs ()
  local data = self.data
  return coroutine.wrap (function ()
    for k, v in pairs (data) do
      if type (v) ~= "function" then
        coroutine.yield (k, self [k])
      end
    end
  end)
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
  local encode = Format.encode
  local decode = Format.decode
  for message, _ in client:pubsub { subscribe = { channel } } do
    if message.kind == "message" then
      local body     = json.decode (message.payload)
      local resource = body.resource
      local keys     = body.keys
      local data     = rawget (store, resource)
      if data then
        local ekeys = {}
        for i, k in ipairs (keys) do
          ekeys [i] = encode (k)
        end
        local values = c:hmget (resource, "nil", table.unpack (ekeys))
        for i, v in ipairs (values) do
          data [keys [i]] = decode (v)
        end
      end
    end
  end
end)

-- Root Resource
-- =============

do
  local Root = require "cosy.server.resource.root"
  local client = redis.connect ({
    host      = host,
    port      = port,
    use_copas = false,
    timeout   = 0.1
  })
  client:select (db)
  local encode = Format.encode
  local r = Root.create ()
  for k, v in pairs (r) do
    if not client:hexists (root, k) then
      client:hset (root, encode (k), encode (v))
    end
  end
end

return function (context)
  return Resource.new (context, root)
end
