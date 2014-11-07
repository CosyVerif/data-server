local configuration = require "cosy.server.configuration"
local redis         = require "redis"

local host = configuration.redis.host
local port = configuration.redis.port
local db   = configuration.redis.database

-- Redis
-- =====

local Client = {}

function Client:get (main)
  local id = coroutine.running ()
  if not self [id] then
    local result = redis.connect ({
      host      = host,
      port      = port,
      use_copas = not main,
      timeout   = 0.1
    })
    result:select (db)
    self [id] = result
  end
  return self [id]
end

function Client:sub (main)
  local result = redis.connect ({
    host      = host,
    port      = port,
    use_copas = not main,
    timeout   = 0
  })
  result:select (db)
  return result
end

return Client
