return function (context, value)
  local response = context.response
  value          = value:lower ()
  if value == "keep-alive" then
    response.headers.connection = "keep-alive"
  elseif value == "close" then
    response.headers.connection = "close"
  elseif value == "upgrade" then
    -- do nothing
  end
end
