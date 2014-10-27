_G.TURBO_SSL = true

local turbo = require "turbo"
local yaml  = require "yaml"
local _     = require "cosy.util.string"
local log   = turbo.log

local paths = {
  "/etc/cosy.yaml",
  os.getenv "HOME" .. "/.cosy/cosy.yaml",
  os.getenv "PWD"  .. "/.cosy.yaml",
}

local function import (source, target)
  assert (type (source) == "table")
  assert (type (target) == "table")
  for k, v in pairs (source) do
    if type (v) == "table" then
      if target [k] == nil then
        target [k] = v
      else
        import (v, target [k])
      end
    else
      target [k] = v
    end
  end
end

local configuration = {}
for _, filename in ipairs (paths) do
  local file = io.open (filename, "r")
  if file then
    log.notice ("Reading configuration file ${filename}..." % { filename = filename })
    local text = file:read ("*all")
    import (yaml.load (text), configuration)
    file:close()
  end
end
log.success ("Loaded configuration.")
log.devel (yaml.dump (configuration))

log.notice ("Adding turbo to configuration...")
configuration.turbo = turbo
configuration.loop  = turbo.ioloop.instance ()

configuration.resources = setmetatable ({}, {
  __mode = "kv"
})
log.success ("Created resources cache.")

return configuration
