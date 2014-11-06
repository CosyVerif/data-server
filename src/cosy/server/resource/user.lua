local configuration = require "cosy.server.configuration"
local bcrypt        = require "bcrypt"

local rounds = configuration.server.password_rounds

local function generate_key ()
  local run = io.popen ("uuidgen", "r")
  local result = run:read ("*all")
  run:close ()
  return result
end

local User = {}

function User.create (t)
  return {
    type           = "user",
    username       = t.username,
    password       = bcrypt.digest (t.password, rounds),
    fullname       = t.fullname,
    email          = t.email,
    validation_key = generate_key (),
    is_active      = configuration.defaults.active,
    is_public      = configuration.defaults.user:lower () == "public",
  }
end

function User:is_owner (context)
  return self.username == context.username
end

function User:can_read (context)
  return self.is_public
end

function User:can_write (context)
  return self.username == context.username
end

--local haricot = require "haricot"
--local bs = haricot.new("localhost", 11300)

return User
