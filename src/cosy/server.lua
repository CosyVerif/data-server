-- Do __not__ load anything before configuration!
local configuration = require "cosy.server.configuration"

-- Load modules:
require "cosy.server.redis"
require "cosy.server.resource.user"


configuration.loop:start ()
