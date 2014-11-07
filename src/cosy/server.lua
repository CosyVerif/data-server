local configuration = require "cosy.server.configuration"
local resource      = require "cosy.server.resource"
local redis         = require "cosy.server.redis"
local copas         = require "copas"
local socket        = require "socket"
local ssl           = require "ssl"
local base64        = require "base64"

local host   = configuration.server.host
local port   = configuration.server.port
local socket = socket.bind (host, port)

-- HTTP
-- ====

local function parse_http (skt)
  local result = {}
  local firstline = skt:receive "*l"
  local method, resource = firstline:match "(%a+)%s+(%S)%s+HTTP/1.1"
  result.method   = method:lower ()
  result.resource = resource
  while true do
    local line = skt:receive "*l"
    if line == "" then
      break
    end
    local name, value = line:match "([a-zA-Z%-]+):%s*(.*)"
    result [name:lower ()] = value
  end
  return result
end

-- Authentication
-- ==============

local identity_cache = setmetatable ({}, { __mode = "kv" })

local function identify (context)
  local request = context.request
  if request.authorization then
    local encoded = request.authorization:match "%s*Basic (.+)%s*"
    local decoded = base64.decode (encoded)
    local username, password = decoded:match "(%w+):(.*)"
    -- Check validity:
    local cached = identity_cache [username]
    if cached and cached == password then
      context.username = username
      context.password = password
    else
      local client = redis:get ()

    end
    context.username = username
    context.password = password
  end
end


local function handler (skt)
  skt = copas.wrap (skt)
  local request = parse_http (skt)
end

copas.addserver (socket, handler)
copas.loop ()
