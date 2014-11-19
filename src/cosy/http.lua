local configuration = require "cosy.server.configuration"
local url   = require "socket.url"
local mime  = require "mime"
local lfs   = require "lfs"

local base64 = {}
base64.encode = mime.b64
base64.decode = mime.unb64

local Http = {}

local logger = configuration.logger
do
  -- Load all HTTP header classes:
  local headers = {}
  for path in package.path:gmatch "([^;]+)" do
    if path:sub (-5) == "?.lua" then
      path = path:sub (1, #path - 5) .. "cosy/http/header/"
      if lfs.attributes (path, "mode") == "directory" then
        for file in lfs.dir (path) do
          if lfs.attributes (path .. file, "mode") == "file"
          and file:sub (1,1) ~= "." then
            local name   = file:gsub (".lua", "")
            local header = require ("cosy.http.header." .. name)
            headers [header] = true
          end
        end
      end
    end
  end
  -- Sort HTTP headers:
  local sorted = {}
  for header in pairs (headers) do
    sorted [#sorted + 1] = header
    logger:info ("Loaded header: " .. tostring (header))
  end
  table.sort (sorted)
  Http.headers = sorted
end

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
  local request     = context.request
  local response    = context.response
  request.protocol  = protocol
  request.method    = method:upper ()
  request.query     = query
  local parsed      = url.parse (query)
  request.resource  = url.parse_path (parsed.path)
  response.protocol = protocol
  -- Extract headers:
  local headers     = request.headers
  while true do
    local line = skt:receive "*l"
    if line == "" then
      break
    end
    local name, value = line:match "([^:]+):%s*(.*)"
    name  = name:to_identifier ()
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
  -- Parse headers:
  for _, h in ipairs (Http.headers) do
    h:parse (context)
  end
  -- Handle headers:
  for _, h in ipairs (Http.headers) do
    h:request (context)
  end
end

function Http.response (context)
  local skt       = context.skt
  local response  = context.response
  local headers   = response.headers
  local body      = response.body
  assert (response.code)
  -- Handle headers:
  for _, h in ipairs (Http.headers) do
    h:response (context)
  end
  -- Write headers:
  for _, h in ipairs (Http.headers) do
    h:write (context)
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
  print (table.concat (to_send, "\n"))
  print (body)
  if body == nil then
    skt:send (table.concat (to_send, "\r\n"))
  elseif type (body) == "string" then
    to_send [#to_send + 1] = body
    skt:send (table.concat (to_send, "\r\n"))
  elseif type (body) == "function" then
    skt:send (table.concat (to_send, "\r\n"))
    skt:send "\r\n"
    for s in body do
      local data = tostring (s)
      skt:send ("${size}\r\n${data}" % {
        size = #data,
        data = data,
      })
    end
    skt:send "0\r\n"
  end
  -- Write trailer:
  for _, h in ipairs (Http.headers) do
    h:trailer (context)
    for key, value in pairs (response.headers) do
      if value then
        skt:send ("${key}: ${value}\r\n" % {
          key   = key,
          value = value,
        })
      end
    end
  end
  skt:send "\r\n"
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
