local mime          = require "mime"
local crypto        = require "crypto"

local base64 = {}
base64.encode = mime.b64
base64.decode = mime.unb64

local sha1 = function (s)
  return crypto.digest ("sha1", s, true)
end

local ws_guid = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11"

return function (context, value)
  local request = context.request
  local headers = request.headers
  if not headers.connection or headers.connection:lower () ~= "upgrade"
  or value ~= "websocket" then
    error {
      code    = 405,
      message = "Method Not Allowed",
    }
  end
  local version   = headers.sec_websocket_version
  if version ~= "13" then
    error {
      code    = 412,
      message = "Precondition Failed",
      reason  = "websocket protocol must be '13'",
    }
  end
  local protocols = headers.sec_websocket_protocol
  if protocols ~= "cosy" then
    error {
      code    = 412,
      message = "Precondition Failed",
      reason  = "protocol must be 'cosy'",
    }
  end
  local key       = headers.sec_websocket_key
  if not key then
    error {
      code    = 400,
      message = "Bad Request",
      reason  = "header Sec-WebSocket-Key is missing",
    }
  end
  context.response = {
    code    = 101,
    message = "Switching Protocols",
    headers = {
      upgrade    = "websocket",
      connection = "upgrade",
      sec_websocket_accept   = base64.encode (sha1 (key .. ws_guid)),
      sec_websocket_protocol = "cosy",
    },
  }
  context:send ()
  -- TODO
end
