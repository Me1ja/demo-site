/* MelShop theme JS v1.0.0 — works alongside OpenCart common.js (jQuery) */
(function () {
  'use strict';
  var $d = document;
  function q(s, c) { return (c || $d).querySelector(s); }
  function qa(s, c) { return Array.prototype.slice.call((c || $d).querySelectorAll(s)); }
  function num(t) { var m = String(t || '').match(/\d+/); return m ? m[0] : ''; }

  /* ---------- counters (cart / wishlist / compare) ---------- */
  function setCount(kind, n) {
    qa('[data-ms-count="' + kind + '"]').forEach(function (el) { el.textContent = (n && n !== '0') ? n : ''; });
  }
  function readCounts() {
    var ct = q('#cart-total'); if (ct) setCount('cart', num(ct.textContent));
    var wl = q('#wishlist-total'); if (wl) setCount('wishlist', num(wl.getAttribute('title') || wl.textContent));
    var cp = q('#compare-total'); if (cp) setCount('compare', num(cp.textContent));
  }

  /* ---------- toast instead of top alerts ---------- */
  var toastTimer, cntTimer;
  function toast(html) {
    var t = q('#ms-toast'); if (!t) return;
    t.innerHTML = html; t.classList.add('is-on');
    clearTimeout(toastTimer); toastTimer = setTimeout(function () { t.classList.remove('is-on'); }, 3200);
  }
  function watchAlerts() {
    var mo = new MutationObserver(function (list) {
      list.forEach(function (m) {
        Array.prototype.forEach.call(m.addedNodes, function (n) {
          if (n.nodeType !== 1 || !n.classList || !n.classList.contains('alert-dismissible')) return;
          if (n.closest && n.closest('form, .modal, #content .panel, .ms-tabs')) return;
          if (!n.classList.contains('alert-success')) return;
          var clone = n.cloneNode(true); var b = clone.querySelector('button'); if (b) b.remove();
          var i = clone.querySelector('.fa'); if (i) i.remove();
          toast(clone.innerHTML.trim());
          n.parentNode && n.parentNode.removeChild(n);
          setTimeout(readCounts, 300);
        });
      });
      clearTimeout(cntTimer); cntTimer = setTimeout(readCounts, 150);
    });
    mo.observe($d.body, { childList: true, subtree: true });
  }

  /* ---------- drawer & catalog ---------- */
  function openDrawer(id) {
    var d = q('#' + id); if (!d) return;
    if (id === 'ms-drawer') {
      var body = q('[data-ms-drawer-menu]', d);
      if (body && !body.children.length) {
        var list = q('#ms-catalog .ms-menu__list');
        if (list) body.appendChild(list.cloneNode(true));
        qa('.has-children > .ms-menu__link', body).forEach(function (a) {
          a.addEventListener('click', function (e) { e.preventDefault(); a.parentNode.classList.toggle('is-open'); });
        });
      }
    }
    d.classList.add('is-open'); d.setAttribute('aria-hidden', 'false'); $d.body.classList.add('ms-lock');
  }
  function closeDrawers() {
    qa('.ms-drawer.is-open').forEach(function (d) { d.classList.remove('is-open'); d.setAttribute('aria-hidden', 'true'); });
    $d.body.classList.remove('ms-lock');
  }

  $d.addEventListener('click', function (e) {
    var t = e.target;
    var op = t.closest('[data-ms-open]');
    if (op) { e.preventDefault(); openDrawer(op.getAttribute('data-ms-open')); return; }
    if (t.closest('[data-ms-close]') || (t.classList && t.classList.contains('ms-drawer'))) { closeDrawers(); return; }
    var tg = t.closest('[data-ms-toggle]');
    if (tg) {
      e.preventDefault();
      var what = tg.getAttribute('data-ms-toggle');
      if (what === 'ms-catalog') {
        if (window.innerWidth < 992) { openDrawer('ms-drawer'); return; }
        q('#ms-header').classList.toggle('is-catalog');
      } else if (what === 'ms-search-open') {
        $d.body.classList.toggle('ms-search-open');
        if ($d.body.classList.contains('ms-search-open')) {
          window.scrollTo({ top: 0, behavior: 'smooth' });
          var inp = q('#search input[name="search"]'); if (inp) setTimeout(function () { inp.focus(); }, 150);
        }
      }
      return;
    }
    var hd = q('#ms-header');
    if (hd && hd.classList.contains('is-catalog') && !t.closest('#ms-catalog') && !t.closest('.ms-catbtn')) hd.classList.remove('is-catalog');
    var qb = t.closest('[data-ms-qty]');
    if (qb) {
      var input = q('#input-quantity'); if (!input) return;
      var v = (parseInt(input.value, 10) || 1) + parseInt(qb.getAttribute('data-ms-qty'), 10);
      input.value = Math.max(1, v);
      if (window.jQuery) jQuery(input).trigger('change');
    }
  });
  $d.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { closeDrawers(); var hd = q('#ms-header'); hd && hd.classList.remove('is-catalog'); }
  });

  /* ---------- header shadow ---------- */
  function onScroll() { var hd = q('#ms-header'); if (hd) hd.classList.toggle('is-scrolled', window.scrollY > 8); }

  /* ---------- sticky buy bar on product page ---------- */
  function stickyBuy() {
    var btn = q('#button-cart'), price = q('[data-ms-price]');
    if (!btn || !price) return;
    var bar = $d.createElement('div');
    bar.className = 'ms-sticky-buy';
    bar.innerHTML = '<b></b><button type="button" class="btn btn-primary"></button>';
    bar.querySelector('b').textContent = price.textContent.trim();
    bar.querySelector('button').innerHTML = btn.innerHTML;
    bar.querySelector('button').addEventListener('click', function () { btn.click(); });
    $d.body.appendChild(bar);
    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (en) { bar.classList.toggle('is-on', !en[0].isIntersecting); }).observe(btn);
    }
  }

  /* ---------- 2 columns grid on phones (common.js sets col-xs-12) ---------- */
  function fixGrid() {
    qa('#content .product-grid.col-xs-12').forEach(function (el) { el.classList.remove('col-xs-12'); el.classList.add('col-xs-6'); });
  }

  function init() {
    readCounts(); watchAlerts(); stickyBuy(); onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
    setTimeout(fixGrid, 50);
    var gv = q('#grid-view'); if (gv) gv.addEventListener('click', function () { setTimeout(fixGrid, 20); });
  }
  if ($d.readyState === 'loading') $d.addEventListener('DOMContentLoaded', init); else init();
})();
