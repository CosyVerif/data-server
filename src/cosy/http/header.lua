-- http://stackoverflow.com/questions/20284515/capitalize-first-letter-of-every-word-in-lua
function string:to_http ()
  return self:gsub ("_", "-"):gsub ("(%a)(%a*)", function (letter, r)
    return letter:upper() .. r:lower()
  end)
end

function string:to_identifier ()
  return self:trim ():lower ():gsub ("-", "_")
end

local Composable = {}

function Composable:parse (context)
  local lhs = self._lhs
  local rhs = self._rhs
  lhs:parse (context)
  rhs:parse (context)
end

function Composable:write (context)
  local lhs = self._lhs
  local rhs = self._rhs
  rhs:write (context)
  lhs:write (context)
end

function Composable.request ()
end

function Composable.response ()
end

function Composable.trailer ()
end

function Composable.__mul (lhs, rhs)
  return setmetatable ({
    _lhs = lhs,
    _rhs = rhs,
  }, Composable)
end


local Identity = setmetatable ({}, Composable)

function Identity.parse ()
end

function Identity.write ()
end


local Tokens = setmetatable ({}, Composable)

function Tokens:parse (context)
  local value  = context.request.headers [self._identifier]
  local tokens = {}
  for word in value:gmatch "([^,%s]+)" do
    tokens [word] = true
  end
  context.request.headers [self._identifier] = tokens
end

function Tokens:write (context)
  local value  = context.request.headers [self._identifier]
  local result = ""
  for k in pairs (value) do
    result = result .. k
  end
  context.request.headers [self._identifier] = result
end

local Integer = setmetatable ({}, Composable)

function Integer:parse (context)
  local header = context.request.headers [self._identifier]
  context.request.headers [self._identifier] = tonumber (header)
end

function Integer:write (context)
  local header = context.request.headers [self._identifier]
  context.request.headers [self._identifier] = tostring (header)
end

local Parameterized = setmetatable ({}, Composable)

Parameterized.token_pattern     = "([^;%s]+)%s*;?(.*)"
Parameterized.parameter_pattern = "([^=;%s]+)%s*[=]%s*([^;%s]+)"

function Parameterized:parse (context)
  local header = context.request.headers [self._identifier]
  local result = {}
  for token in pairs (header) do
    local value, remaining = token:match (Parameterized.token_pattern) 
    local parameters = {}
    for k, v in remaining:gmatch (Parameterized.parameter_pattern) do
      parameters [k] = v
    end
    result [value] = parameters
  end
  context.request.headers [self._identifier] = result
end

function Parameterized:write (context)
  local header = context.request.headers [self._identifier]
  local result = {}
  for token, parameters in pairs (header) do
    local value = token
    for k, v in pairs (parameters) do
      value = value .. "; " .. k .. "=" .. v
    end
    result [value] = true
  end
  context.request.headers [self._identifier] = result
end

local Normalized = setmetatable ({}, Composable)

function Normalized:parse (context)
  local header = context.request.headers [self._identifier]
  local result = {}
  for k, v in pairs (header) do
    result [k:to_identifier ()] = v
  end
  context.request.headers [self._identifier] = result
end

function Normalized:write (context)
  local header = context.request.headers [self._identifier]
  local result = {}
  for k, v in pairs (header) do
    result [k:to_http ()] = v
  end
  context.request.headers [self._identifier] = result
end

local Sorted = setmetatable ({}, Composable)

function Sorted:parse (context)
  local header = context.request.headers [self._identifier]
  local result = {}
  for token, parameters in pairs (header) do
    if parameters.q then
      parameters.q = tonumber (parameters.q)
    end
    result [#result + 1] = {
      token      = token,
      parameters = parameters,
    }
  end
  table.sort (result, function (lhs, rhs)
    local l = lhs.parameters.q or 1
    local r = rhs.parameters.q or 1
    return l > r
  end)
  context.request.headers [self._identifier] = result
end

function Sorted:write (context)
  local header = context.request.headers [self._identifier]
  local result = {}
  for x in ipairs (header) do
    result [x.token] = x.parameters
  end
  context.request.headers [self._identifier] = result
end

local MIME = setmetatable ({}, Composable)

MIME.pattern = "([^/%s]+)%s*=%s*(.*)"

function MIME:parse (context)
  local header = context.request.headers [self._identifier]
  for _, x in ipairs (header) do
    local main, sub = x.token:match (MIME.pattern) 
    x.main = main:lower ()
    x.sub  = sub:lower ()
  end
end

function MIME:write (context)
  local header = context.request.headers [self._identifier]
  for _, x in ipairs (header) do
    x.token = x.main .. "/" .. x.sub
    x.main  = nil
    x.sub   = nil
  end
end

local Language = setmetatable ({}, Composable)

Language.pattern = "(%a+)(-(%a+))?"

function Language:parse (context)
  local header = context.request.headers [self._identifier]
  for i, x in ipairs (header) do
    i = i
    local primary, _, sub = x.token:match (Language.pattern) 
    x.primary = primary:lower ()
    if sub then
      x.sub   = sub:lower ()
    end
  end
end

function Language:write (context)
  local header = context.request.headers [self._identifier]
  for _, x in ipairs (header) do
    x.token = x.primary
    if x.sub then
      x.token = x.token .. "-" .. x.sub
    end
    x.primary = nil
    x.sub     = nil
  end
end

local Header = {}

function Header.__lt (lhs, rhs)
  if not lhs._depends then
    lhs._depends = {}
    for _, d in ipairs (lhs.depends or {}) do
      local id = d:to_identifier ()
      lhs._depends [id] = true
    end
  end
  return not lhs._depends [rhs._identifier]
end

function Header:__tostring ()
  return self._http
end

function Header.class (name, filters)
  local result = setmetatable ({}, Header)
  result._identifier = name:to_identifier ()
  result._http       = name:to_http ()
  result.depends     = {}
  local io = Identity
  for f in ipairs (filters or {}) do
    io = io * f
  end
  result.io          = io
  result.request     = nil
  result.response    = nil
  return result
end

return {
  class         = Header.class,
  Identity      = Identity,
  Tokens        = Tokens,
  Parameterized = Parameterized,
  Normalized    = Normalized,
  Sorted        = Sorted,
  MIME          = MIME,
  Language      = Language,
}
