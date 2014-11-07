local configuration = require "cosy.server.configuration"
local resource      = require "cosy.server.resource"
local _             = require "cosy.util.string"
local copas         = require "copas"
local socket        = require "socket"
--local ssl           = require "ssl"
local base64        = require "base64"
local json          = require "cjson"

local host   = configuration.server.host
local port   = configuration.server.port

-- HTTP
-- ====

local Context = {}

Context.__index = Context

function Context.new (skt)
  return setmetatable ({
    skt     = skt,
    request  = {
      method   = nil,
      resource = nil,
      headers  = {},
      body     = nil, -- iterator function
    },
    response = {
      code    = nil,
      message = nil,
      reason  = nil,
      headers = {},
      body    = nil, -- iterator function
    },
  }, Context)
end

function Context:receive ()
  local skt       = self.skt
  local request   = self.request
  local headers   = request.headers
  local firstline = skt:receive "*l"
  local method, resource = firstline:match "(%a+)%s+(%S)%s+HTTP/1.1"
  if not method or not resource then
    error {
      code    = 400,
      message = "Bad Request",
    }
  end
  request.method   = method:lower ()
  request.resource = resource
  while true do
    local line = skt:receive "*l"
    if line == "" then
      break
    end
    local name, value = line:match "([a-zA-Z%-]+):%s*(.*)"
    headers [name:lower ():gsub ("-", "_")] = value
  end
end

function Context:send ()
  local skt       = self.skt
  local response  = self.response
  local headers   = response.headers
  local body      = response.body
  assert (response.code)
  -- Send prefix:
  local firstline = "HTTP/1.1 ${code} ${message}\r\n" % {
    code    = response.code,
    message = response.message,
  }
  skt:send (firstline)
  -- Cleanup body and compute content-length:
  if body == nil then
    body = ""
    response.headers.content_length = 0
  elseif type (body) == "boolean" or type (body) == "number" then
    body = tostring (body)
    response.headers.content_length = #(body)
  elseif type (body) == "string" then
    response.headers.content_length = #(body)
  elseif type (body) == "table" then
    body = json.encode (body)
    response.headers.content_length = #(body)
  elseif type (body) == "function" then
    -- TODO
  else
    assert (false)
  end
  -- Send headers:
  for k, v in pairs (headers) do
    local header = "${name}: ${value}\r\n" % {
      name  = k:gsub ("_", "-"),
      value = v,
    }
    skt:send (header)
  end
  skt:send "\r\n"
  -- Send body:
  if type (body) == "string" then
    skt:send (body)
  elseif type (body) == "function" then
    for s in body () do
      skt:send (s) -- FIXME
    end
  end
end

function Context:flush ()
  local skt = self.skt
  skt:flush ()
end

-- Authentication
-- ==============

local identities = setmetatable ({}, { __mode = "kv" })

function Context:identify ()
  local request = self.request
  if request.headers.authorization then
    local encoded = request.headers.authorization:match "%s*Basic (.+)%s*"
    local decoded = base64.decode (encoded)
    local username, password = decoded:match "(%w+):(.*)"
    -- Check validity:
    local cached = identities [username]
    if not (cached and cached == password) then
      local user = resource {} [username]
      if not user then
        error {
          code    = 401,
          message = "Unauthorized",
          reason  = "user does not exist",
        }
      elseif user.type ~= "user" then
        error {
          code    = 401,
          message = "Unauthorized",
          reason  = "entity is not a user",
        }
      elseif not user:check_password (password) then
        error {
          code    = 401,
          message = "Unauthorized",
          reason  = "password is erroneous",
        }
      end
      identities [username] = password
    end
    self.username = username
    self.password = password
  end
end

--[=[
local function handler (skt)
  skt = copas.wrap (skt)
  while true do
    local context = Context.new (skt)
    print "before receive"
    context:receive  ()
    print "before identity"
    context:identify ()
    print "after identity"
    context:send ()
    print "after send"
    context:flush ()
  end
end
--]=]
local function handler (skt)
  skt = copas.wrap (skt)
  while true do
    local context = Context.new (skt)
    local function perform ()
      local ok, r = pcall (function ()
        context:receive  ()
        context:identify ()
      end)
      if not ok then
        print ("Error:", r)
        context.response.code    = r.code
        context.response.message = r.message
        context.response.body    = r.reason .. "\r\n"
      end
      context:send ()
    end
    local ok, err = pcall (perform)
    if not ok then
      print ("Error:", err)
      context.response.code    = 500
      context.response.message = "Internal Server Error"
      context.response.headers.connection = "close"
      context:send ()
      break
    end
    context:flush ()
  end
end

copas.addserver (socket.bind (host, port), handler)
copas.loop ()
