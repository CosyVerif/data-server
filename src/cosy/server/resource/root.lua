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
  
end

return Root
