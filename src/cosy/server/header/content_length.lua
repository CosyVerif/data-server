require "cosy.util.string"

return function (context, length)
  local skt     = context.skt
  local request = context.request
  length        = tonumber (length)
  request.body  = skt:receive (length):trim ()
end
