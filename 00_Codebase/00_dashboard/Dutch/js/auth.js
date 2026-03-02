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

  /**
   * Decode JWT payload (no signature check; used only for UI role visibility).
   * @returns {object|null} Payload or null
   */
  function getPayload() {
    var token = Api.getToken();
    if (!token || typeof token !== 'string') return null;
    var parts = token.split('.');
    if (parts.length !== 3) return null;
    try {
      var b64 = parts[1].replace(/-/g, '+').replace(/_/g, '/');
      var pad = b64.length % 4;
      if (pad) b64 += (new Array(5 - pad)).join('=');
      return JSON.parse(decodeURIComponent(escape(atob(b64))));
    } catch (e) {
      return null;
    }
  }

  /**
   * Get current user role from JWT (default 'user' if missing).
   * @returns {string}
   */
  function getRole() {
    var p = getPayload();
    return (p && typeof p.role === 'string') ? p.role : 'user';
  }

  /**
   * Return true if current user's role is in the allowed list.
   * @param {string[]} allowedRoles e.g. ['admin', 'editor']
   * @returns {boolean}
   */
  function hasRole(allowedRoles) {
    if (!Array.isArray(allowedRoles) || allowedRoles.length === 0) return false;
    var role = getRole();
    return allowedRoles.indexOf(role) !== -1;
  }

  global.DutchDashboardAuth = {
    requireAuth: requireAuth,
    login: login,
    logout: logout,
    getPayload: getPayload,
    getRole: getRole,
    hasRole: hasRole
  };
})(typeof window !== 'undefined' ? window : this);
