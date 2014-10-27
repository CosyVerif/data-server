local configuration = require "cosy.server.configuration"
local Resource      = require "cosy.server.resource"
local bcrypt        = require "bcrypt"

local trim      = configuration.turbo.escape.trim
local redis     = configuration.redis.client
local resources = configuration.resources
local loop      = configuration.loop

local function generate_key ()
  local run = io.popen ("uuidgen", "r")
  local result = run:read ("*all")
  run:close ()
  return trim (result)
end

local User = Resource.create ()

function User:check (t)
  local errors = {}
  -- TODO
  if #errors ~= 0 then
    return nil, errors
  end
  return true
end

function User:new (t)
  -- Check if user exists
  local resource = "${root}/users/${user}" % {
    root = configuration.server.root,
    user = t.username,
  }
  -- Create user:
  local digest = bcrypt.digest (t.password, configuration.server.password_rounds)
  return User:create {
    resource       = resource,
    username       = t.username,
    password       = t.password,
    fullname       = t.fullname,
    email          = t.email,
    validation_key = generate_key (),
    is_active      = false,
    is_public      = true,
  }
end

-- Test:
configuration.loop:add_callback (function ()
  redis:flushall ()
  print "Before:"
  for _, k in ipairs (redis:keys "*") do
    print (k)
  end
  print "Adding user:"
  User:new {
    username = "alinard",
    password = "toto",
    fullname = "Alban Linard",
    email    = "alban@linard.fr",
  }
  print "After:"
  for _, k in ipairs (redis:keys "*") do
    print (k)
  end
  print "Load:"
--  collectgarbage ()
  local u = User:load "cosyverif.io/users/alinard"
  print (u.validation_key)
end)

return User
