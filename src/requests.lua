local http  = require "socket.http"
local ltn12 = require "ltn12"
local json  = require "cjson"

local body, code = http.request {
  url    = "http://127.0.0.1:8080/",
  method = "GET",
  headers = {
    -- TODO
  },
  source = ltn12.source.string (json.encode {
    -- TODO
  }),
  redirect = true,
}


