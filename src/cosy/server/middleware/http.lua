local url           = require "socket.url"
local mime          = require "mime"

local base64 = {}
base64.encode = mime.b64
base64.decode = mime.unb64

local Http = {}

function Http.request (context)
  local skt        = context.skt
  local firstline  = skt:receive "*l"
  if firstline == nil then
    context.continue = false
    error (true)
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
  local response   = context.response
  request.protocol = protocol
  request.method   = method:upper ()
  request.query    = query
  local parsed     = url.parse (query)
  request.resource = url.parse_path (parsed.path)
  -- Set default headers depending on protocol:
  local headers     = request.headers
  response.protocol = protocol
  -- Extract headers:
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
  -- Add protocol-specific headers:
  if protocol == "HTTP/1.0" then
    if not headers.connection then
      headers.connection = "close"
    end
  elseif protocol == "HTTP/1.1" then
    if not headers.connection then
      headers.connection = "keep-alive"
    end
    if method == "POST" and
      not headers.content_length and
      not headers.transfer_encoding then
      headers.transfer_encoding = "chunked"
    end
  else
    assert (false)
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
  local handled     = {}
  local handlers    = {}
  local nb_handlers = 0
  for k in pairs (headers) do
    local ok, handler = pcall (require, "cosy.server.header." .. k)
    if not ok then
      error {
        code    = 412,
        message = "Precondition Failed",
        reason  = "unknown header: " .. k
      }
    end
    if not handler.request then
      error ("No request for " .. k)
    end
    handlers [k] = handler
    nb_handlers = nb_handlers + 1
  end
  local count = 0
  while count ~= nb_handlers do
    for k, handler in pairs (handlers) do
      if not handled [k] then
        local missing = false
        for _, k in ipairs (handler.depends or {}) do
          k = k:lower ():gsub ("-", "_")
          if headers [k] and not handled [k] then
            missing = true
            break
          end
        end
        if not missing then
          handler.request (context)
          handled [k] = true
          count       = count + 1
        end
      end
    end
  end
end

function Http.response (context)
  local skt       = context.skt
  local response  = context.response
  local headers   = response.headers
  local body      = response.body
  assert (response.code)
  -- Set Content-Length or Transfer-Encoding:
  if body == nil then
    response.headers.content_length = 0
  elseif type (body) == "string"   then
    response.headers.content_length = #(body)
  elseif type (body) == "function" then
    response.headers.transfer_encoding = "chunked"
  else
    print (type (body), body)
    assert (false)
  end
  -- Handle headers:
  -- FIXME: use the same as in request
  for k in pairs (headers) do
    local ok, handler = pcall (require, "cosy.server.header." .. k)
    if not ok then
      error {
        code    = 412,
        message = "Precondition Failed",
        reason  = "unknown header: " .. k
      }
    end
    if not handler.response then
      error ("No response for " .. k)
    end
    handler.response (context)
  end
  -- Send response:
  local to_send   = {}
  to_send [1] = "${protocol} ${code} ${message}" % {
    protocol = response.protocol,
    code     = response.code,
    message  = response.message,
  }
  for k, v in pairs (headers) do
    to_send [#to_send + 1] = "${name}: ${value}" % {
      name  = k:gsub ("_", "-"),
      value = v,
    }
  end
  to_send [#to_send + 1] = ""
  print (table.concat (to_send, "\r\n"))
  if body == nil then
    skt:send (table.concat (to_send, "\r\n"))
  elseif type (body) == "string"   then
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
end

function Http.error (context, err)
  if type (err) == "table" and err.code then
    context.response.code    = err.code
    context.response.message = err.message or ""
    context.response.body    = (err.reason  or "") .. "\r\r"
    Http.response (context)
  else
    context.continue = false
    context.response.code = 500
    context.response.message = "Internal Server Error"
    context.response.headers.connection = { close = true }
    Http.response (context)
    error (err)
  end
end

return Http
