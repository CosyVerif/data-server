local configuration = require "cosy.server.configuration"

local trim       = configuration.turbo.escape.trim
local redis      = configuration.redis.client
local subscriber = configuration.redis.subscriber
local resources  = configuration.resources
local turbo      = configuration.turbo
local loop       = configuration.loop

local RESOURCE = {}
local DATA     = {}
local DIRTY    = {}
local HANDLER  = {}

local function publish (resource)
  redis:publish (resource, "Updated!")
end

local function refresh (proxy)
  local resource = proxy [RESOURCE]
  local data     = redis:hgetall (resource)
  rawset (proxy, DATA, data)
end

local function subscribe (resource)
  subscriber:subscribe (resource)
  subscriber:start (function (msg)
    local proxy = resources [resource]
    if proxy then
      rawset (proxy, DIRTY, true)
    else
      subscriber:unsubscribe (resource)
    end
  end)
end

local function commit (proxy)
  -- Set timer to commit to redis after a small timeout:
  local handler = rawget (proxy, HANDLER)
  if handler then
    loop:clear_interval (handler)
  end
  local time = turbo.util.gettimeofday()
  handler = loop:set_interval (configuration.server.commit_timeout, function ()
      local resource   = proxy [RESOURCE]
      local data       = proxy [DATA]
      local parameters = {}
      for k, v in pairs (data) do
        parameters [#parameters + 1] = k
        parameters [#parameters + 1] = v
      end
      redis:hmset (resource, table.unpack (parameters))
      publish (resource)
      loop:clear_interval (handler)
      rawset (proxy, HANDLER, nil)
    end)
  rawset (proxy, HANDLER, handler)
end

local function create (self, data)
  local resource = data.resource
  assert (resource)
  -- Check if resource does not already exist:
  if resources [resource]
  or redis:exists (resource) then
    error {
      "Resource ${resource} already exists." % { resource = resource },
    }
  end
  -- Check data:
  if data then
    local ok, errors = self:check (data)
    if not ok then
      error (errors)
    end
  end
  -- Create (lazily) resource:
  local result = setmetatable ({
    [RESOURCE] = resource,
    [DATA    ] = data or {},
    [DIRTY   ] = data == nil,
    [HANDLER ] = nil,
  }, self)
  resources [resource] = result
  subscribe (resource)
  commit (result)
  return result
end

local function load (self, resource)
  -- Try to find in cache:
  local result = resources [resource]
  if result then
    return result
  end
  -- Load (lazily) resource:
  result = setmetatable ({
    [RESOURCE] = resource,
    [DATA    ] = data or {},
    [DIRTY   ] = true,
    [HANDLER ] = nil,
  }, self)
  if result then
    resources [resource] = result
    subscribe (resource)
    return result
  end
  error {
    "Resource ${resource} does not exist." % {
      resource = resource,
    }
  }
end

local function __index (self, key)
  local resource = rawget (self, RESOURCE)
  -- If data is marked as dirty, reload it.
  local dirty = rawget (self, DIRTY)
  if dirty then
    refresh (self)
  end
  return rawget (self, DATA) [key]
end

local function __newindex (self, key, value)
  refresh (self)
  local resource = rawget (self, RESOURCE)
  local data     = rawget (self, DATA)
  -- Update data:
  local data = rawget (self, data)
  local old  = data [key]
  data [key] = value
  local ok, errors = self.check (data)
  if not ok then
    data [key] = old
    error (errors)
  end
  commit (self)
end

local Resource = {}

function Resource.create ()
  return {
    create     = create,
    load       = load,
    __index    = __index,
    __newindex = __newindex,
  }
end

return Resource
