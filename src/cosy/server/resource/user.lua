local configuration = require "cosy.server.configuration"
local resources     = require "cosy.server.resources"
local bcrypt        = require "bcrypt"
local copas = require "copas.timer"

local rounds = configuration.server.password_rounds

local function generate_key ()
  local run = io.popen ("uuidgen", "r")
  local result = run:read ("*all")
  run:close ()
  return result
end

local User = resources ()

function User:__check (t)
  local errors = {}
  -- TODO
  if #errors ~= 0 then
    return nil, errors
  end
  return true
end

function User:new (t)
  local resource = "${root}/users/${user}" % {
    root = configuration.server.root,
    user = t.username,
  }
  User [resource] = {
    username       = t.username,
--    password       = bcrypt.digest (t.password, rounds),
    fullname       = t.fullname,
    email          = t.email,
    validation_key = generate_key (),
    is_active      = false,
    is_public      = true,
  }
--local haricot = require "haricot"
--local bs = haricot.new("localhost", 11300)
  return User [resource]
end

-- Test:
copas.addthread (function ()
  local redis = configuration.redis.client
  redis:flushall ()
  print "Before add."
  for i = 1, 1000 do
    print (i)
    User:new {
      username = "user-${n}" % { n = i },
      password = "toto",
      fullname = "User ${n}" % { n = i },
      email    = "user.${n}@gmail.com" % { n = i },
    }
    if i % 10 == 0 then
      print (i)
    end
  end
  print "After add."
end)

return User
