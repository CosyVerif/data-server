local configuration = require "cosy.server.configuration"
local resource      = require "cosy.server.resource"
local _             = require "cosy.util.string"
local copas         = require "copas"
local socket        = require "socket"
local mime          = require "mime"
--local iconv         = require "iconv"
--local utf8          = require "utf8"
--local ssl           = require "ssl"

local base64 = {}
base64.encode = mime.b64
base64.decode = mime.unb64

local host  = configuration.server.host
local port  = configuration.server.port

-- Request
-- =======

local Perform = {}

function Perform.call (context)
  local request  = context.request
  local response = context.response
  local r       = resource (context)
  for _, k in ipairs (request.resource) do
    r = r [k]
    if r == nil then
      error {
        code    = 404,
        message = "Not Found",
      }
    end
  end
  local method = r [request.method]
  if not method then
    error {
      code    = 405,
      message = "Method Not Allowed",
    }
  end
  response.body = method (r, context)
  if not response.code then
    response.code = 200
    response.message = "OK"
  end
end

-- Query Handler
-- =============

local Http = require "cosy.server.http"

local function new_context (skt)
  return {
    raw_skt  = skt,
    skt      = copas.wrap (skt),
    continue = true,
    request  = {
      protocol   = nil,
      method     = nil,
      resource   = nil,
      headers    = {},
      parameters = {},
      body       = nil,
    },
    response = {
      protocol = nil,
      code     = nil,
      message  = nil,
      reason   = nil,
      headers  = {},
      body     = nil,
    },
    onion = {
      Http,
      Perform,
    },
  }
end

local function handler (skt)
  local context = new_context (skt)
  while context.continue do
    local onion = context.onion -- it may change!
    local function perform (i)
      if not i then
        i = 1
      end
      local o = onion [i]
      if not o then
        return
      end
      local result, err = pcall (function ()
        if o.pre then
          o.pre (context)
        end
        if o.call then
          o.call (context)
        end
        perform (i+1)
        if o.post then
          o.post (context)
        end
      end)
      if not result then
        if o.error then
          o.error (context, err)
        else
          error (err)
        end
      end
    end
    local ok, err = pcall (perform)
    if not ok then
      print ("Error:", err)
      break
    end
  end
end

-- Onion:
--1. HTTP receive / send
--2. convert format
--3. convert encoding
--#. apply

-- Switching to websocket: replace 1
-- Stack must be part of the context

copas.addserver (socket.bind (host, port), handler)
copas.loop ()
