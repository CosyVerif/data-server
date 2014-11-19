local Header = require "cosy.http.header"

local Accept_Language = Header.class ("Accept-Language", {
  Header.Tokens,
  Header.Parameterized,
  Header.Normalized,
  Header.Sorted,
  Header.Language,
})

function Accept_Language:request (context)
  local headers = context.request.headers
  if not headers.accept_language then
    headers.accept_language = {
      {
        primary    = "*",
        parameters = {},
      },
    }
  end
end

function Accept_Language:response (context)
  local headers = context.response.headers
  assert (not headers.accept_language)
end

return Accept_Language
