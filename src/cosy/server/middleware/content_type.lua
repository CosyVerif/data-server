local json = require "cjson"
local yaml = require "yaml"
local url  = require "socket.url"

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
    body = yaml.load (request.body)
  elseif ct.main_type == "application" and ct.sub_type == "lua"  then
    error {
      code    = 501,
      message = "Not Implemented",
    }
  elseif ct.main_type == "application" and ct.sub_type == "x-www-form-urlencoded" then
    body = {}
    for p in request.body:gmatch "([^;&]+)" do
      local k, v = p:match "([^=]+)=(.*)"
      k = url.unescape (k):gsub ("+", " ")
      v = url.unescape (v):gsub ("+", " ")
      body [k] = v
    end
  elseif ct.main_type == "multipart" and ct.sub_type == "form-data" then
    error {
      code    = 502,
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

local encode = {}

encode.json = function (x)
  if type (x) == "function" then
    return function ()
      local value = x ()
      if value == nil then
        return nil
      else
        return json.encode (value) .. "\r\n"
      end
    end
  elseif type (x) == "table" then
    return json.encode (x) .. "\r\n"
  else
    assert (false)
  end
end

encode.yaml = function (x)
  if type (x) == "function" then
    return function ()
      local value = x ()
      if value == nil then
        return nil
      else
        return yaml.dump (value) .. "\r\n"
      end
    end
  elseif type (x) == "table" then
    return yaml.dump (x) .. "\r\n"
  else
    assert (false)
  end
end

function Content_Type.response (context)
  local request  = context.request
  local response = context.response
  local accepts  = request.headers.accept
  if response.body == nil then
    response.body = {}
  elseif response.protocol == "HTTP/1.0"
  and type (response.body) == "function" then
    local bodies = {}
    for b in response.body do
      bodies [#bodies + 1] = b
    end
    response.body = bodies
  end
  local encoder
  for _, ct in ipairs (accepts) do
    if ct.main_type == "*"           and ct.sub_type == "*"
    or ct.main_type == "application" and ct.sub_type == "*"
    or ct.main_type == "application" and ct.sub_type == "json" then
      context.response.headers.content_type = {
        main_type = "application",
        sub_type  = "json",
      }
      encoder = encode.json
    elseif ct.main_type == "application" and ct.sub_type == "yaml" then
      context.response.headers.content_type = {
        main_type = "application",
        sub_type  = "yaml",
      }
      encoder = encode.yaml
    elseif ct.main_type == "application" and ct.sub_type == "lua"  then
      error {
        code    = 501,
        message = "Not Implemented",
      }
    end
  end
  if encoder == false then
    error {
      code    = 406,
      message = "Not Acceptable",
      reason  = "no Accept handled",
    }
  end
  response.body = encoder (response.body)
end

return Content_Type
