local Transfer_Encoding = {
  depends = {
    "Connection",
    "Content-Length",
    "TE",
  },
}

function Transfer_Encoding.request (context)
  local request = context.request
  local headers = request.headers
  local tokens  = {}
  for word in headers.transfer_encoding:gmatch "([^,%s]+)" do
    tokens [word:lower ():gsub ("-", "_")] = true
  end
  headers.transfer_encoding = tokens
  --
  local skt = context.skt
  if tokens.chunked then
    local body = ""
    repeat
      local line
      while not line or line == "" do
        line = skt:receive "*l"
      end
      local size = tonumber (line, 16)
      body = body .. skt:receive (size)
    until size == 0 or not size
    request.body = body
  end
end

function Transfer_Encoding.response (context)
  context.response.headers.transfer_encoding = nil
end

return Transfer_Encoding
