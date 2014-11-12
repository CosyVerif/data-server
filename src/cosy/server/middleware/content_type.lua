local json = require "cjson"
local yaml = require "yaml"

local Content_Type = {}

function Content_Type.request (context)
  local request = context.request
  local ct = request.headers.content_type
  if not ct then
    return
  end
  local body
  if     ct.main_type == "application" and ct.sub_type == "json" then
    body = json.decode (request.body)
  elseif ct.main_type == "application" and ct.sub_type == "yaml" then
    body = yaml.decode (request.body)
  elseif ct.main_type == "application" and ct.sub_type == "lua"  then
    error {
      code    = 501,
      message = "Not Implemented",
    }
  else
    error {
      code    = 415,
      message = "Unsupported Media Type",
      reason  = "unknown Content-Type",
    }
  end
  if not body then
    error {
      code    = 412,
      message = "Precondition Failed",
      reason  = "unable to parse body",
    }
  end
  request.body = body
end

function Content_Type.response (context)
  local request  = context.request
  local response = context.response
  local accepts  = request.headers.accept
  local body = false
  for _, ct in ipairs (accepts) do
    if     ct.main_type == "*"           and ct.sub_type == "*"    then
      response.headers.content_type = {
        main_type = "application",
        sub_type  = "json",
      }
      body = json.encode (response.body)
    elseif ct.main_type == "application" and ct.sub_type == "*"    then
      response.headers.content_type = {
        main_type = "application",
        sub_type  = "json",
      }
    elseif ct.main_type == "application" and ct.sub_type == "json" then
      response.headers.content_type = {
        main_type = "application",
        sub_type  = "json",
      }
      body = json.encode (response.body)
    elseif ct.main_type == "application" and ct.sub_type == "yaml" then
      response.headers.content_type = {
        main_type = "application",
        sub_type  = "yaml",
      }
      body = yaml.encode (response.body)
    elseif ct.main_type == "application" and ct.sub_type == "lua"  then
      response.headers.content_type = {
        main_type = "application",
        sub_type  = "lua",
      }
      error {
        code    = 501,
        message = "Not Implemented",
      }
    end
  end
  if body == nil then
    error {
      code    = 412,
      message = "Precondition Failed",
      reason  = "unable to pretty-print body",
    }
  elseif body == false then
    error {
      code    = 406,
      message = "Not Acceptable",
      reason  = "no Accept handled",
    }
  end
  response.body = body
end

return Content_Type
