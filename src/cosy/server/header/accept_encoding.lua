local word_pattern      = "%s*([^=;,%s]+)%s*"
local parameter_pattern = "%s*([^/=;,%s]+)%s*[=]%s*([^;,%s]+)%s*"

local cache = setmetatable ({}, { __mode = "kv" })

local Accept_Encoding = {}

function Accept_Encoding.request (context)
  local headers = context.request.headers
  local value   = headers.accept_encoding
  local cached  = cache [value]
  if cached then
    headers.accept_encoding = cached
    return
  end
  local accepts = {}
  headers.accept_encoding = accepts
  for part in value:gmatch "%s*([^,]+)" do
    local v = part:match (word_pattern)
    local result = {
      value      = v,
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

return Accept_Encoding
