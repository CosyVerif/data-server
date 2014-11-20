local Error = {}

Error [100] = "Continue"
Error [101] = "Switching Protocols"
Error [102] = "Processing"
Error [118] = "Connection timed out"
Error [200] = "OK"
Error [201] = "Created"
Error [202] = "Accepted"
Error [203] = "Non-Authoritative Information"
Error [204] = "No Content"
Error [205] = "Reset Content"
Error [206] = "Partial Content"
Error [207] = "Multi-Status"
Error [210] = "Content Different"
Error [226] = "IM Used"
Error [300] = "Multiple Choices"
Error [301] = "Moved Permanently"
Error [302] = "Moved Temporarily"
Error [303] = "See Other"
Error [304] = "Not Modified"
Error [305] = "Use Proxy"
Error [307] = "Temporary Redirect"
Error [308] = "Permanent Redirect"
Error [310] = "Too many Redirects"
Error [400] = "Bad Request"
Error [401] = "Unauthorized"
Error [402] = "Payment Required"
Error [403] = "Forbidden"
Error [404] = "Not Found"
Error [405] = "Method Not Allowed"
Error [406] = "Not Acceptable"
Error [407] = "Proxy Authentication Required"
Error [408] = "Request Time-out"
Error [409] = "Conflict"
Error [410] = "Gone"
Error [411] = "Length Required"
Error [412] = "Precondition Failed"
Error [413] = "Request Entity Too Large"
Error [414] = "Request-URI Too Long"
Error [415] = "Unsupported Media Type"
Error [416] = "Requested range unsatisfiable."
Error [417] = "Expectation failed"
Error [418] = "I’m a teapot"
Error [422] = "Unprocessable entity"
Error [423] = "Locked"
Error [424] = "Method failure"
Error [425] = "Unordered Collection"
Error [426] = "Upgrade Required"
Error [428] = "Precondition Required"
Error [429] = "Too Many Requests"
Error [431] = "Request Header Fields Too Large"
Error [449] = "Retry With"
Error [450] = "Blocked by Windows Parental Controls"
Error [456] = "Unrecoverable Error"
Error [499] = "Client Has Closed Connection"
Error [500] = "Internal Server Error"
Error [501] = "Not Implemented"
Error [502] = "Bad Gateway or Proxy Error"
Error [503] = "Service Unavailable"
Error [504] = "Gateway Time-out"
Error [505] = "HTTP Version not supported"
Error [506] = "Variant also negociate"
Error [507] = "Insufficient storage"
Error [508] = "Loop detected"
Error [509] = "Bandwidth Limit Exceeded"
Error [510] = "Not extended"
Error [520] = "Unknown Error"

-- Build shortcuts for errors.
do
  local functions = {}
  for code, message in pairs (Error) do
    local id = message:gsub ("[^%w]", "_")
    functions [id] = function (x)
      if type (x) == "table" then
        x.code    = code
        x.message = message
        error (x)
      else
        error {
          code    = code,
          message = message,
          reason  = x,
        }
      end
    end
    functions ["_" .. tostring (code)] = functions [id]
  end
  for k, v in pairs (functions) do
    Error [k] = v
  end
end

return Error
