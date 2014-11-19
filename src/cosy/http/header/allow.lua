local Header = require "cosy.http.header"

local Allow = Header.class ("Allow", {
  Header.Tokens,
})

return Allow
