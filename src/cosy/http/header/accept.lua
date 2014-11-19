local Header = require "cosy.http.header"

local Accept = Header.class ("Accept", {
  Header.Tokens,
  Header.Parameterized,
  Header.Sorted,
  Header.MIME,
})

function Accept:request (context)
  local headers = context.request.headers
  if not headers.accept then
    headers.accept = {
      {
        main       = "*",
        sub        = "*",
        parameters = {},
      },
    }
  end
end

function Accept:response (context)
  local headers = context.response.headers
  assert (not headers.accept)
end

return Accept
