local resource = require "cosy.server.resource"

local Perform = {}

function Perform.request (context)
  local request  = context.request
  local response = context.response
  local r        = resource (context)
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
  response.body = method (r, context)
  if not response.code then
    response.code = 200
    response.message = "OK"
  end
end

return Perform
