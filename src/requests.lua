local configuration = require "cosy.server.configuration"
local _             = require "cosy.util.string"

local http  = require "socket.http"
local ltn12 = require "ltn12"
local json  = require "cjson"

-- GET /
do
  print "GET /"
  local result = {}
  local body, code, h = http.request {
    url    = "http://${host}:${port}/" % {
      host = configuration.server.host,
      port = configuration.server.port,
    },
    method = "GET",
    headers = {
      ["Accept"] = "*/*",
    },
    sink   = ltn12.sink.table (result),
    redirect = true,
  }
  print (code, body)
  for k, v in pairs (h) do
    print (k, v)
  end
  print (table.concat (result))
  assert (code == 200)
end

-- POST / user
do
  print "==== POST / alinard"
  local data = json.encode {
    type           = "user",
    identifier     = "alinard",
    password       = "toto",
    fullname       = "Alban Linard",
    email          = "alban.linard@lsv.ens-cachan.fr",
  }
  local result = {}
  local body, code, h = http.request {
    url    = "http://${host}:${port}/" % {
      host = configuration.server.host,
      port = configuration.server.port,
    },
    method = "POST",
    headers = {
      ["Accept"] = "*/*",
      ["Content-Type"] = "application/json",
    },
    sink   = ltn12.sink.table (result),
    source = ltn12.source.string (data),
    redirect = true,
  }
  print (code, body)
  for k, v in pairs (h) do
    print (k, v)
  end
  print (table.concat (result))
  assert (code == 201)
end

-- DELETE / user
do
  print "DELETE /alinard"
  local result = {}
  local body, code, h = http.request {
    url    = "http://${host}:${port}/alinard" % {
      host = configuration.server.host,
      port = configuration.server.port,
    },
    method = "DELETE",
    headers = {},
    sink   = ltn12.sink.table (result),
    redirect = true,
  }
  print (code, body)
  for k, v in pairs (h) do
    print (k, v)
  end
  print (table.concat (result))
  assert (code == 201)
end


