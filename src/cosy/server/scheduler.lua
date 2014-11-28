local Socket = {}

Socket.__index = Socket

function Socket:connect (host, port)
  local coroutine = self.coroutine
  local socket    = self.socket
  socket:settimeout (0)
  repeat
    local ret, err = socket:connect (host, port)
    if not ret and err == "timeout" then
      coroutine.yield ()
    elseif not ret then
      return ret, err
    else
      ret:settimeout (0)
      return ret
    end
  until false
end

function Socket:close ()
  local socket = self.socket
  socket:shutdown ()
  socket:close ()
end

function Socket:receive (pattern)
  local coroutine = self.coroutine
  local socket    = self.socket
  socket:settimeout (0)
  pattern = pattern or "*l"
  repeat
    local s, err = socket:receive (pattern)
    if not s and err == "timeout" then
      coroutine.yield ()
    elseif not s then
      error (err)
    else
      return s
    end
  until false
end

function Socket:send (data, from, to)
  local coroutine = self.coroutine
  local socket    = self.socket
  socket:settimeout (0)
  from = from or 1
  local s, err
  local last = from - 1
  repeat
    s, err, last = socket:send (data, last + 1, to)
    if not s and err == "timeout" then
      coroutine.yield ()
    elseif not s then
      error (err)
    else
      return s
    end
  until false
end

function Socket.flush ()
end

function Socket:setoption (option, value)
  local socket = self.socket
  socket:setoption (option, value)
end

function Socket:settimeout (t)
  local socket = self.socket
  socket:settimeout (t)
end

local Scheduler = {}

Scheduler.__index = Scheduler

function Scheduler.create ()
  return setmetatable ({
    threads   = {},
    _last     = 0,
    coroutine = require "coroutine.make" (),
  }, Scheduler)
end

function Scheduler.addthread (scheduler, f, ...)
  local threads   = scheduler.threads
  local coroutine = scheduler.coroutine
  local i         = #threads + 1
  local args      = { ... }
  threads [i]     = coroutine.create (function () f (table.unpack (args)) end)
  scheduler._last = math.max (i, scheduler._last)
end

function Scheduler.addserver (scheduler, socket, handler)
  local sleep     = require "socket" . sleep
  local coroutine = scheduler.coroutine
  socket:settimeout (0)
  scheduler:addthread (function ()
    while not scheduler.stopping do
      local client, err = socket:accept ()
      if not client and err == "timeout" then
        if scheduler._last == 1 then
          sleep (0.01)
        else
          coroutine.yield ()
        end
      elseif not client then
        error (err)
      else
        scheduler:addthread (function ()
          local status, err = pcall (handler, scheduler:wrap (client))
          if not status then
            print (err)
          end
          client:close ()
        end)
      end
    end
  end)
end

function Scheduler.pass (scheduler)
  local coroutine = scheduler.coroutine
  coroutine.yield ()
end

function Scheduler.stop (scheduler, brutal)
  scheduler.stopping  = true
  scheduler.addthread = function ()
    error "Method addthread is disabled."
  end
  scheduler.addserver = function ()
    error "Method addserver is disabled."
  end
  if brutal then
    local threads = scheduler.threads
    for i in pairs (threads) do
      threads [i] = nil
    end
    scheduler._last = 0
  end
end

function Scheduler.loop (scheduler)
  local threads   = scheduler.threads
  local coroutine = scheduler.coroutine
  local i         = 1
  while scheduler._last ~= 0 do
    local current = threads [i]
    if current ~= nil then
      local status, err = coroutine.resume (current)
      if not status then
        print (err)
      end
      if coroutine.status (current) == "dead" then
        threads [i] = nil
        for j = scheduler._last, 0, -1 do
          if threads [j] then
            break
          else
            scheduler._last = j
          end
        end
      end
    end
    i = i >= scheduler._last and 1 or i + 1
  end
end

function Scheduler.wrap (scheduler, socket)
  return setmetatable ({
    coroutine = scheduler.coroutine,
    socket    = socket,
  }, Socket)
end

return Scheduler
