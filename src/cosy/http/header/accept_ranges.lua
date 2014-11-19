local Header = require "cosy.http.header"

local Accept_Ranges = Header.class ("Accept-Ranges", {
  Header.Tokens,
  Header.Parameterized,
  Header.Normalized,
})

function Accept_Ranges:request (context)
  local headers = context.request.headers
  assert (not headers.accept_ranges)
end

function Accept_Ranges:response (context)
  local headers = context.response.headers
  if not headers.accept_ranges then
    headers.accept_ranges = {
      none = true,
    }
  end
end

return Accept_Ranges
