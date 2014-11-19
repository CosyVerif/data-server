local Header = require "cosy.http.header"

local Content_Length = Header.class ("Content-Length", {
  Header.Integer,
})

function Content_Length.request (context)
  local skt     = context.skt
  request.body  = skt:receive (request.headers.content_length):trim ()
end

return Content_Length
