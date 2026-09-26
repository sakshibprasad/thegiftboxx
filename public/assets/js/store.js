/* The Gift Boxx — storefront interactions (no frameworks). */
(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var csrf = (window.TGB || {}).csrf;
  var money = function (n) { return '₹' + Number(n).toLocaleString('en-IN', { maximumFractionDigits: Number(n) % 1 ? 2 : 0 }); };

  /* ---------- Analytics dispatch (GA4 + Google Ads + Meta Pixel) ---------- */
  var metaMap = { view_item: 'ViewContent', add_to_cart: 'AddToCart', begin_checkout: 'InitiateCheckout', purchase: 'Purchase' };
  function sendEvent(name, data) {
    try {
      if (window.gtag) {
        gtag('event', name, data);
        if (name === 'purchase' && window.TGB_TRACK && TGB_TRACK.ads) {
          gtag('event', 'conversion', { send_to: TGB_TRACK.ads, value: data.value, currency: 'INR', transaction_id: data.transaction_id });
        }
      }
      if (window.fbq && metaMap[name]) {
        var p = { currency: 'INR', value: data.value, content_type: 'product', contents: (data.items || []).map(function (i) { return { id: i.item_id, quantity: i.quantity }; }) };
        p.content_ids = p.contents.map(function (c) { return c.id; });
        fbq('track', metaMap[name], p, name === 'purchase' ? { eventID: 'order-' + data.transaction_id } : undefined);
      }
      var pinMap = { view_item: 'pagevisit', add_to_cart: 'addtocart', purchase: 'checkout' };
      if (window.pintrk && pinMap[name]) {
        pintrk('track', pinMap[name], { value: data.value, currency: 'INR', order_id: data.transaction_id, order_quantity: (data.items || []).reduce(function (a, i) { return a + (i.quantity || 1); }, 0),
          line_items: (data.items || []).map(function (i) { return { product_id: i.item_id, product_name: i.item_name, product_price: i.price, product_quantity: i.quantity }; }) });
      }
      if (window.dataLayer) dataLayer.push({ event: name, ecommerce: data });
    } catch (e) { /* never break the page for analytics */ }
  }
  (window.TGB_EVENTS || []).forEach(function (e) { sendEvent(e.event, e.data); });

  /* ---------- Header shadow on scroll ---------- */
  var header = $('.site-header');
  var onScroll = function () { if (header) header.classList.toggle('scrolled', window.scrollY > 8); };
  window.addEventListener('scroll', onScroll, { passive: true }); onScroll();

  /* ---------- Reveal on scroll + gentle parallax ---------- */
  var reveals = $$('.reveal');
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } });
    }, { rootMargin: '0px 0px -4% 0px', threshold: 0.01 });
    reveals.forEach(function (el) { io.observe(el); });
  } else { reveals.forEach(function (el) { el.classList.add('in'); }); }
  var parallax = $$('.parallax');
  if (parallax.length && !matchMedia('(prefers-reduced-motion: reduce)').matches && matchMedia('(hover: hover) and (min-width: 1025px)').matches) {
    var ticking = false;
    window.addEventListener('scroll', function () {
      if (ticking) return; ticking = true;
      requestAnimationFrame(function () {
        parallax.forEach(function (el) { el.style.transform = 'translate3d(0,' + (window.scrollY * parseFloat(el.dataset.speed || 0)) + 'px,0)'; });
        ticking = false;
      });
    }, { passive: true });
  }

  /* ---------- Toasts ---------- */
  function toast(msg, type) {
    var box = $('.toasts'); if (!box) return;
    var t = document.createElement('div');
    t.className = 'toast toast-' + (type || 'success');
    t.innerHTML = '<svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">' + (type === 'error' ? '<path d="M12 3 2 20h20L12 3Z"/><path d="M12 10v4M12 17v.5"/>' : '<path d="m5 12 5 5L20 7"/>') + '</svg><span></span>';
    t.querySelector('span').textContent = msg;
    box.appendChild(t); autoHide(t);
  }
  function autoHide(t) { setTimeout(function () { t.classList.add('out'); setTimeout(function () { t.remove(); }, 400); }, 4200); }
  $$('.toast').forEach(autoHide);

  /* ---------- Sheets (menu, cart, search) ---------- */
  var scrim = $('.scrim');
  var openSheet = null;
  function open(name) {
    close();
    var el = $('#sheet-' + name); if (!el) return;
    el.hidden = false; openSheet = el;
    if (name !== 'search' && scrim) scrim.hidden = false;
    document.documentElement.style.overflow = 'hidden';
    var input = $('input', el); if (name === 'search' && input) setTimeout(function () { input.focus(); }, 50);
  }
  function close() {
    if (openSheet) { openSheet.hidden = true; openSheet = null; }
    if (scrim) scrim.hidden = true;
    document.documentElement.style.overflow = '';
  }
  document.addEventListener('click', function (e) {
    var o = e.target.closest('[data-open]'); if (o) { e.preventDefault(); open(o.dataset.open); return; }
    if (e.target.closest('[data-close]') || e.target === scrim || e.target.id === 'sheet-search') close();
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') close();
    if (e.key === '/' && !/input|textarea|select/i.test(document.activeElement.tagName)) { e.preventDefault(); open('search'); }
  });

  /* ---------- Cart (AJAX) ---------- */
  function post(url, body) {
    return fetch(url, { method: 'POST', body: body, headers: { 'Accept': 'application/json', 'X-Requested-With': 'fetch', 'X-CSRF-Token': csrf }, credentials: 'same-origin' })
      .then(function (r) { return r.json().then(function (j) { j._status = r.status; return j; }); });
  }
  function setBadge(sel, n) { $$(sel).forEach(function (b) { b.textContent = n; b.hidden = !n; }); }
  function updateDrawer(html) { var d = $('[data-cart-drawer]'); if (d && html != null) d.innerHTML = html; }

  var addForm = $('[data-add-to-cart]');
  if (addForm) {
    addForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var btn = $('[data-add-btn]', addForm);
      if (btn.disabled) return;
      var label = $('[data-add-label]', addForm), old = label.textContent;
      btn.disabled = true; label.textContent = 'Adding…';
      post('/cart/add', new FormData(addForm)).then(function (res) {
        if (!res.ok) { toast(res.error || 'Could not add to cart.', 'error'); return; }
        setBadge('[data-cart-count]', res.count);
        updateDrawer(res.drawer);
        if (res.event) sendEvent('add_to_cart', res.event);
        label.textContent = 'Added ✓';
        open('cart');
      }).catch(function () { addForm.submit(); })
        .finally(function () { setTimeout(function () { btn.disabled = false; label.textContent = old === 'Adding…' ? 'Add to cart' : old; }, 900); });
    });
    $$('[data-step]', addForm).forEach(function (b) {
      b.addEventListener('click', function () {
        var i = $('input[name=qty]', addForm);
        i.value = Math.max(1, Math.min(50, (parseInt(i.value, 10) || 1) + parseInt(b.dataset.step, 10)));
      });
    });
  }
  document.addEventListener('submit', function (e) {
    var f = e.target.closest('[data-ajax-cart]'); if (!f) return;
    e.preventDefault();
    var fd = new FormData(f);
    if (e.submitter && e.submitter.name) fd.append(e.submitter.name, e.submitter.value);
    fd.append('_csrf', csrf);
    post('/cart/update', fd).then(function (res) { setBadge('[data-cart-count]', res.count); updateDrawer(res.drawer); });
  });

  /* ---------- Wishlist ---------- */
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-wish]'); if (!b) return;
    e.preventDefault();
    var fd = new FormData(); fd.append('product_id', b.dataset.wish); fd.append('_csrf', csrf);
    post('/wishlist/toggle', fd).then(function (res) {
      $$('[data-wish="' + b.dataset.wish + '"]').forEach(function (x) { x.classList.toggle('on', res.added); x.setAttribute('aria-pressed', res.added); x.classList.remove('pop'); void x.offsetWidth; x.classList.add('pop'); });
      setBadge('[data-wish-count]', res.count);
      toast(res.added ? 'Saved to your wishlist' : 'Removed from wishlist');
    });
  });

  /* ---------- Product page ---------- */
  var dataEl = $('#product-data');
  if (dataEl) {
    var P = JSON.parse(dataEl.textContent);
    var gallery = $('[data-gallery]');
    var slides = [], thumbs = [], current = 0;
    function collectGallery() { slides = $$('.slide', gallery); thumbs = $$('.thumb', gallery); }
    function show(i) {
      if (!slides.length) return;
      current = (i + slides.length) % slides.length;
      slides.forEach(function (s, k) { s.classList.toggle('on', k === current); s.classList.remove('zoom'); });
      thumbs.forEach(function (t, k) { t.classList.toggle('on', k === current); });
    }
    function setImages(list) {
      if (!gallery || !list.length) return;
      var main = $('.gallery-main', gallery);
      $$('.slide', main).forEach(function (s) { s.remove(); });
      list.forEach(function (img, i) {
        var f = document.createElement('figure'); f.className = 'slide' + (i === 0 ? ' on' : ''); f.dataset.slide = i;
        var im = document.createElement('img'); im.src = img.lg; im.alt = P.name; im.width = 1200; im.height = 1500;
        f.appendChild(im); main.insertBefore(f, $('.gal-nav', main));
      });
      var tw = $('.thumbs', gallery);
      if (tw) {
        tw.innerHTML = '';
        list.forEach(function (img, i) {
          var b = document.createElement('button'); b.className = 'thumb' + (i === 0 ? ' on' : ''); b.dataset.thumb = i; b.setAttribute('aria-label', 'Show image ' + (i + 1));
          b.innerHTML = '<img alt="" width="120" height="150">'; b.firstChild.src = img.sm; tw.appendChild(b);
        });
        tw.hidden = list.length < 2;
      }
      collectGallery(); show(0);
    }
    if (gallery) {
      collectGallery();
      gallery.addEventListener('click', function (e) {
        var t = e.target.closest('[data-thumb]'); if (t) { show(+t.dataset.thumb); return; }
        var n = e.target.closest('[data-gal]'); if (n) { show(current + +n.dataset.gal); return; }
        var s = e.target.closest('.slide'); if (s && matchMedia('(hover:hover)').matches) s.classList.toggle('zoom');
      });
      gallery.addEventListener('mousemove', function (e) {
        var s = $('.slide.zoom', gallery); if (!s) return;
        var r = s.getBoundingClientRect();
        s.firstElementChild.style.transformOrigin = ((e.clientX - r.left) / r.width * 100) + '% ' + ((e.clientY - r.top) / r.height * 100) + '%';
      });
      var sx = null;
      gallery.addEventListener('touchstart', function (e) { sx = e.touches[0].clientX; }, { passive: true });
      gallery.addEventListener('touchend', function (e) { if (sx === null) return; var dx = e.changedTouches[0].clientX - sx; if (Math.abs(dx) > 40) show(current + (dx < 0 ? 1 : -1)); sx = null; });
    }

    /* Wood theme: switch the page background when the box type changes. */
    function applyBox(id) {
      var vars = id && P.boxes[id];
      if (!vars) return;
      vars.split(';').forEach(function (pair) {
        var at = pair.indexOf(':'); if (at < 0) return;
        document.body.style.setProperty(pair.slice(0, at).trim(), pair.slice(at + 1).trim());
      });
      document.body.classList.add('has-wood');
    }

    var priceEl = $('[data-price]'), stickyPrice = $('[data-sticky-price]');
    var varInput = $('[data-variation-id]'), addBtn = $('[data-add-btn]'), addLabel = $('[data-add-label]'), descEl = $('[data-var-desc]');
    var lastImagesKey = null;
    function priceHtml(v) {
      if (v.price === null) return '<span class="price">Price on request</span>';
      return '<span class="price">' + (v.on_sale && v.regular > v.price ? '<del>' + money(v.regular) + '</del> ' : '') + '<ins>' + money(v.price) + '</ins></span>';
    }
    function selection() {
      var sel = {};
      $$('.option').forEach(function (fs) {
        var c = $('input:checked', fs); var name = $('input', fs).name.replace(/^attr\[|\]$/g, '');
        if (c) sel[name] = c.value;
        var lbl = $('[data-selected-label]', fs); if (lbl) lbl.textContent = c ? c.value : '';
      });
      return sel;
    }
    function match(sel) {
      return P.variations.find(function (v) {
        return Object.keys(v.attrs).every(function (k) { return v.attrs[k] === '' || v.attrs[k] === sel[k]; }) && Object.keys(sel).length >= Object.keys(v.attrs).length;
      });
    }
    function update() {
      if (!P.variable) return;
      var sel = selection(), v = match(sel);
      // Grey out options that don't exist with the current choice.
      $$('.option').forEach(function (fs) {
        var name = $('input', fs).name.replace(/^attr\[|\]$/g, '');
        $$('input', fs).forEach(function (inp) {
          var test = Object.assign({}, sel); test[name] = inp.value;
          var ok = P.variations.some(function (x) { return Object.keys(test).every(function (k) { return !(k in x.attrs) || x.attrs[k] === '' || x.attrs[k] === test[k]; }); });
          inp.closest('.swatch').classList.toggle('disabled', !ok);
        });
      });
      if (!v) {
        varInput.value = ''; addBtn.disabled = true; addLabel.textContent = 'Choose an option';
        return;
      }
      varInput.value = v.id;
      priceEl.innerHTML = priceHtml(v); if (stickyPrice) stickyPrice.innerHTML = priceHtml(v);
      var oos = v.stock === 'outofstock';
      addBtn.disabled = oos; addLabel.textContent = oos ? 'Sold out' : 'Add to cart';
      if (descEl) { descEl.textContent = v.desc || ''; descEl.hidden = !v.desc; }
      var imgs = v.images.length ? v.images.concat(P.baseImages.filter(function (b) { return !v.images.some(function (x) { return x.lg === b.lg; }); })) : P.baseImages;
      var key = imgs.map(function (i) { return i.lg; }).join('|');
      if (key !== lastImagesKey) { if (lastImagesKey !== null) setImages(imgs); lastImagesKey = key; }
      if (v.box) applyBox(v.box);
      history.replaceState(null, '', location.pathname + '?variation=' + v.id);
    }
    $$('.option input').forEach(function (i) { i.addEventListener('change', update); });
    var qsVar = new URLSearchParams(location.search).get('variation');
    if (qsVar) {
      var pre = P.variations.find(function (v) { return String(v.id) === qsVar; });
      if (pre) Object.keys(pre.attrs).forEach(function (k) {
        $$('.option input').forEach(function (inp) { if (inp.name === 'attr[' + k + ']' && inp.value === pre.attrs[k]) inp.checked = true; });
      });
    }
    update();

    /* Sticky add-to-cart bar once the main button scrolls away. */
    var sticky = $('[data-sticky-buy]');
    if (sticky && addBtn && 'IntersectionObserver' in window) {
      new IntersectionObserver(function (en) { sticky.hidden = en[0].isIntersecting || en[0].boundingClientRect.top > 0; }).observe(addBtn);
      $('[data-scroll-buy]', sticky).addEventListener('click', function () {
        if (addBtn.disabled) { addForm.scrollIntoView({ behavior: 'smooth', block: 'center' }); } else { addForm.requestSubmit ? addForm.requestSubmit() : addForm.submit(); }
      });
    }

    /* Pincode check */
    var pin = $('[data-pincode]');
    if (pin) pin.addEventListener('submit', function (e) {
      e.preventDefault();
      var msg = $('[data-pincode-msg]', pin), code = $('input', pin).value.trim();
      msg.className = 'small'; msg.textContent = 'Checking…';
      fetch('/pincode-check/?code=' + encodeURIComponent(code), { headers: { Accept: 'application/json' } }).then(function (r) { return r.json(); })
        .then(function (r) { msg.textContent = r.message; msg.className = 'small ' + (r.ok ? 'ok' : 'bad'); })
        .catch(function () { msg.textContent = 'Could not check right now.'; });
    });
  }

  /* ---------- Checkout ---------- */
  var checkout = $('[data-checkout]');
  if (checkout) {
    $$('[data-toggle]', checkout).forEach(function (cb) {
      var target = $(cb.dataset.toggle);
      var sync = function () { target.hidden = !cb.checked; $$('input,select', target).forEach(function (i) { if (i.name !== 'ship_address2' && i.name !== 'password') i.required = cb.checked; }); };
      cb.addEventListener('change', sync); sync();
    });
    var ta = $('[data-count]', checkout), counter = $('[data-counter]', checkout);
    if (ta && counter) { var c = function () { counter.textContent = ta.value.length + ' / ' + ta.maxLength; }; ta.addEventListener('input', c); c(); }
    var totalEl = $('[data-total]', checkout), codRow = $('[data-cod-fee]', checkout);
    var syncTotal = function () {
      var m = $('input[name=payment_method]:checked', checkout);
      var isCod = m && m.value === 'cod', base = parseFloat(totalEl.dataset.total), fee = parseFloat(totalEl.dataset.cod || 0);
      totalEl.textContent = money(base + (isCod ? fee : 0)); if (codRow) codRow.hidden = !isCod;
    };
    $$('input[name=payment_method]', checkout).forEach(function (r) { r.addEventListener('change', syncTotal); }); syncTotal();
    // Save email/phone as the customer types so we can send a reminder if they leave.
    var captured = '';
    $$('[data-capture]', checkout).forEach(function (i) {
      i.addEventListener('blur', function () {
        var email = $('input[name=email]', checkout).value.trim();
        if (!/^\S+@\S+\.\S+$/.test(email)) return;
        var key = email + $('input[name=phone]', checkout).value + $('input[name=name]', checkout).value;
        if (key === captured) return; captured = key;
        var fd = new FormData(); fd.append('email', email); fd.append('name', $('input[name=name]', checkout).value); fd.append('phone', $('input[name=phone]', checkout).value); fd.append('_csrf', csrf);
        navigator.sendBeacon ? navigator.sendBeacon('/checkout/capture', fd) : post('/checkout/capture', fd);
      });
    });
    checkout.addEventListener('submit', function () { var b = $('button[type=submit]', checkout); setTimeout(function () { b.disabled = true; b.textContent = 'Placing order…'; }, 0); });
  }

  /* ---------- Account tabs ---------- */
  $$('[data-tab]').forEach(function (t) {
    t.addEventListener('click', function () {
      $$('[data-tab]').forEach(function (x) { x.classList.toggle('on', x === t); });
      $$('[data-panel]').forEach(function (p) { p.hidden = p.dataset.panel !== t.dataset.tab; });
    });
  });

  /* ---------- Cookie note ---------- */
  var cookie = $('#cookie');
  try { if (cookie && !localStorage.getItem('tgb_cookie')) cookie.hidden = false; } catch (e) { }
  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-cookie]')) { try { localStorage.setItem('tgb_cookie', '1'); } catch (x) { } cookie.hidden = true; }
  });

  /* ---------- Installable app ---------- */
  if ('serviceWorker' in navigator && location.protocol === 'https:') {
    window.addEventListener('load', function () { navigator.serviceWorker.register('/sw.js').catch(function () { }); });
  }
})();
