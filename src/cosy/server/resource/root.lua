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

function Root:can_write (context)
  local _ = self
  return context.username ~= nil
end

function Root:GET (context)
  local result = {}
  for k, v in pairs (self) do
    if type (v) == "table" and v.type then
      result [#result + 1] = {
        identifier = k,
        is_owner   = v:is_owner  (context),
        can_read   = v:can_read  (context),
        can_write  = v:can_write (context),
      }
    end
  end
  return result
end

function Root:POST (context)
  local request    = context.request
  local response   = context.response
  local parameters = request.body
  local r_type     = parameters.type
  local Resource   = require ("cosy.server.resource." .. r_type)
  local result     = Resource.create (parameters)
  self [result.identifier] = result
  response.body    = result
  response.code    = 201
  response.message = "Created"
end

return Root
