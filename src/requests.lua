local http  = require "socket.http"
local ltn12 = require "ltn12"
local json  = require "cjson"

do
  local result = {}
  local body, code, h = http.request {
    url    = "http://127.0.0.1:8080/",
    method = "GET",
    headers = {
      ["Accept"] = "*/*",
--      ["Content-Type"] = "application/json",
    },
    sink   = ltn12.sink.table (result),
    source = ltn12.source.string (json.encode {
      -- TODO
    }),
    redirect = true,
  }
  print (code, body)
  for k, v in pairs (h) do
    print (k, v)
  end
  print (table.concat (result))
  assert (code == 200)
  body = json.decode (body)
  -- TODO
end


