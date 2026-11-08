// Mobile menu only. This file makes no network requests and stores nothing.
(function () {
  'use strict';
  var btn = document.querySelector('.menu-btn');
  var nav = document.getElementById('siteNav');
  if (!btn || !nav) return;
  btn.addEventListener('click', function () {
    var open = nav.classList.toggle('open');
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    btn.setAttribute('aria-label', open ? 'メニューを閉じる' : 'メニューを開く');
  });
})();
