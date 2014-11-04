local configuration = require "cosy.server.configuration"
                      require "cosy.util.string"
local copas         = require "copas.timer"
local json          = require "cjson"
local redis         = require "redis"

local CONTEXT  = {}
local DATA     = {}
local DELETED  = {}
local DIRTY    = {}

local function expand (t)
  local result = {}
  for k, v in pairs (t) do
    result [#result + 1] = k
    result [#result + 1] = v
  end
  return table.unpack (result)
end

local host    = configuration.redis.host
local port    = configuration.redis.port
local db      = configuration.redis.database
local channel = "cosy-updates"

local Redis_mt = {
  __mode  = "kv",
  __copas = {},
  __sync  = {},
}

function Redis_mt:__call (synchronous)
  local id = coroutine.running ()
  local container
  if synchronous then
    container = Redis_mt.__sync
  else
    container = Redis_mt.__copas
  end
  local found = container [id]
  if found then
    return found
  end
  local result = redis.connect ({
    host      = host,
    port      = port,
    use_copas = not synchronous,
  })
  result:select (db)
  container [id] = result
  return result
end

local Redis = setmetatable ({}, Redis_mt)

do
  local client = Redis (true)
  local root = configuration.server.root
  client:hmset (root, expand {
    _resource = root,
    _type     = "root",
  })
end

local Store_mt = {
  __mode = "kv",
}

function Store_mt:__index (key)
  local data = {
    _resource = key,
    [DIRTY]   = true,
  }
  self [key] = data
  return data
end

local store   = setmetatable ({}, Store_mt)

local Resources_mt = {
  names   = {},
  classes = {},
}
local Resources = setmetatable ({}, Resources_mt)

local Resource_mt  = {}

function Resources_mt:__index (key)
  local _ = self
  if type (key) == "string" then
    return Resources_mt.classes [key]
  elseif type (key) == "table" then
    return Resources_mt.names [key]
  else
    assert (false)
  end
end

function Resources_mt:__newindex (key, value)
  local _ = self
  assert (type (key) == "string")
  assert (type (value) == "table")
  Resources_mt.names [value] = key
  Resources_mt.classes [key] = value
end

function Resources_mt:__call (t)
  local _ = self
  assert (type (t) == "table")
  return setmetatable ({
    [CONTEXT] = t,
    [DATA   ] = store [configuration.server.root],
  }, Resource_mt)
end

function Resource_mt:__index (key)
  local in_mt = Resource_mt [key]
  if in_mt then
    return in_mt
  end
  local data = self [DATA]
  if data [DIRTY] then
    self:__refresh ()
  end
  local class    = Resources [data._type]
  local in_class = class [key]
  if in_class then
    return in_class
  end
  local subdata = data [key]
  if not subdata then
    print "here"
    assert (false)
    return nil
  elseif type (subdata) == "string" and subdata:find ("@=@", 1, true) == 1 then
    local subresource = subdata:sub (4)
    return setmetatable ({
      [CONTEXT] = self [CONTEXT],
      [DATA   ] = store [subresource],
    }, Resource_mt)
  elseif type (subdata) == "table" then
    return setmetatable ({
      [CONTEXT] = self [CONTEXT],
      [DATA   ] = subdata,
    }, Resource_mt)
  else
    return subdata
  end
end

function Resource_mt:__create (resource, t)
  assert (type (t) == "table")
  assert (not t._resource)
  t._resource = resource
  t._type     = Resources [getmetatable (t)]
  assert (t._type)
  local parameters = {}
  for k, v in pairs (t) do
    local kt = type (k)
    local vt = type (v)
    if kt == "string" then
      if vt == "string"  or vt == "boolean" or vt == "number"  then
        parameters [k] = v
      elseif vt == "table" then
        if not v._resource then
          local subresource = "${resource}/${key}" % {
            resource = resource,
            key      = k,
          }
          self:__create (subresource, v)
        end
        parameters [k] = "@=@${target}" % {
          target = v._resource
        }
      end
    end
  end
  store [resource] = t
  local client = Redis ()
  client:del     (resource)
  client:hmset   (resource, expand (parameters))
  client:publish (channel, json.encode {
    origin   = configuration.server.uuid,
    resource = resource,
    action   = "create",
  })
  return resource
end

function Resource_mt:__newindex (key, value)
  assert (type (key) ~= "table")
  local data = self [DATA]
  if data [DIRTY] then
    self:__refresh ()
  end
  local class    = Resources [data._type]
  assert (not class [key])
  local id = tostring (coroutine.running ())
  local resource = data._resource
  data [key] = value
  local vt = type (value)
  local client = Redis ()
  if vt == "string"  or vt == "boolean" or vt == "number"  then
    client:hset (resource, key, value)
  elseif vt == "table" then
    if not value._resource then
      local subresource = "${resource}/${key}" % {
        resource = resource,
        key      = key,
      }
      self:__create (subresource, value)
    end
    client:hset (resource, key, "@=@" .. tostring (value._resource))
  else
    assert (false)
  end
  client:publish (channel, json.encode {
    origin   = configuration.server.uuid,
    resource = resource,
    action   = "update",
  })
end

function Resource_mt:__eq (r)
  local lhs = rawget (self, DATA)
  local rhs = rawget (r   , DATA)
  return lhs._resource == rhs._resource
end

function Resource_mt:__refresh ()
  local data = self [DATA]
  if not data [DIRTY] then
    return
  end
  local key      = data._resource
  local client   = Redis ()
  local received = client:hgetall (key)
  if pairs (received) (received) == nil then
    data [DELETED] = true
    assert (false)
  end
  print ("in " .. key)
  for k in pairs (data) do
    if type (k) == "string" and not received [k] then
      print ("remove " .. k)
      rawset (data, k, nil)
    end
  end
  for k, v in pairs (received) do
    print ("add " .. k .. " = " .. tostring (v))
    if type (k) == "string" then
      rawset (data, k, v)
    end
  end
  data [DIRTY] = nil
end

function Resource_mt:__ipairs ()
  local data = self [DATA]
  self:__refresh ()
  return coroutine.wrap (function ()
    for k in ipairs (data) do
      coroutine.yield (k, self [k])
    end
  end)
end

function Resource_mt:__pairs ()
  local data = self [DATA]
  self:__refresh ()
  return coroutine.wrap (function ()
    for k in pairs (data) do
      if type (k) == "string" or type (k) == "boolean" or type (k) == "number" then
        coroutine.yield (k, self [k])
      end
    end
  end)
end

function Resource_mt:__mod (t)
  assert (type (t) == "table")
  local data     = self [DATA]
  local resource = data._resource
  self:__refresh ()
  local parameters = {}
  for k, v in pairs (t) do
    if type (k) == "string" then
      parameters [k] = v
      data       [k] = v
    end
  end
  local client = Redis ()
  client:hmset   (resource, expand (parameters))
  client:publish (channel, json.encode {
    origin   = configuration.server.uuid,
    resource = resource,
    action   = "update",
  })
end

copas.addthread (function ()
  local client = Redis ()
  for message, _ in client:pubsub { subscribe = { channel } } do
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

Resources.root = {}


return Resources
