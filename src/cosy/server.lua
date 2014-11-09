local configuration = require "cosy.server.configuration"
local resource      = require "cosy.server.resource"
local _             = require "cosy.util.string"
local copas         = require "copas"
local socket        = require "socket"
local url           = require "socket.url"
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

local exit_thread = {}

local host  = configuration.server.host
local port  = configuration.server.port

-- HTTP
-- ====

local Context = {}

Context.__index = Context

function Context.new (skt)
  return setmetatable ({
    raw_skt = skt,
    skt     = copas.wrap (skt),
    request  = {
      protocol   = nil,
      method     = nil,
      resource   = nil,
      headers    = {},
      parameters = {},
      body       = nil,
    },
    response = {
      code    = nil,
      message = nil,
      reason  = nil,
      headers = {},
      body    = nil,
    },
  }, Context)
end

function Context:receive ()
  local skt        = self.skt
  local request    = self.request
  local firstline  = skt:receive "*l"
  -- Extract method:
  local method, query, protocol = firstline:match "^(%a+)%s+(%S+)%s+(%S+)"
  if not method or not query or not protocol then
    error {
      code    = 400,
      message = "Bad Request",
    }
  end
  request.protocol = protocol
  request.method   = method:lower ()
  local parsed     = url.parse (query)
  request.resource = url.parse_path (parsed.path)
  -- Extract headers:
  local headers    = request.headers
  while true do
    local line = skt:receive "*l"
    if line == "" then
      break
    end
    local name, value = line:match "([^:]+):%s*(.*)"
    name  = name:trim ():lower ():gsub ("-", "_")
    value = value:trim ()
    headers [name] = value -- FIXME: very slow, why?
  end
  -- Extract body:
  local length  = headers.content_length
  local chunked = (headers.transfer_encoding or ""):lower () == "chunked"
  if length then
    request.body = skt:receive (tonumber (length)):trim ()
  elseif chunked then
    local body = ""
    repeat
      local size = tonumber (skt:receive "*l")
      body = body .. skt:receive (size)
    until size == 0
  end
  -- Extract parameters:
  local parameters = request.parameters
  local params = parsed.query or ""
  if request.method == "post" then
    params = params .. "&" .. request.body
  end
  for p in params:gmatch "([^;&]+)" do
    local k, v = p:match "([^=]+)=(.*)"
    k = url.unescape (k):gsub ("+", " ")
    v = url.unescape (v):gsub ("+", " ")
    parameters [k] = v
  end
end

function Context:send ()
  local skt       = self.skt
  local request   = self.request
  local response  = self.response
  local headers   = response.headers
  local body      = response.body
  assert (response.code)
  -- Send prefix:
  local firstline = "${protocol} ${code} ${message}\r\n" % {
    protocol = request.protocol,
    code     = response.code,
    message  = response.message,
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
    response.headers.transfer_encoding = "chunked"
  else
    assert (false)
  end
  -- Send headers:
  local close = false
  if request.protocol == "HTTP/1.0" then
    close = true
  end
  local connection = (request.headers.connection or ""):lower ()
  if connection == "keep-alive" then
    response.headers.connection = "keep-alive"
    close = false
  elseif connection == "close" then
    response.headers.connection = "close"
    close = true
  end
  local to_send = {}
  for k, v in pairs (headers) do
    to_send [#to_send + 1] = "${name}: ${value}" % {
      name  = k:gsub ("_", "-"),
      value = v,
    }
  end
  to_send [#to_send + 1] = ""
  -- Send body:
  if type (body) == "string" then
    to_send [#to_send + 1] = body
    skt:send (table.concat (to_send, "\r\n"))
  elseif type (body) == "function" then
    skt:send (table.concat (to_send, "\r\n"))
    for s in body () do
      local data = tostring (s)
      skt:send ("${size}\r\n${data}" % {
        size = #data,
        data = data,
      })
    end
  end
  -- Close if required:
  if close then
    error (exit_thread)
  end
end

-- Authentication
-- ==============
--
-- TODO: use JSON Web Tokens

local identities = setmetatable ({}, { __mode = "kv" })

function Context:identify ()
  local request = self.request
  if request.headers.authorization then
    local encoded = request.headers.authorization:match "%s*Basic (.+)%s*"
    local decoded = base64.decode (encoded)
    local username, password = decoded:match "(%w+):(.*)"
    -- Check validity:
    local cached = identities [username]
    if not cached or cached ~= password then
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

-- Request
-- =======

function Context:answer ()
  local request = self.request
  local r       = resource (self)
  for _, k in ipairs (request.resource) do
    r = r [k]
    if r == nil then
      error {
        code    = 404,
        message = "Not Found",
      }
    end
  end
  local method = r [request.method]
  if not method then
    error {
      code    = 405,
      message = "Method Not Allowed",
    }
  end
  return method (r, self)
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
  -- TODO
  return true
end

-- HTTP Handler
-- ============

local function handler (skt)
  while true do
    local context = Context.new (skt)
    local function perform ()
      local ok, r = pcall (function ()
        context:receive   ()
        context:identify  ()
        if not context:websocket () then
          context:answer ()
        end
      end)
      if not ok then
--        print ("Error:", r)
        context.response.code    = r.code
        context.response.message = (r.message or "")
        context.response.body    = (r.reason  or "") .. "\r\n"
      end
      context:send ()
    end
    local ok, err = pcall (perform)
    if not ok then
      if err ~= exit_thread then
--        print ("Error:", err)
        context.response.code    = 500
        context.response.message = "Internal Server Error"
        context.response.headers.connection = "close"
        context:send ()
      end
      context.raw_skt:shutdown ()
      break
    end
  end
end

copas.addserver (socket.bind (host, port), handler)
copas.loop ()
