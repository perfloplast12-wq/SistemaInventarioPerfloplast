(function () {
  const KEY = 'fi-accent';
  const saved = localStorage.getItem(KEY) || 'ocean';
  document.documentElement.setAttribute('data-accent', saved);

  window.setFilamentAccent = function (accent) {
    document.documentElement.setAttribute('data-accent', accent);
    localStorage.setItem(KEY, accent);
  };
})();
