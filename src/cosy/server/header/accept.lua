local pair_pattern      = "%s*([^/=;,%s]+)%s*[/]%s*([^;,%s]+)%s*"
local parameter_pattern = "%s*([^/=;,%s]+)%s*[=]%s*([^;,%s]+)%s*"

local cache = setmetatable ({}, { __mode = "kv" })

local Accept = {}

function Accept.request (context)
  local headers = context.request.headers
  local value   = headers.accept
  local cached  = cache [value]
  if cached then
    headers.accept = cached
    return
  end
  local accepts  = {}
  headers.accept = accepts
  for part in value:gmatch "%s*([^,]+)" do
    local main_type, sub_type = part:match (pair_pattern)
    local result = {
      main_type  = main_type,
      sub_type   = sub_type,
      parameters = {},
    }
    for k, v in part:gmatch (parameter_pattern) do
      result.parameters [k] = v
    end
    accepts [#accepts + 1] = result
  end
  table.sort (accepts, function (l, r)
    local ql = l.parameters.q or 1
    local qr = r.parameters.q or 1
    return tonumber (ql) > tonumber (qr)
  end)
  cache [value] = accepts
end

return Accept
