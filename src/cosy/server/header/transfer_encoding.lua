local Transfer_Encoding = {}

function Transfer_Encoding.request (context)
  local request = context.request
  local value   = request.headers.transfer_encoding
  local skt     = context.skt
  if value:lower () == "chunked" then
    local body = ""
    repeat
      local size = tonumber (skt:receive "*l")
      body = body .. skt:receive (size)
    until size == 0
    request.body = body
  end
end

return Transfer_Encoding
