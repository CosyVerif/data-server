local configuration = require "cosy.server.configuration"

local Root = {}

function Root.create ()
  return {
    type      = "root",
    is_public = configuration.defaults.root:lower () == "public",
  }
end

function Root:is_owner ()
  local _ = self
  return false
end

function Root:can_read ()
  return self.is_public
end

function Root:can_write ()
  local _ = self
  return true
end

function Root:GET (context)
  local iter = pairs (self)
  return function ()
    repeat
      local k, v = iter ()
      if k == nil then
        return nil
      elseif v:can_read (context) then
        return {
          key        = k,
          identifier = tostring (v),
          is_owner   = v:is_owner  (context),
          can_read   = v:can_read  (context),
          can_write  = v:can_write (context),
        }
      end
    until false
  end
end

function Root:POST (context)
  local request    = context.request
  local response   = context.response
  local parameters = request.body
  local r_type     = parameters.type
  local Resource   = require ("cosy.server.resource." .. r_type)
  local created    = self + Resource.new (parameters)
  response.body    = result
  response.code    = 201
  response.message = "Created"
end

return Root
