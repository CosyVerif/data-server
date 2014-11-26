local socket = require "socket"

local scheduler = {}

scheduler._COROUTINE_TAG = {}
scheduler._ITERATOR_TAG  = {}

scheduler.threads = {}
scheduler.last = 0

function scheduler.addthread (f, ...)
  local threads = scheduler.threads
  local i     = #threads + 1
  threads [i] = { co = coroutine.create (f), ps = table.pack (...) }
  scheduler.last = math.max (i, scheduler.last)
end

function scheduler.addserver (skt, handler, timeout)
  skt:settimeout (timeout or 0)
  scheduler.addthread (function ()
    while true do
      local result, err = skt:accept ()
      if not result and err == "timeout" then
        if scheduler.last == 1 then
          socket.sleep (0.01)
        end
        scheduler.yield ()
      elseif not result then
        error (err)
      else
        scheduler.addthread (function (skt)
          pcall (handler, skt)
          skt:close ()
        end, scheduler.wrap (result))
      end
    end
  end)
end

function scheduler.yield (...)
  coroutine.yield (scheduler._COROUTINE_TAG, ...)
end

function scheduler.loop ()
  local i     = 1
  local threads = scheduler.threads
  while #threads ~= 0 do
    local current = threads [i]
    if current ~= nil then
      local result
      if type (current) == "table" then
        result    = { coroutine.resume (current.co, table.unpack (current.ps)) }
        current   = current.co
        threads [i] = current
      else
        result = { coroutine.resume (current) }
      end
      local status = result [1]
      local tag    = result [2]
      if status and tag and tag ~= scheduler._COROUTINE_TAG then
        table.remove (result, 1)
        table.remove (result, 1)
        coroutine.yield (tag, table.unpack (result))
      end
      if coroutine.status (current) == "dead" then
        threads [i] = nil
        for j = scheduler.last, 1, -1 do
          if threads [j] then
            scheduler.last = j
            break
          end
        end
      end
    end
    i = i >= scheduler.last and 1 or i + 1
  end
end

function scheduler.connect (skt, host, port)
  skt:settimeout(0)
  repeat
    local ret, err = skt:connect (host, port)
    if ret or err ~= "timeout" then
      return ret, err
    end
    scheduler.yield ()
  until false
end

function scheduler.settimeout (client, t)
  client:settimeout (t)
end

function scheduler.receive (client, pattern)
  pattern = pattern or "*l"
  repeat
    local s, err = client:receive (pattern)
    if not s and err == "timeout" then
      scheduler.yield ()
    elseif not s then
      error (err)
    else
      return s
    end
  until false
end

function scheduler.send (client, data, from, to)
  from = from or 1
  local s, err
  local last = from - 1
  repeat
    s, err, last = client:send (data, last + 1, to)
    if not s and err == "timeout" then
      scheduler.yield ()
    elseif not s then
      error (err)
    else
      return s
    end
  until false
end

function scheduler.flush ()
end

function scheduler.setoption (client, option, value)
  client:setoption (option, value)
end

function scheduler.close (client)
  client:close ()
end

local Socket = {}

Socket.__index = Socket

function Socket:connect (host, port)
  return scheduler.connect (self.skt, host, port)
end

function Socket:settimeout (t)
  return scheduler.settimeout (self.skt, t)
end

function Socket:receive (pattern)
  return scheduler.receive (self.skt, pattern)
end

function Socket:send (data, from, to)
  return scheduler.send (self.skt, data, from, to)
end

function Socket:flush ()
  return scheduler.flush (self.skt)
end

function Socket:setoption (option, value)
  return scheduler.setoption (self.skt, option, value)
end

function Socket:close ()
  return scheduler.close (self.skt)
end

function scheduler.wrap (skt)
  skt:settimeout (0)
  return setmetatable ({
    skt = skt
  }, Socket)
end

function scheduler.iterator (f, t)
  t = t or scheduler._ITERATOR_TAG
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

return scheduler
