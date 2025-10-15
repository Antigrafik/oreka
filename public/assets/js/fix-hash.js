window.addEventListener('DOMContentLoaded', () => {
  if (location.hash) {
    history.replaceState(null, '', location.pathname + location.search);
    setTimeout(() => window.scrollTo(0, 0), 50);
  }
});

if ('scrollRestoration' in history) {
  history.scrollRestoration = 'manual';
}

window.addEventListener('load', () => {
  window.scrollTo(0, 0);
});
