export const apiDomain = '/api/v1/'
export const loginUrl = apiDomain + 'oauth/token'
export const userUrl = apiDomain + '/user'

export const getHeader = function () {
  const tokenData = JSON.parse(window.localStorage.getItem('authUser'))
  return {
    Accept: 'application/json',
    Authorization: 'Bearer ' + tokenData.access_token
  }
}
