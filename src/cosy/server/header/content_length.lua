local Content_Length = {}

function Content_Length.request (context)
  local request = context.request
  local length  = tonumber (request.headers.content_length)
  local skt     = context.skt
  request.body  = skt:receive (length):trim ()
end

function Content_Length.response ()
end

return Content_Length
