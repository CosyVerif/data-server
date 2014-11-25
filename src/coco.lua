-- Coroutine
-- =========

function coroutine.wrap (f, t)
  local co = coroutine.create (f)
  return function ()
    local result = { table.pack (coroutine.resume (co)) }
    local status = result [1]
    if not status then
      error (result [2])
    end
    local tag = result [2]
    table.remove (result, 1)
    if tag == t then
      table.remove (result, 1)
      return table.unpack (result)
    elseif tag == nil then
      return
    else
      local _, main = coroutine.running ()
      if main then
        error "Yield is caught by no wrap."
      else
        coroutine.yield (table.unpack (result))
      end
    end
  end
end

-- CoCo
-- ====

local socket = require "socket"

local coco = {}

coco._COROUTINE_TAG = {}

coco.queue = {}

-- Each element has the following form:
-- {
--   co     = coroutine,
--   wakeup = number or inf or nil,
--   
-- }
--
-- Coroutines are sorted by wakeup time: -number < nil < number < nan

function coco.addthread (f, ...)
  local queue = coco.queue
  queue [#queue + 1] = { co = coroutine.create (f), ps = table.pack (...) }
end

function coco.addserver (skt, handler, timeout)
  skt:settimeout (timeout or 0)
  coco.addthread (function ()
    while true do
      local result, err = skt:accept ()
      if not result and err ~= "timeout" then
        error (err)
      elseif result then
        coco.addthread (function (skt)
          handler (skt)
          skt:close ()
        end, coco.wrap (result))
      end
      if #(coco.queue) == 1 then
        socket.sleep (0.001)
      end
      coco.yield ()
    end
  end)
end

function coco.yield (...)
  coroutine.yield (coco._COROUTINE_TAG, ...)
end

function coco.loop ()
  local i     = 1
  local queue = coco.queue
  while #queue ~= 0 do
    local current = queue [i]
    local result
    if type (current) == "table" then
      result    = { coroutine.resume (current.co, table.unpack (current.ps)) }
      current   = current.co
      queue [i] = current
    else
      result = { coroutine.resume (current) }
    end
    local status = result [1]
    if status then
      local tag = result [2]
      if coroutine.status (current) == "dead" then
        table.remove (queue, i)
      elseif tag == coco._COROUTINE_TAG then
        i = i + 1
      else
        table.remove (result, 1)
        table.remove (result, 1)
        coroutine.yield (tag, table.unpack (result))
        i = i + 1
      end
    else
      print ("Error: ", result [2])
      table.remove (queue, i)
    end
    i = i > #queue and 1 or i
  end
end

function coco.connect (skt, host, port)
  skt:settimeout(0)
  repeat
    local ret, err = skt:connect (host, port)
    if ret or err ~= "timeout" then
      return ret, err
    end
    coco.yield ()
  until false
end

function coco.settimeout (client, t)
  client:settimeout (t)
end

function coco.receive (client, pattern)
  pattern = pattern or "*l"
  repeat
    local s, err = client:receive(pattern)
    if s or err ~= "timeout" then
      return s, err
    end
    coco.yield ()
  until false
end

function coco.send (client, data, from, to)
  from = from or 1
  local s, err
  local last = from - 1
  repeat
    s, err, last = client:send (data, last + 1, to)
    if math.random (100) > 90 then
      coco.yield ()
    end
    if s or err ~= "timeout" then
      return s, err, last
    end
    coco.yield ()
  until false
end

function coco.flush ()
end

function coco.setoption (client, option, value)
  client:setoption (option, value)
end

function coco.close (client)
  client:close ()
end

local Socket = {}

Socket.__index = Socket

function Socket:connect (host, port)
  return coco.connect (self.skt, host, port)
end

function Socket:settimeout (t)
  return coco.settimeout (self.skt, t)
end

function Socket:receive (pattern)
  return coco.receive (self.skt, pattern)
end

function Socket:send (data, from, to)
  return coco.send (self.skt, data, from, to)
end

function Socket:flush ()
  return coco.flush (self.skt)
end

function Socket:setoption (option, value)
  return coco.setoption (self.skt, option, value)
end

function Socket:close ()
  return coco.close (self.skt)
end

function coco.wrap (skt)
  return setmetatable ({
    skt = skt
  }, Socket)
end




-- Test
-- ====
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

coco.addserver (skt, function (skt)
  local lines = {}
  while true do
    lines [#lines + 1] = skt:receive "*l"
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
  coco.addthread (function (n)
    for _ = 1, n do
      coco.yield ()
    end
  end, 10)
end
--]]

local start  = socket.gettime ()
coco.loop ()
local finish = socket.gettime ()

print ("Time"  , format ((finish - start) * 1000, "ms"))
print ("Memory", format (collectgarbage "count", "kb"))

if profiler then
  profiler:stop()
  profiler:writeReport "profiler.txt"
end
