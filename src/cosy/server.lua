local configuration = require "cosy.server.configuration"
local resource      = require "cosy.server.resource"
local _             = require "cosy.util.string"
local copas         = require "copas"
local socket        = require "socket"
local mime          = require "mime"
local json          = require "cjson"
local crypto        = require "crypto"
--local ssl           = require "ssl"

local base64 = {}
base64.encode = mime.b64
base64.decode = mime.unb64
local sha1 = function (s)
  return crypto.digest ("sha1", s, true)
end

local host   = configuration.server.host
local port   = configuration.server.port

-- HTTP
-- ====

local Context = {}

Context.__index = Context

function Context.new (skt)
  return setmetatable ({
    raw_skt = skt,
    skt     = copas.wrap (skt),
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

-- WebSocket
-- =========
--
-- Code partially taken from https://github.com/lipp/lua-websockets

local ws_guid = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11"

function Context:websocket ()
  local headers = self.request.headers
  if not headers.connection or headers.connection:lower () ~= "upgrade"
  or not headers.upgrade    or headers.upgrade ~= "websocket" then
    return
  end
  local key       = headers.sec_websocket_key
  local version   = headers.sec_websocket_version
  local protocols = headers.sec_websocket_protocol
  if not key then
    error {
      code = 400,
      message = "Bad Request",
      reason  = "header Sec-WebSocket-Key is missing",
    }
  end
  self.response = {
    code    = 101,
    message = "Switching Protocols",
    headers = {
      upgrade    = "websocket",
      connection = "upgrade",
      sec_websocket_accept   = base64.encode (sha1 (key .. ws_guid)),
      sec_websocket_protocol = "cosy",
    },
  }
  self:send ()
end

local function handler (skt)
  while true do
    local context = Context.new (skt)
    local function perform ()
      local ok, r = pcall (function ()
        context:receive   ()
        context:identify  ()
        context:websocket ()
        context.response.code    = 200
        context.response.message = "OK"
      end)
      if not ok then
--        print ("Error:", r)
        context.response.code    = r.code
        context.response.message = r.message
        context.response.body    = r.reason .. "\r\n"
      end
      context:send ()
    end
    local ok, err = pcall (perform)
    if not ok then
--      print ("Error:", err)
      context.response.code    = 500
      context.response.message = "Internal Server Error"
      context.response.headers.connection = "close"
      context:send ()
      context.raw_skt:shutdown ()
      break
    end
    context:flush ()
  end
end

copas.addserver (socket.bind (host, port), handler)
copas.loop ()
