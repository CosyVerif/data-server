local configuration = require "cosy.server.configuration"
local _             = require "cosy.util.string"
local copas         = require "copas"
local socket        = require "socket"
--local iconv         = require "iconv"
--local utf8          = require "utf8"
--local ssl           = require "ssl"

local host  = configuration.server.host
local port  = configuration.server.port

-- Query Handler
-- =============

local Http         = require "cosy.server.middleware.http"
local Content_Type = require "cosy.server.middleware.content_type"
local Perform      = require "cosy.server.middleware.perform"

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
      Content_Type,
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
        if o.request then
          o.request (context)
        end
        perform (i+1)
        if o.response then
          o.response (context)
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
