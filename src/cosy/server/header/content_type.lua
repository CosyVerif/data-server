local pair_pattern      = "%s*([^/=;,%s]+)%s*[/]%s*([^;,%s]+)%s*"
local parameter_pattern = "%s*([^/=;,%s]+)%s*[=]%s*([^;,%s]+)%s*"

local cache = setmetatable ({}, { __mode = "kv" })

local Content_Type = {}

function Content_Type.request (context)
  local headers = context.request.haeders
  local value   = headers.content_type
  local cached  = cache [value]
  if cached then
    headers.content_type = cached
    return
  end
  local main_type, sub_type = value:match (pair_pattern)
  local result = {
    main_type  = main_type,
    sub_type   = sub_type,
    parameters = {},
  }
  for k, v in value:gmatch (parameter_pattern) do
    result.parameters [k] = v
  end
  headers.content_type = result
  cache [value]        = result
end

function Content_Type.response (context)
  local headers = context.response.headers
  local value   = headers.content_type
  headers.content_type = "${main}/${sub}" % {
    main = value.main_type,
    sub  = value.sub_type,
  }
end

return Content_Type
