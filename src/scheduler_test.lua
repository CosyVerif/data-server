local Scheduler = require "cosy.server.scheduler"
local socket    = require "socket"

local function format (n, unit)
  local formatted = tostring (math.ceil (n))
  local k
  while true do
    formatted, k = formatted:gsub ("^(-?%d+)(%d%d%d)", '%1,%2')
    if k==0 then break end
  end
  return formatted .. " " .. unit
end

local profiler = false

if profiler then
  profiler = require "profiler"
  profiler:start()
end

local skt = socket.bind ("127.0.0.1", 8080)

local scheduler = Scheduler.create ()

scheduler:addserver (skt, function (skt)
  local lines = {}
  while true do
    local line = skt:receive "*l"
    if line:find ("DELETE") then
      print "Stopping scheduler"
      scheduler:stop ()
    end
    lines [#lines + 1] = line
    scheduler:pass ()
    if lines [#lines] == "" then
      skt:send [[HTTP/1.0 200 OK
Connection: close
]]
      return
    end
  end
end)

--[[
for _ = 1, 1000 do
  scheduler:addthread (function (n)
    for _ = 1, n do
      scheduler.coroutine.yield ()
    end
  end, 10)
end
--]]

local start  = socket.gettime ()
scheduler:loop ()
local finish = socket.gettime ()

print ("Time"  , format ((finish - start) * 1000, "ms"))
print ("Memory", format (collectgarbage "count", "kb"))

if profiler then
  profiler:stop()
  profiler:writeReport "profiler.txt"
end
