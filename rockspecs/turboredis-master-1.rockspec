package = "turboredis"
version = "master-1"

source = {
   url = "git://github.com/enotodden/turboredis",
}

description = {
  summary     = "Redis library for Turbo",
  detailed    = [[
  ]],
  homepage    = "https://github.com/enotodden/turboredis",
  license     = "MIT/X11",
  maintainer  = "enotodden",
}

dependencies = {
  "lua >= 5.1",
}

build = {
  type    = "builtin",
  modules = {
    ["turboredis"] = "turboredis.lua",
  },
}
