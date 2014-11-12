            require "cosy.util.string"
local url = require "socket.url"

local Content_Length = {}

function Content_Length.request (context)
  local request = context.request
  local length  = tonumber (request.headers.length)
  local skt     = context.skt
  request.body  = skt:receive (length):trim ()
  -- Extract parameters:
  if request.method == "POST" then
    local parameters = request.parameters
    for p in request.body:gmatch "([^;&]+)" do
      local k, v = p:match "([^=]+)=(.*)"
      k = url.unescape (k):gsub ("+", " ")
      v = url.unescape (v):gsub ("+", " ")
      parameters [k] = v
    end
  end
end

function Content_Length.response ()
end

return Content_Length
