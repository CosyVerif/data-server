local configuration = require "cosy.server.configuration"
local resource      = require "cosy.server.resource"
local _             = require "cosy.util.string"
local copas         = require "copas"
local socket        = require "socket"
local url           = require "socket.url"
local mime          = require "mime"
local json          = require "cjson"
local iconv         = require "iconv"
local utf8          = require "utf8"
--local ssl           = require "ssl"

local base64 = {}
base64.encode = mime.b64
base64.decode = mime.unb64

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
  request.query    = query
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
    headers [name] = value
  end
end

function Context:extract_parameters ()
  -- Extract parameters:
  local request    = self.request
  local parameters = request.parameters
  local parsed     = url.parse (request.query)
  local params     = parsed.query or ""
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
    body = json.encode (body) -- FIXME: convert to the correct format
    response.headers.content_length = #(body)
  elseif type (body) == "function" then
    response.headers.transfer_encoding = "chunked"
  else
    assert (false)
  end
  -- Send headers:
  if not headers.connection then
    if request.protocol == "HTTP/1.0" then
      headers.connection = "close"
    elseif request.protocol == "HTTP/1.1" then
      headers.connection = "keep-alive"
    else
      assert (false)
    end
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
  if headers.connection == "close" then
    error (exit_thread)
  end
end

function Context:handle_headers ()
  local headers = self.request.headers
  for k, v in pairs (headers) do
    local ok, handler = pcall (require, "cosy.server.header." .. k)
    if not ok then
      error {
        code    = 412,
        message = "Precondition Failed",
        reason  = "unknown header: " .. k
      }
    end
    handler (self, v)
  end
end

-- Request
-- =======

function Context:answer ()
  local request  = self.request
  local response = self.response
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
  local result = method (r, self)
  if not response.code then
    response.code = 200
    response.message = "OK"
  end
  return result
end

-- HTTP Handler
-- ============

local function handler (skt)
  while true do
    local context = Context.new (skt)
    local function perform ()
      local ok, r = pcall (function ()
        context:receive ()
        context:handle_headers ()
        context:extract_parameters ()
        context:answer ()
      end)
      if not ok then
        print ("Error:", r)
        context.response.code    = r.code
        context.response.message = (r.message or "")
        context.response.body    = (r.reason  or "") .. "\r\n"
      end
      context:send ()
    end
    local ok, err = pcall (perform)
    if not ok then
      if err ~= exit_thread then
        print ("Error:", err)
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
