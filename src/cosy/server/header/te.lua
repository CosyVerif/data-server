local TE = {}

function TE.request (context)
  local request  = context.request
  local response = context.response
  local value    = request.headers.te
  local headers  = response.headers
  headers.transfer_encoding = {}
  for word in value:gmatch "([^,%s]+)" do
    headers.transfer_encoding [word:lower ():gsub ("-", "_")] = true
  end
end

return TE
