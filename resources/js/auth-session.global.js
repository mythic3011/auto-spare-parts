// One storage contract for legacy modules and classic staff scripts.
(() => {
  if (window.SLMC_AUTH) return;
  const keys = ['token', 'userToken'];
  function clear() {
    for (const key of keys) {
      sessionStorage.removeItem(key);
      localStorage.removeItem(key);
    }
  }
  function getToken() {
    for (const storage of [sessionStorage, localStorage]) {
      for (const key of keys) {
        const value = storage.getItem(key);
        if (value) return value;
      }
    }
    for (const key of ['token', 'userToken', 'auth_token', 'jwt']) {
      const cookie = document.cookie.split('; ').find(value => value.startsWith(key + '='));
      if (cookie) return decodeURIComponent(cookie.slice(key.length + 1));
    }
    return '';
  }
  function storeToken(token, remember) {
    if (typeof token !== 'string' || !token.trim()) throw new Error('Missing authentication token');
    clear();
    const storage = remember ? localStorage : sessionStorage;
    for (const key of keys) storage.setItem(key, token);
  }
  window.SLMC_AUTH = Object.freeze({ getToken, storeToken, clear });
})();
