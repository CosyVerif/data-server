local resource = require "cosy.server.resource"
local mime     = require "mime"
local Header   = require "cosy.http.header"
local configuration = require "cosy.server.configuration"

local base64 = {}
base64.encode = mime.b64
base64.decode = mime.unb64

local identities = setmetatable ({}, { __mode = "kv" })

local Authorization = Header.class "Authorization"

function Authorization:request (context)
  local headers = context.request.headers
  local value   = headers.authorization
  local encoded = value:match "%s*Basic (.+)%s*"
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

function Authorization:response (context)
  local headers = context.request.headers
  if context.response.code == 401 then
    headers.www_authenticate = [[Basic realm="${realm}"]] % {
      realm = configuration.server.root,
    }
  end
end

return Authorization
