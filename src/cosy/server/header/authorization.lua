local resource = require "cosy.server.resource"
local mime     = require "mime"

local base64 = {}
base64.encode = mime.b64
base64.decode = mime.unb64

local identities = setmetatable ({}, { __mode = "kv" })

return function (context, authorization)
  local encoded = authorization:match "%s*Basic (.+)%s*"
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
  context.username = username
  context.password = password
end
