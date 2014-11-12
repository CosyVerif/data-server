local Connection = {}

function Connection.request (context)
  local req_headers  = context.request.headers
  local res_headers  = context.response.headers
  local value        = req_headers.connection:lower ()
  if value == "keep-alive" then
    res_headers.connection = "keep-alive"
  elseif value == "close" then
    res_headers.connection = "close"
  elseif value == "upgrade" then
    -- do nothing
  end
end

function Connection.response (context)
  local res_headers  = context.response.headers
  local value        = res_headers.connection:lower ()
  if     value == "keep-alive" then
  elseif value == "close"      then
    context.continue = false
  elseif value == "upgrade"    then
    -- do nothing
  end
end

return Connection
