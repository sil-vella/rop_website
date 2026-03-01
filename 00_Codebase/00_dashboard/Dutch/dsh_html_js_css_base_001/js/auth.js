/**
 * Auth helpers: login, logout, and protected page check for Dutch.mt Dashboard.
 */

(function (global) {
  'use strict';

  var Api = global.DutchDashboardApi;
  if (!Api) {
    console.error('auth.js requires api.js');
    return;
  }

  /**
   * If no valid token, redirect to login. Call on protected pages (e.g. index.html).
   */
  function requireAuth() {
    if (!Api.getToken()) {
      var base = (typeof window !== 'undefined' && window.location)
        ? window.location.pathname.replace(/[^/]*$/, '') + 'login.html'
        : 'login.html';
      window.location.href = base + (window.location.search ? '?' + window.location.search : '');
      return false;
    }
    return true;
  }

  /**
   * Login: POST to api/login.php with credentials. On success stores tokens and returns data.
   * @param {string} username
   * @param {string} password
   * @returns {Promise<object>} Response data (access_token, refresh_token, etc.)
   */
  function login(username, password) {
    return Api.fetch('/api/login.php', {
      method: 'POST',
      auth: false,
      body: JSON.stringify({ username: username, password: password })
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (data && data.access_token) {
          Api.setTokens(data.access_token, data.refresh_token || '');
          return data;
        }
        throw new Error(data && data.error ? data.error : 'Login failed');
      });
  }

  /**
   * Logout: clear tokens and optionally redirect to login.
   */
  function logout(redirectToLogin) {
    Api.clearTokens();
    if (redirectToLogin !== false && typeof window !== 'undefined' && window.location) {
      var login = window.location.pathname.replace(/[^/]*$/, '') + 'login.html';
      window.location.href = login;
    }
  }

  global.DutchDashboardAuth = {
    requireAuth: requireAuth,
    login: login,
    logout: logout
  };
})(typeof window !== 'undefined' ? window : this);
