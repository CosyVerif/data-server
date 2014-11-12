local url           = require "socket.url"
local mime          = require "mime"
local json          = require "cjson"

local base64 = {}
base64.encode = mime.b64
base64.decode = mime.unb64

local Http = {}

function Http.pre (context)
  local skt        = context.skt
  local firstline  = skt:receive "*l"
  if firstline == nil then
    context.continue = false
    return
  end
  -- Extract method:
  local method, query, protocol = firstline:match "^(%a+)%s+(%S+)%s+(%S+)"
  if not method or not query or not protocol then
    error {
      code    = 400,
      message = "Bad Request",
    }
  end
  local request    = context.request
  request.protocol = protocol
  request.method   = method:upper ()
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
  -- Extract parameters:
  local parameters = request.parameters
  local params     = parsed.query or ""
  for p in params:gmatch "([^;&]+)" do
    local k, v = p:match "([^=]+)=(.*)"
    k = url.unescape (k):gsub ("+", " ")
    v = url.unescape (v):gsub ("+", " ")
    parameters [k] = v
  end
  -- Handle headers:
  for k, v in pairs (headers) do
    local ok, handler = pcall (require, "cosy.server.header." .. k)
    if not ok then
      error {
        code    = 412,
        message = "Precondition Failed",
        reason  = "unknown header: " .. k
      }
    end
    handler (context, v)
  end
end

function Http.post (context)
  local skt       = context.skt
  local request   = context.request
  local response  = context.response
  local headers   = response.headers
  local body      = response.body
  local to_send   = {}
  assert (response.code)
  -- Send prefix:
  response.protocol = request.protocol
  to_send [1] = "${protocol} ${code} ${message}" % {
    protocol = response.protocol,
    code     = response.code,
    message  = response.message,
  }
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
    if response.protocol == "HTTP/1.0" then
      headers.connection = "close"
    elseif response.protocol == "HTTP/1.1" then
      headers.connection = "keep-alive"
    else
      assert (false)
    end
  end
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
    context.continue = false
  end
end

function Http.error (context, err)
  print (err)
  if type (err) == "table" and err.code then
    context.response.code    = err.code
    context.response.message = (err.message or "")
    context.response.body    = (err.reason  or "") .. "\r\n"
    Http.post (context)
  else
    context.continue         = false
    context.response.code    = 500
    context.response.message = "Internal Server Error"
    context.response.headers.connection = "close"
    Http.post (context)
    error (err)
  end
end

return Http
