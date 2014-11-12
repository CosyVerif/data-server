local mime          = require "mime"
local crypto        = require "crypto"

local base64 = {}
base64.encode = mime.b64
base64.decode = mime.unb64

local md5 = function (s)
  return crypto.digest ("md5", s, true)
end

local Content_MD5 = {}

function Content_MD5.request (context)
  local request  = context.request
  local value    = request.headers.content_md5
  local computed = base64.encode (md5 (request.body))
  if computed ~= value then
    error {
      code    = 412,
      message = "Precondition Failed",
      reason  = "Content-MD5 is invalid",
    }
  end
end

return Content_MD5
