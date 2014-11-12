return function (context, value)
  local skt     = context.skt
  local request = context.request
  if value:lower () == "chunked" then
    local body = ""
    repeat
      local size = tonumber (skt:receive "*l")
      body = body .. skt:receive (size)
    until size == 0
    request.body = body
  end
end
