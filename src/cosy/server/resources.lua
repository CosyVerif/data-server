local configuration = require "cosy.server.configuration"
                      require "cosy.server.redis"
                      require "cosy.util.string"
local copas         = require "copas.timer"
local json          = require "dkjson"

local redis = configuration.redis.client
local subscriber = configuration.redis.subscriber

local DATA     = {}
local CONTEXT  = {}

local DELETED  = {}
local DIRTY    = {}

local channel = "cosy-updates"
local store   = setmetatable ({}, { __mode = "kv" })

copas.addthread (function ()
  for message, _ in subscriber:pubsub { subscribe = { channel } } do
    if message.kind == "message" then
      local content = json.decode (message.payload)
      assert (content)
      local origin   = content.origin
      if origin ~= configuration.server.uuid then
        local resource = content.resource
        local action   = content.action
        local found    = store [resource]
        if found then
          found [DIRTY] = true
        end
        if action == "delete" then
          found [DELETED ] = true
          store [resource] = nil
        end
      end
    end
  end
end)

--[[
  Resources.register [typename] = class
  local r = Resources {
    username = ...,
    ...
  }
  local user = r.alinard
  local model = user.philosophers

  Permissions:
  r.is_private = true
  r.allow.x = "admin" | "read" | "write"
--]]

local Resources_mt = {}
local Resource_mt  = {}

function Resources_mt:__call (t)
  assert (type (t) == "table")
  return setmetatable ({
    [CONTEXT] = t,
  }, Resource_mt)
end

function Resources_mt:__index (key)
  local stored  = store [key]
  if stored then
    return stored
  end
  local exists = redis:exists (key)
  if not exists then
    return nil
  end
  stored = store [key] -- double check
  if stored then
    return stored
  end
  local data  = {
    resource = key,
    [DIRTY ] = true,
  }
  store [key] = data
  return setmetatable ({
    [CONTEXT] = self [CONTEXT],
    [DATA   ] = data,
  }, Resource_mt)
end

function Resources_mt:__newindex (key, value)
  if value ~= nil or type (value) ~= table then
    rawset (self, key, value)
    return
  end
  if value == nil then
    -- delete
    store [key] = nil
    redis:del (key)
    redis:publish (channel, json.encode {
      origin   = configuration.server.uuid,
      resource = key,
      action   = "delete",
    })
    return
  end
  if store [key] == value then
    return
  end
  -- Check validity:
  local data = {
    resource = key,
  }
  for k, v in pairs (value) do
    if type (k) == "string" then
      rawset (data, k, v)
      -- TODO: if v is table
    end
  end
  local ok, errors = self:__check (data)
  if not ok then
    error (errors)
  end
  -- Update resource:
  store [key] = data
  local parameters = {}
  for k, v in pairs (data) do
    if type (k) == "string" then
      parameters [#parameters + 1] = k
      parameters [#parameters + 1] = v
    end
  end
  redis:transaction (function (t)
    t:del (key)
    t:hmset (key, table.unpack (parameters))
  end)
  redis:publish (channel, json.encode {
    origin   = configuration.server.uuid,
    resource = key,
    action   = "create",
  })
end

--

function Resource_mt:__index (key)
  local data   = rawget (self, DATA)
  -- TODO: check access rights!
  if data [DIRTY] then
    self:__refresh ()
  end
  assert (not data [DELETED])
  return data [key]
end

function Resource_mt:__newindex (key, value)
  local data   = rawget (self, DATA)
  if data [DIRTY] then
    self:__refresh ()
  end
  -- TODO: check access rights!
  self [key] = value
  redis:hset (data.resource, key, value)
  redis:publish (channel, json.encode {
    origin   = configuration.server.uuid,
    resource = key,
    action   = "update",
  })
end

function Resource_mt:__eq (r)
  local lhs = rawget (self, DATA)
  local rhs = rawget (r   , DATA)
  return lhs.resource == rhs.resource
end

function Resource_mt:__refresh ()
  local data     = rawget (self, DATA)
  if not data [DIRTY] then
    return
  end
  local key      = data.resource
  local received = redis:hgetall (key)
  if pairs (received) (received) == nil then
    data [DELETED] = true
    return
  end
  for k in pairs (data) do
    if type (k) == "string" and not received [k] then
      rawset (data, k, nil)
    end
  end
  for k, v in pairs (received) do
    if type (k) == "string" then
      rawset (data, k, v)
    end
  end
end

function Resource_mt:__ipairs ()
  local data = rawget (self, DATA)
  self:__refresh ()
  return coroutine.wrap (function ()
    for k, v in ipairs (data) do
      coroutine.yield (k, v)
    end
  end)
end

function Resource_mt:__pairs ()
  local data = rawget (self, DATA)
  self:__refresh ()
  return coroutine.wrap (function ()
    for k, v in pairs (data) do
      coroutine.yield (k, v)
    end
  end)
end

function Resource_mt:__mod (t)
  assert (type (t) == "table")
  local data     = rawget (self, DATA)
  local resource = data.resource
  self:__refresh ()
  local parameters = {}
  for k, v in pairs (t) do
    if type (k) == "string" then
      parameters [#parameters + 1] = k
      parameters [#parameters + 1] = v
      data [k] = v
    end
  end
  t:hmset (resource, table.unpack (parameters))
  redis:publish (channel, json.encode {
    origin   = configuration.server.uuid,
    resource = resource,
    action   = "update",
  })
end

local resources = setmetatable ({}, Resources_mt)

local Resources = {
  classes = {}
}

function Resources:register (name, class)
  assert (not Resources_mt.classes [name])
  Resources_mt.classes [name] = class
end

function Resources:root (t)
  local proxy = setmetatable ({
    [CONTEXT] = t,
  }, Resources_mt)
  return proxy [configuration.server.root]
  -- When loading:
  -- * wrap the data within a proxy (context + dirty + ...)
  -- * add functions contained in the class
end

return Resources
