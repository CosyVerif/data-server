local Header = require "cosy.http.header"

local Connection = Header.class ("Connection", {
  Header.Tokens,
  Header.Normalized,
})

function Connection:request (context)
  local request  = context.request
  local response = context.response
  local request_header  = request.headers.connection
  local response_header = {}
  if request_header.close then
    response_header.close = true
  elseif request_header.keep_alive then
    response_header.keep_alive = true
  end
  response.headers.connection = response_header
end

function Connection:response (context)
  local response = context.response
  local header   = response.headers.connection
  if header.close then
    context.continue = false
  elseif header.keep_alive then
    context.continue = true
  end
end

return Connection
