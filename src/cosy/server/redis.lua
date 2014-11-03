local configuration = require "cosy.server.configuration"
                      require "cosy.util.string"
local redis         = require "redis"

local logger = configuration.logger
local host   = configuration.redis.host
local port   = configuration.redis.port

local client = redis.connect ({
  host      = host,
  port      = port,
  use_copas = true,
})

if client then
  logger:info ("Connected to Redis at ${host}:${port}." % {
    host = host,
    port = port,
  })
else
  logger:info ("Cannot connect to Redis at ${host}:${port}!" % {
    host = host,
    port = port,
  })
  error "redis"
end
client:select (configuration.redis.database)
configuration.redis.client = client

local subscriber = redis.connect ({
  host      = host,
  port      = port,
  use_copas = true,
})
if subscriber then
  logger:info ("Connected to Redis for Pub/Sub at ${host}:${port}." % {
    host = host,
    port = port,
  })
else
  logger:info ("Cannot connect to Redis for Pub/Sub at ${host}:${port}!" % {
    host = host,
    port = port,
  })
  error "redis"
end
subscriber:select (configuration.redis.database)
configuration.redis.subscriber = subscriber
