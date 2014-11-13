local Connection = {}

function Connection.request (context)
  local headers = context.request.headers
  local tokens  = {}
  for word in headers.connection:gmatch "([^,%s]+)" do
    tokens [word:lower ():gsub ("-", "_")] = true
  end
  headers.connection = tokens
  --
  local res_headers  = context.response.headers
  res_headers.connection = {}
  if tokens.keep_alive then
    res_headers.connection.keep_alive = true
  elseif tokens.close then
    res_headers.connection.close = true
  end
end

local function concat (t)
  local r = {}
  for k in pairs (t) do
    r [#r + 1] = k:gsub ("_", "-")
  end
  return table.concat (r, ", ")
end

function Connection.response (context)
  local headers = context.response.headers
  if headers.connection.close then
    context.continue = false
  end
  headers.connection = concat (headers.connection)
end

return Connection
