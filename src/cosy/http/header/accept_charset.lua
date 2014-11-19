local Header = require "cosy.http.header"

local Accept_Charset = Header.class ("Accept-Charset", {
  Header.Tokens,
  Header.Parameterized,
  Header.Normalized,
  Header.Sorted,
})

function Accept_Charset:request (context)
  local headers = context.request.headers
  if not headers.accept_charset then
    headers.accept_charset = {
      {
        token      = "*",
        parameters = {},
      },
    }
  end
  local found_any        = false
  local found_iso_8859_1 = false
  for _, x in ipairs (headers.accept_charset) do
    if x.token == "*" then
      found_any = true
    elseif x.token == "iso_8859_1" then
      found_iso_8859_1 = true
    end
  end
  if not found_any and not found_iso_8859_1 then
    table.insert (headers.accept_charset, 1, {
      key   = "iso-8859-1",
      value = {},
    })
  end
end

function Accept_Charset:response (context)
  local headers = context.response.headers
  assert (not headers.accept_charset)
end

return Accept_Charset
