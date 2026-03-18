/**
 * API base URL and fetch helpers for Dutch.mt Dashboard.
 * No JWT secret or service key in frontend. Uses configurable API base.
 */

(function (global) {
  'use strict';

  // API base: relative to current origin when served with PHP backend, or set via window.DUTCH_DASHBOARD_API_BASE
  var API_BASE = (typeof global.DUTCH_DASHBOARD_API_BASE !== 'undefined' && global.DUTCH_DASHBOARD_API_BASE)
    ? global.DUTCH_DASHBOARD_API_BASE.replace(/\/$/, '')
    : '';

  function getToken() {
    try {
      return localStorage.getItem('dutch_dashboard_access_token') || '';
    } catch (e) {
      return '';
    }
  }

  function getRefreshToken() {
    try {
      return localStorage.getItem('dutch_dashboard_refresh_token') || '';
    } catch (e) {
      return '';
    }
  }

  function setTokens(accessToken, refreshToken) {
    try {
      if (accessToken) localStorage.setItem('dutch_dashboard_access_token', accessToken);
      if (refreshToken) localStorage.setItem('dutch_dashboard_refresh_token', refreshToken);
    } catch (e) {}
  }

  function clearTokens() {
    try {
      localStorage.removeItem('dutch_dashboard_access_token');
      localStorage.removeItem('dutch_dashboard_refresh_token');
    } catch (e) {}
  }

  function url(path) {
    var p = path.charAt(0) === '/' ? path : '/' + path;
    return API_BASE ? (API_BASE + p) : p;
  }

  /**
   * Refresh token and retry once. Returns promise that resolves to new access token or rejects.
   */
  function refreshAndRetry() {
    var refresh = getRefreshToken();
    if (!refresh) return Promise.reject(new Error('No refresh token'));

    return fetch(url('/api/refresh.php'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ refresh_token: refresh })
    })
      .then(function (res) { return res.json().then(function (data) { return { res: res, data: data }; }); })
      .then(function (out) {
        if (out.data && out.data.access_token) {
          setTokens(out.data.access_token, out.data.refresh_token || getRefreshToken());
          return out.data.access_token;
        }
        throw new Error(out.data && out.data.error ? out.data.error : 'Refresh failed');
      });
  }

  /**
   * Fetch with optional auth. On 401, tries refresh once and retries; then redirects to login.
   * @param {string} path - Path (e.g. '/api/get-tournaments.php')
   * @param {object} options - fetch options (method, body, etc.)
   * @param {boolean} options.auth - If true, add Bearer and handle 401 with refresh
   */
  function apiFetch(path, options) {
    options = options || {};
    var auth = options.auth !== false;
    var token = auth ? getToken() : '';

    function doFetch(accessToken) {
      var headers = Object.assign({}, options.headers || {});
      if (!headers['Content-Type']) headers['Content-Type'] = 'application/json';
      if (accessToken) headers['Authorization'] = 'Bearer ' + accessToken;

      return fetch(url(path), Object.assign({}, options, { headers: headers }));
    }

    return doFetch(token).then(function (res) {
      if (res.status === 401 && auth) {
        return refreshAndRetry()
          .then(function (newToken) { return doFetch(newToken); })
          .catch(function () {
            clearTokens();
            if (typeof window !== 'undefined' && window.location) {
              var login = window.location.pathname.replace(/[^/]*$/, '') + 'login.html';
              window.location.href = login + (window.location.search ? '?' + window.location.search : '');
            }
            return Promise.reject(new Error('Unauthorized'));
          });
      }
      return res;
    });
  }

  global.DutchDashboardApi = {
    getBase: function () { return API_BASE; },
    url: url,
    getToken: getToken,
    getRefreshToken: getRefreshToken,
    setTokens: setTokens,
    clearTokens: clearTokens,
    fetch: apiFetch,
    refreshAndRetry: refreshAndRetry
  };
})(typeof window !== 'undefined' ? window : this);
