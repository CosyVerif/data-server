local configuration = require "cosy.server.configuration"

local Root = {}

function Root.create ()
  return {
    type      = "root",
    is_public = configuration.defaults.root:lower () == "public",
  }
end

function Root:is_owner (context)
  return false
end

function Root:can_read (context)
  return self.is_public
end

function Root:can_write (context)
  return context.username ~= nil
end

function Root:get (context)
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
  context.response.code    = 200
  context.response.message = "OK"
  return result
end

return Root
