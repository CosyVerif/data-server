local configuration = require "cosy.server.configuration"
local _             = require "cosy.util.string"
local turboredis    = require "turboredis"
local turbo         = configuration.turbo
local log           = turbo.log

log.notice ("Adding redis to configuration...")
local host       = configuration.redis.host
local port       = configuration.redis.port
local client     = turboredis.Connection:new (host, port)
local subscriber = turboredis.PubSubConnection:new (host, port)

local Client     = {}
local Subscriber = {}

local function generate (target, source, names)
  for _, name in ipairs (names) do
    name = name:lower ():gsub (" ", "_")
    if not source [name] then
      log.warning ("Command ${name} is not supported by Redis client." % {
        name = name
      })
    else
      target [name] = function (self, ...)
        return coroutine.yield (source [name] (source, ...))
      end
    end
  end
end
generate (Client, client, turboredis.COMMANDS)
generate (Client, client, { "connect" })
generate (Subscriber, subscriber, turboredis.PUBSUB_COMMANDS)
generate (Subscriber, subscriber, { "connect", "start" })

configuration.loop:add_callback (function ()
  if Client:connect () then
    configuration.redis.client = Client
    log.success ("Connected to Redis at ${host}:${port}." % {
      host = host,
      port = port,
    })
  else
    log.error ("Cannot connect to Redis at ${host}:${port}!" % {
      host = host,
      port = port,
    })
  end
  if Subscriber:connect () then
    configuration.redis.subscriber = Subscriber
    log.success ("Connected to Redis Pub/Sub at ${host}:${port}." % {
      host = host,
      port = port,
    })
  else
    log.error ("Cannot connect to Redis Pub/Sub at ${host}:${port}!" % {
      host = host,
      port = port,
    })
  end
  configuration.loop:close ()
end)

configuration.loop:start ()
