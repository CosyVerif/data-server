local _             = require "cosy.util.string"
local configuration = require "cosy.server.configuration"
local redis         = require "cosy.server.redis"
local copas         = require "copas"
local json          = require "cjson"

local root_id = configuration.server.root
local uuid    = configuration.server.uuid

local channel = "cosy-updates"

--[[
Each resource is represented as:
resource:
  "_parent:" "@resource"
  "_properties": json
  "sub": "@resource"
  "sub": "@resource"
--]]

-- Cache
-- =====

local Store = {
  __mode = "kv",
}

function Store:__index (id)
  local client = redis:get ()
  local data   = client:hgetall (id)
  if data._properties then
    data._properties = json.decode (data._properties)
    local type  = data._properties.type:lower ()
    data._class = require ("cosy.server.resource." .. type)
  end
  self [id] = data
  return data
end

local store = setmetatable ({}, Store)

-- Resource
-- ========

local Resource = {}

local Properties = {}

Resource.__index = Resource

function Resource.root (context)
  local data   = store [root_id]
  local class  = data._class
  local result = setmetatable ({
    _id       = root_id,
    _context  = context,
  }, Resource)
  if class then
    context.is_owner  = class.is_owner  (result, context)
    context.can_read  = class.can_read  (result, context)
    context.can_write = class.can_write (result, context)
  end
  return result
end

function Resource:__add (t)
  local id     = self._id
  if Resource.exists (self) then
    error {
      code    = 409,
      message = "Conflict",
      reason  = "resource ${id} exists already" % { id = id },
    }
  end
  local key    = self._key
  local parent = self._parent
  if parent and not Resource.exists (parent) then
    error {
      code    = 404,
      message = "Not Found",
      reason  = "parent resource ${id} does not exist" % { id = parent._id },
    }
  end
  local script = [[
assert (redis.call ("EXISTS", ${resource}) == 0)
assert (redis.call ("HEXISTS", ${parent}, "${key}") == 0)
redis.call ("HSET", ${parent}, "${key}", ${resource})
redis.call ("HSET", ${resource}, "_properties", [=[${properties}]=])
redis.call ("HSET", ${resource}, "_parent", ${parent})
redis.call ("PUBLISH", "${channel}", [=[${message}]=])
]] % {
    parent     = "KEYS [1]",
    resource   = "KEYS [2]",
    key        = key,
    properties = json.encode (t),
    channel    = channel,
    message    = json.encode {
      origin   = uuid,
      {
        resource = parent._id,
        action   = "update",
        keys     = { key },
      },
      {
        resource = id,
        action   = "create",
      }
    },
  }
  local client = redis:get ()
  if not pcall (function ()
    client:eval (script, 2, parent._id, id)
  end) then
    error {
      code    = 409,
      message = "Conflict",
      reason  = "resource ${id} exists already" % { id = id },
    }
  end
  local data = store [id]
  data._properties = t
  data._parent     = parent._id
  return self
end

function Resource:exists ()
  local id   = self._id
  local data = store [id]
  return data._properties ~= nil
end

function Resource:__div (key)
  assert (type (key) == "string")
  local context = self._context
  local id      = "${parent}/${key}" % {
    parent = self._id,
    key    = key,
  }
  local data   = store [id]
  local class  = data._class
  local result = setmetatable ({
    _id       = id,
    _context  = context,
    _key      = key,
    _parent   = self,
  }, Resource)
  if data._class then
    context.is_owner  = class.is_owner  (result, context)
    context.can_read  = class.can_read  (result, context)
    context.can_write = class.can_write (result, context)
  end
  return result
end

function Resource:__index (key)
  local id     = self._id
  local data   = store [id]
  local class  = data._class
  if class [key] then
    return class [key]
  else
    return Properties.new (self) [key]
  end
end

function Resource:__newindex (key, value)
  Properties.new (self) [key] = value
end

function Resource:__mod (f)
  assert (type (f) == "function")
  local id         = self._id
  local data       = store [id]
  local properties = data._properties
  local ok, err = pcall (f, properties)
  if not ok then
    error {
      code    = 409,
      message = "Conflict",
      reason  = err,
    }
  end
  local client = redis:get ()
  client:hset (self._id, "_properties", json.encode (properties))
end

function Resource:__len ()
  local id     = self._id
  local data   = store [id]
  local result = 0
  for k in pairs (data) do
    if type (k) == "string" and k:sub (1, 1) ~= "_" then
      result = result + 1
    end
  end
  return result
end

function Resource:__ipairs ()
  local _ = self
  assert (false)
end

function Resource:__pairs ()
  local id    = self._id
  local data  = store [id]
  local count = 1
  local k     = nil
  return function ()
    if count % 10 == 0 then
      coroutine.yield ()
    end
    count = count + 1
    repeat
      k = next (data, k)
    until k == nil or (type (k) == "string" and k:sub (1,1) ~= "_")
    return k, k and self / k or nil
  end
end

function Resource:__tostring ()
  return self._id .. "*"
end

function Properties.new (resource, properties)
  local id   = resource._id
  local data = store [id]
  return setmetatable ({
    _resource = resource,
    _current  = properties or data._properties
  }, Properties)
end

function Properties:__index (key)
  local resource = self._resource
  local current  = self._current
  local value    = current [key]
  if type (value) == "table" then
    return Properties.new (resource, value)
  else
    return value
  end
end

function Properties:__newindex (key, value)
  local resource = self._resource
  local id       = resource._id
  local data     = store [id]
  local current  = self._current
  local client   = redis:get ()
  current [key] = value
  client:hset (resource._id, "_properties", json.encode (data._properties))
end

-- Updater
-- =======

copas.addthread (function ()
  local sub    = redis:sub ()
  for message, _ in sub:pubsub { subscribe = { channel } } do
    print (json.encode (message))
    if message.kind == "message" then
      local body     = json.decode (message.payload)
      local origin   = body.origin
      if origin ~= uuid then
        for _, part in ipairs (body) do
          local id = part.resource
          rawset (store, id, nil)
        end
      end
    end
  end
end)

-- Root Resource
-- =============

do
  local Root   = require "cosy.server.resource.root"
  local client = redis:get (true)
  local script = [[
assert (redis.call ("EXISTS", ${root}) == 0)
redis.call ("HSET", ${root}, "_properties", [=[${properties}]=])
]] % {
    root       = "KEYS [1]",
    properties = json.encode (Root.create ()),
  }
  pcall (function () client:eval (script, 1, root_id) end)

  --[=[
  local root = Resource.root ()
  for _, s in ipairs {
    "abcde",
    "tata",
    "tete",
    "titi",
    "toto",
    "tutu",
  } do
    local r = root / s
    pcall (function () Resource.create (r, Root.create ()) end)
    r.machin = {}
    r.machin.chose = s
  end
  print ("#", #root)
  for k, v in pairs (root) do
    print (k, v)
  end
  --]=]
end

return {
  root   = function (context)
    return Resource.root (context)
  end,
  exists = Resource.exists,
}
