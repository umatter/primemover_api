
# API User Authorization
1. Send a POST request with 2 parameters to https://url.to.api/api/v1/login
   ```
   {
        email: researcher@primemover.example,
        password: CHANGEME-backend-password
   }
   ```
2. Take `access_token` from response
3. Use `headers` to send `Authorization` key with the value from `access_token` in each request in this format: `Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9......`
