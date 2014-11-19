local Header = require "cosy.http.header"

local Accept_Encoding = Header.class ("Accept-Encoding", {
  Header.Tokens,
  Header.Parameterized,
  Header.Normalized,
  Header.Sorted,
})

function Accept_Encoding:request (context)
  local headers = context.request.headers
  if not headers.accept_encoding then
    headers.accept_encoding = {
      {
        token      = "identity",
        parameters = {},
      },
    }
  end
end

function Accept_Encoding:response (context)
  local headers = context.response.headers
  assert (not headers.accept_encoding)
end

return Accept_Encoding
