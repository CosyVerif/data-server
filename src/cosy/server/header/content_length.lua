require "cosy.util.string"

return function (context, length)
  local skt     = context.skt
  local request = context.request
  length        = tonumber (length)
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
