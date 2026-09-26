/* The Gift Boxx Admin — interactions. Plain JavaScript, no build step. */
(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var csrf = (window.ADMIN || {}).csrf;
  var esc = function (s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; };
  var money = function (n) { return '₹' + Number(n || 0).toLocaleString('en-IN', { maximumFractionDigits: 2 }); };
  var ICON = {
    x: '<svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>',
    plus: '<svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>',
    chev: '<svg class="i chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="m6 9 6 6 6-6"/></svg>',
    check: '<svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="m5 12 5 5L20 7"/></svg>',
    alert: '<svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M12 3 2 20h20L12 3Z"/><path d="M12 10v4M12 17v.5"/></svg>'
  };

  /* ---------- Toasts ---------- */
  function hideLater(t) { setTimeout(function () { t.classList.add('out'); setTimeout(function () { t.remove(); }, 400); }, 4500); }
  $$('.toast').forEach(hideLater);
  function toast(msg, ok) {
    var box = $('.toasts'); if (!box) return;
    var t = document.createElement('div'); t.className = 'toast' + (ok === false ? ' t-error' : '');
    t.innerHTML = (ok === false ? ICON.alert : ICON.check) + '<span>' + esc(msg) + '</span>';
    box.appendChild(t); hideLater(t);
  }
  window.adminToast = toast;

  function post(url, data) {
    var fd = data instanceof FormData ? data : new FormData();
    if (!(data instanceof FormData) && data) Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
    fd.append('_csrf', csrf);
    return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin', headers: { Accept: 'application/json' } })
      .then(function (r) { return r.json().catch(function () { return { ok: false, error: 'Server error (' + r.status + ')' }; }); });
  }

  /* ---------- Chrome: sidebar, topbar, theme ---------- */
  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-open-side]')) document.body.classList.add('side-open');
    if (e.target.closest('[data-close-side]')) document.body.classList.remove('side-open');
  });
  var topbar = $('.topbar');
  var onScroll = function () { if (topbar) topbar.classList.toggle('scrolled', window.scrollY > 30); };
  window.addEventListener('scroll', onScroll, { passive: true }); onScroll();
  try { localStorage.removeItem('admin_theme'); } catch (e) { }
  document.addEventListener('click', function (e) {
    if (!e.target.closest('[data-theme-toggle]')) return;
    var root = document.documentElement, cur = root.dataset.theme;
    var isDark = cur === 'dark' || (cur !== 'light' && matchMedia('(prefers-color-scheme: dark)').matches);
    root.dataset.theme = isDark ? 'light' : 'dark';
    try { localStorage.setItem('admin_theme', root.dataset.theme); } catch (x) { }
  });

  /* ---------- Clickable rows, confirms ---------- */
  document.addEventListener('click', function (e) {
    var tr = e.target.closest('tr[data-href]');
    if (tr && !e.target.closest('a,button,input,label,select')) { location.href = tr.dataset.href; }
  });
  document.addEventListener('submit', function (e) {
    var f = e.target;
    var msg = f.dataset.confirm || (e.submitter && e.submitter.dataset.confirm);
    if (msg && !confirm(msg)) { e.preventDefault(); return; }
    if (f.method.toLowerCase() === 'post' && !f.dataset.noLock) {
      var b = e.submitter || $('button[type=submit],button:not([type])', f);
      if (b) setTimeout(function () { b.disabled = true; }, 0);
      window.__saving = true;
    }
  });

  /* ---------- Save without breaking the Back button ---------- */
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (e.defaultPrevented || (f.method || '').toLowerCase() !== 'post' || f.hasAttribute('data-native') || f.target) return;
    e.preventDefault();
    var fd = new FormData(f);
    if (e.submitter && e.submitter.name) fd.append(e.submitter.name, e.submitter.value);
    fetch(f.getAttribute('action') || location.href, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-Admin-Fetch': '1', 'Accept': 'application/json' } })
      .then(function (r) { return r.text(); })
      .then(function (t) {
        var j = null; try { j = JSON.parse(t); } catch (x) { }
        window.__saving = true;
        if (j && j.redirect) { location.replace(j.redirect); return; }
        if (j && j.ok) { location.reload(); return; }
        document.open(); document.write(t); document.close();
      })
      .catch(function () { f.setAttribute('data-native', ''); HTMLFormElement.prototype.submit.call(f); });
  });

  /* ---------- Command palette (⌘K) ---------- */
  var cmdk = $('#cmdk');
  if (cmdk) {
    var pages = JSON.parse(($('#cmdk-pages') || {}).textContent || '[]');
    var input = $('input', cmdk), results = $('.cmdk-results', cmdk), sel = 0, timer = null, items = [];
    var openCmd = function () { cmdk.hidden = false; input.value = ''; render(pages.slice(0, 8)); setTimeout(function () { input.focus(); }, 10); };
    var closeCmd = function () { cmdk.hidden = true; };
    function render(list) {
      items = list; sel = 0;
      results.innerHTML = list.length ? list.map(function (r, i) {
        return '<a href="' + esc(r.url) + '" class="' + (i === 0 ? 'sel' : '') + '"><span>' + esc(r.title) + (r.sub ? '<small>' + esc(r.sub) + '</small>' : '') + '</span><em>' + esc(r.type || '') + '</em></a>';
      }).join('') : '<p>No results</p>';
    }
    input.addEventListener('input', function () {
      var q = input.value.trim().toLowerCase();
      var local = pages.filter(function (p) { return p.title.toLowerCase().indexOf(q) > -1; });
      render(local.slice(0, 6));
      clearTimeout(timer);
      if (q.length < 2) return;
      timer = setTimeout(function () {
        fetch('/search?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } }).then(function (r) { return r.json(); })
          .then(function (d) { if (input.value.trim().toLowerCase() === q) render(local.slice(0, 4).concat(d.results || [])); });
      }, 160);
    });
    input.addEventListener('keydown', function (e) {
      var links = $$('a', results);
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault(); if (!links.length) return;
        sel = (sel + (e.key === 'ArrowDown' ? 1 : -1) + links.length) % links.length;
        links.forEach(function (a, i) { a.classList.toggle('sel', i === sel); }); links[sel].scrollIntoView({ block: 'nearest' });
      } else if (e.key === 'Enter' && links[sel]) { location.href = links[sel].getAttribute('href'); }
    });
    document.addEventListener('keydown', function (e) {
      if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); cmdk.hidden ? openCmd() : closeCmd(); }
      if (e.key === 'Escape') closeCmd();
    });
    document.addEventListener('click', function (e) {
      if (e.target.closest('[data-cmdk]')) { e.preventDefault(); openCmd(); }
      else if (e.target === cmdk) closeCmd();
    });
  }

  /* ---------- Save bar: appears when a form has unsaved changes ---------- */
  $$('form[data-savebar]').forEach(function (form) {
    var bar = document.createElement('div');
    bar.className = 'savebar';
    bar.innerHTML = '<span>Unsaved changes</span><button type="button" class="btn secondary sm" data-discard>Discard</button><button type="button" class="btn sm" data-save>Save</button>';
    document.body.appendChild(bar);
    var dirty = false;
    var mark = function () { if (!dirty) { dirty = true; bar.classList.add('show'); } };
    form.addEventListener('input', mark); form.addEventListener('change', mark);
    form.addEventListener('tgb:dirty', mark);
    $('[data-save]', bar).onclick = function () { form.requestSubmit ? form.requestSubmit() : form.submit(); };
    $('[data-discard]', bar).onclick = function () { dirty = false; location.reload(); };
    form.addEventListener('submit', function () { dirty = false; });
    window.addEventListener('beforeunload', function (e) { if (dirty && !window.__saving) { e.preventDefault(); e.returnValue = ''; } });
    document.addEventListener('keydown', function (e) { if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 's') { e.preventDefault(); $('[data-save]', bar).click(); } });
  });
  function dirty(el) { var f = el.closest('form'); if (f) f.dispatchEvent(new Event('tgb:dirty')); }

  /* ---------- Rich text editor ---------- */
  $$('textarea[data-rte]').forEach(function (ta) {
    var wrap = document.createElement('div'); wrap.className = 'rte-wrap';
    var bar = document.createElement('div'); bar.className = 'rte-bar';
    var tools = [['formatBlock:H2', 'H2', 'Heading'], ['formatBlock:H3', 'H3', 'Subheading'], ['formatBlock:P', '¶', 'Paragraph'], ['|'],
      ['bold', '<b>B</b>', 'Bold'], ['italic', '<i>I</i>', 'Italic'], ['underline', '<u>U</u>', 'Underline'], ['|'],
      ['insertUnorderedList', '• List', 'Bullets'], ['insertOrderedList', '1. List', 'Numbers'], ['formatBlock:BLOCKQUOTE', '❝', 'Quote'], ['|'],
      ['link', 'Link', 'Add link'], ['unlink', 'Unlink', 'Remove link'], ['removeFormat', 'Clear', 'Clear formatting'], ['|'], ['source', '&lt;/&gt;', 'Edit HTML']];
    bar.innerHTML = tools.map(function (t) { return t[0] === '|' ? '<span class="sep"></span>' : '<button type="button" data-cmd="' + t[0] + '" title="' + t[2] + '">' + t[1] + '</button>'; }).join('');
    var area = document.createElement('div'); area.className = 'rte-area' + (ta.rows && ta.rows < 5 ? ' short' : ''); area.contentEditable = 'true';
    area.innerHTML = ta.value; area.dataset.placeholder = ta.placeholder || 'Start writing…';
    ta.classList.add('rte-source');
    ta.parentNode.insertBefore(wrap, ta); wrap.appendChild(bar); wrap.appendChild(area); wrap.appendChild(ta);
    var sync = function () { ta.value = area.innerHTML.replace(/<br>$/, ''); ta.dispatchEvent(new Event('input', { bubbles: true })); };
    area.addEventListener('input', sync);
    area.addEventListener('paste', function (e) {
      // Keep pasted text clean (no Word/Google Docs styling).
      var html = e.clipboardData.getData('text/html'); var text = e.clipboardData.getData('text/plain');
      e.preventDefault();
      if (html) {
        var tmp = document.createElement('div'); tmp.innerHTML = html;
        $$('*', tmp).forEach(function (n) { n.removeAttribute('style'); n.removeAttribute('class'); n.removeAttribute('id'); if (/^(SPAN|FONT|META|STYLE|SCRIPT|O:P)$/i.test(n.tagName)) { if (/STYLE|SCRIPT|META/i.test(n.tagName)) n.remove(); else n.replaceWith.apply(n, Array.prototype.slice.call(n.childNodes)); } });
        document.execCommand('insertHTML', false, tmp.innerHTML);
      } else {
        document.execCommand('insertHTML', false, esc(text).replace(/\n\n+/g, '</p><p>').replace(/\n/g, '<br>'));
      }
      sync();
    });
    bar.addEventListener('mousedown', function (e) { if (e.target.closest('button')) e.preventDefault(); });
    bar.addEventListener('click', function (e) {
      var b = e.target.closest('[data-cmd]'); if (!b) return;
      var cmd = b.dataset.cmd;
      if (cmd === 'source') {
        if (wrap.classList.toggle('source')) { ta.value = area.innerHTML; ta.focus(); } else { area.innerHTML = ta.value; }
        b.classList.toggle('on'); return;
      }
      area.focus();
      if (cmd === 'link') { var u = prompt('Link address (https://…)'); if (u) document.execCommand('createLink', false, u); }
      else if (cmd.indexOf('formatBlock:') === 0) document.execCommand('formatBlock', false, cmd.split(':')[1]);
      else document.execCommand(cmd, false, null);
      sync();
    });
    ta.addEventListener('input', function () { if (wrap.classList.contains('source')) area.innerHTML = ta.value; });
  });

  /* ---------- Image uploads ---------- */
  function upload(files, onDone, tile) {
    if (!files.length) return;
    var fd = new FormData(); Array.prototype.forEach.call(files, function (f) { fd.append('file[]', f); });
    if (tile) { tile.classList.add('uploading'); tile.dataset.label = tile.innerHTML; tile.innerHTML = 'Uploading…'; }
    post('/upload', fd).then(function (res) {
      if (!res.ok) { toast(res.error || 'Upload failed', false); return; }
      res.files.forEach(onDone);
    }).catch(function () { toast('Upload failed — check your connection.', false); })
      .finally(function () { if (tile) { tile.classList.remove('uploading'); tile.innerHTML = tile.dataset.label; } });
  }
  window.adminUpload = upload;

  // Single image field: <div data-image-field data-name="hero_image"> with hidden input + preview
  function initImageField(box) {
    if (box.dataset.ready) return; box.dataset.ready = 1;
    var inp = $('input[type=hidden]', box), prev = $('.preview', box), file = $('input[type=file]', box), rm = $('[data-remove]', box);
    file.addEventListener('change', function () {
      upload(file.files, function (f) { inp.value = f.path; prev.src = f.url; prev.hidden = false; if (rm) rm.hidden = false; dirty(box); box.dispatchEvent(new CustomEvent('image:change', { bubbles: true, detail: f })); });
      file.value = '';
    });
    if (rm) rm.addEventListener('click', function () { inp.value = ''; prev.hidden = true; rm.hidden = true; dirty(box); });
  }
  $$('[data-image-field]').forEach(initImageField);

  // Gallery: sortable, multiple uploads, drag & drop
  $$('[data-gallery]').forEach(function (g) {
    var grid = $('.gallery-grid', g), addTile = $('.add-tile', g), file = $('input[type=file]', g), name = g.dataset.gallery;
    function tile(path, url, alt) {
      var d = document.createElement('div'); d.className = 'gimg'; d.draggable = true;
      d.innerHTML = '<img alt=""><button type="button" class="x" aria-label="Remove">' + ICON.x + '</button><input type="hidden"><input type="hidden">';
      d.querySelector('img').src = url; var hs = d.querySelectorAll('input');
      hs[0].name = name + '[]'; hs[0].value = path; hs[1].name = name.replace('images', 'image_alt') + '[]'; hs[1].value = alt || '';
      grid.insertBefore(d, addTile); return d;
    }
    addTile.addEventListener('click', function () { file.click(); });
    file.addEventListener('change', function () { upload(file.files, function (f) { tile(f.path, f.url); dirty(g); g.dispatchEvent(new Event('gallery:change', { bubbles: true })); }, addTile); file.value = ''; });
    grid.addEventListener('click', function (e) { var x = e.target.closest('.x'); if (x) { x.parentNode.remove(); dirty(g); g.dispatchEvent(new Event('gallery:change', { bubbles: true })); } });
    var dragEl = null;
    grid.addEventListener('dragstart', function (e) { dragEl = e.target.closest('.gimg'); if (dragEl) { dragEl.classList.add('dragging'); e.dataTransfer.effectAllowed = 'move'; } });
    grid.addEventListener('dragend', function () { if (dragEl) dragEl.classList.remove('dragging'); dragEl = null; dirty(g); g.dispatchEvent(new Event('gallery:change', { bubbles: true })); });
    grid.addEventListener('dragover', function (e) {
      e.preventDefault();
      if (dragEl) { var over = e.target.closest('.gimg'); if (over && over !== dragEl) { var r = over.getBoundingClientRect(); grid.insertBefore(dragEl, (e.clientX - r.left) > r.width / 2 ? over.nextSibling : over); } }
      else g.classList.add('drag');
    });
    g.addEventListener('dragleave', function (e) { if (!g.contains(e.relatedTarget)) g.classList.remove('drag'); });
    g.addEventListener('drop', function (e) {
      g.classList.remove('drag');
      if (!dragEl && e.dataTransfer.files.length) { e.preventDefault(); upload(e.dataTransfer.files, function (f) { tile(f.path, f.url); dirty(g); g.dispatchEvent(new Event('gallery:change', { bubbles: true })); }, addTile); }
    });
  });

  /* ---------- Colour fields ---------- */
  $$('.color-field').forEach(function (c) {
    var inp = $('input[type=color]', c), code = $('code', c);
    inp.addEventListener('input', function () { code.textContent = inp.value.toUpperCase(); });
  });

  /* ---------- Character counters ---------- */
  $$('[data-count]').forEach(function (el) {
    var max = +el.dataset.count, c = document.createElement('span'); c.className = 'counter';
    var lab = el.closest('label'); (lab || el.parentNode).insertBefore(c, lab ? lab.firstChild.nextSibling : el);
    var upd = function () { c.textContent = el.value.length + ' / ' + max; c.classList.toggle('over', el.value.length > max); };
    el.addEventListener('input', upd); upd();
  });

  /* ---------- Bulk select ---------- */
  var all = $('[data-select-all]');
  if (all) {
    var bulk = $('.bulkbar');
    var boxes = function () { return $$('input[name="ids[]"]'); };
    var sync = function () { var n = boxes().filter(function (b) { return b.checked; }).length; if (bulk) { bulk.hidden = !n; $('[data-count-sel]', bulk).textContent = n + ' selected'; } };
    all.addEventListener('change', function () { boxes().forEach(function (b) { b.checked = all.checked; }); sync(); });
    document.addEventListener('change', function (e) { if (e.target.name === 'ids[]') sync(); });
  }

  /* ---------- Connection tests ---------- */
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-test]'); if (!b) return;
    e.preventDefault();
    var label = b.innerHTML; b.disabled = true; b.textContent = 'Testing…';
    post(b.dataset.test, {}).then(function (r) { toast(r.message || (r.ok ? 'Works!' : 'Failed'), !!r.ok); })
      .finally(function () { b.disabled = false; b.innerHTML = label; });
  });

  /* ---------- Copy buttons ---------- */
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-copy]'); if (!b) return;
    navigator.clipboard.writeText(b.dataset.copy).then(function () { toast('Copied to clipboard'); });
  });

  /* ---------- Toggle blocks (e.g. sale schedule, stock qty) ---------- */
  $$('[data-show-if]').forEach(function (el) {
    var src = $(el.dataset.showIf);
    if (!src) return;
    var upd = function () { el.hidden = src.type === 'checkbox' ? !src.checked : (el.dataset.showValue ? src.value !== el.dataset.showValue : !src.value); };
    src.addEventListener('change', upd); upd();
  });

  /* ---------- Chart tooltip ---------- */
  $$('.chart-wrap').forEach(function (w) {
    var svg = $('svg', w), tip = $('.chart-tip', w), pts = JSON.parse(w.dataset.points || '[]');
    if (!svg || !pts.length) return;
    var dots = $$('.dot', svg);
    svg.addEventListener('mousemove', function (e) {
      var r = svg.getBoundingClientRect(), x = (e.clientX - r.left) / r.width;
      var i = Math.max(0, Math.min(pts.length - 1, Math.round(x * (pts.length - 1))));
      dots.forEach(function (d, k) { d.style.opacity = k === i ? 1 : 0; });
      tip.innerHTML = '<strong>' + money(pts[i].v) + '</strong>' + esc(pts[i].l);
      tip.style.left = (i / (pts.length - 1) * 100) + '%'; tip.style.top = (+dots[i].getAttribute('cy') / 220 * r.height) + 'px'; tip.style.opacity = 1;
    });
    svg.addEventListener('mouseleave', function () { tip.style.opacity = 0; dots.forEach(function (d) { d.style.opacity = 0; }); });
  });

  /* ---------- Product editor ---------- */
  var pf = $('#product-form');
  if (pf) {
    var typeInputs = $$('input[name=type]', pf);
    var attrBox = $('#attributes'), varBox = $('#variations'), simpleBox = $$('[data-simple-only]'), varOnly = $$('[data-variable-only]');
    function isVariable() { var c = $('input[name=type]:checked', pf); return c && c.value === 'variable'; }
    function syncType() { simpleBox.forEach(function (el) { el.hidden = isVariable(); }); varOnly.forEach(function (el) { el.hidden = !isVariable(); }); }
    typeInputs.forEach(function (i) { i.addEventListener('change', syncType); }); syncType();

    // Tag-style inputs for option values
    function tagify(hidden) {
      if (hidden.dataset.tagified) return; hidden.dataset.tagified = 1;
      var box = document.createElement('div'); box.className = 'tag-input';
      var inp = document.createElement('input'); inp.placeholder = hidden.placeholder || 'Type and press Enter';
      box.appendChild(inp); hidden.parentNode.insertBefore(box, hidden); hidden.type = 'hidden';
      var values = hidden.value ? hidden.value.split(',').map(function (s) { return s.trim(); }).filter(Boolean) : [];
      function draw() {
        $$('.tag', box).forEach(function (t) { t.remove(); });
        values.forEach(function (v, i) { var t = document.createElement('span'); t.className = 'tag'; t.innerHTML = esc(v) + '<button type="button" aria-label="Remove">' + ICON.x + '</button>'; t.querySelector('button').onclick = function () { values.splice(i, 1); draw(); change(); }; box.insertBefore(t, inp); });
        hidden.value = values.join(', ');
      }
      function change() { hidden.dispatchEvent(new Event('change', { bubbles: true })); dirty(hidden); }
      function add() {
        var before = values.length;
        inp.value.split(',').map(function (s) { return s.trim(); }).filter(Boolean).forEach(function (v) { if (values.indexOf(v) < 0) values.push(v); });
        inp.value = '';
        if (values.length !== before) { draw(); change(); }
      }
      inp.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ',') { e.preventDefault(); add(); } else if (e.key === 'Backspace' && !inp.value && values.length) { values.pop(); draw(); change(); } });
      inp.addEventListener('blur', add);
      box.addEventListener('click', function () { inp.focus(); });
      draw();
    }
    $$('[data-tags]', pf).forEach(tagify);

    // Attributes (options) rows
    var attrTpl = $('#attr-row-tpl');
    $('#add-attr') && $('#add-attr').addEventListener('click', function () {
      var row = attrTpl.content.firstElementChild.cloneNode(true);
      var idx = $$('.attr-row', attrBox).length;
      $$('[name]', row).forEach(function (n) { n.name = n.name.replace('__i__', idx); });
      attrBox.appendChild(row); $$('[data-tags]', row).forEach(tagify); $('input', row).focus();
    });
    attrBox && attrBox.addEventListener('click', function (e) { var r = e.target.closest('[data-remove-attr]'); if (r) { r.closest('.attr-row').remove(); dirty(attrBox); } });

    function currentAttrs() {
      return $$('.attr-row', attrBox).map(function (row) {
        var name = $('input[name^=attr_name]', row).value.trim();
        var opts = $('input[name^=attr_options]', row).value.split(',').map(function (s) { return s.trim(); }).filter(Boolean);
        var info = $('input[name^=attr_info]', row);
        return { name: name, options: opts, info: info && info.checked };
      }).filter(function (a) { return a.name && a.options.length && !a.info; });
    }

    // Variations
    var varTpl = $('#var-tpl'), varList = $('#var-list');
    var vCount = $$('.var-card', varList).length;
    function varTitle(card) {
      var parts = $$('.var-attr', card).map(function (s) { return s.value; }).filter(Boolean);
      $('.grow', card).textContent = parts.join(' · ') || 'New option';
      var price = $('[name$="[regular_price]"]', card).value, sale = $('[name$="[sale_price]"]', card).value;
      $('.var-price', card).textContent = price ? (sale ? money(sale) + ' (sale)' : money(price)) : 'No price';
      var en = $('[name$="[enabled]"]', card); card.style.opacity = en && !en.checked ? .55 : 1;
    }
    function newVar(attrs) {
      var html = varTpl.innerHTML.replace(/__v__/g, 'n' + (vCount++));
      var wrap = document.createElement('div'); wrap.innerHTML = html.trim();
      var card = wrap.firstElementChild;
      var sel = $('.var-attrs', card);
      currentAttrs().forEach(function (a) {
        var l = document.createElement('label'); l.className = 'f';
        l.innerHTML = esc(a.name) + '<select class="var-attr"></select>';
        var s = $('select', l); s.name = card.dataset.prefix + '[attrs][' + a.name + ']';
        s.innerHTML = a.options.map(function (o) { return '<option' + (attrs && attrs[a.name] === o ? ' selected' : '') + '>' + esc(o) + '</option>'; }).join('');
        sel.appendChild(l);
      });
      varList.appendChild(card); $$('[data-image-field]', card).forEach(initImageField); initVar(card); varTitle(card); dirty(varList);
      return card;
    }
    function initVar(card) {
      $('.var-top', card).addEventListener('click', function (e) { if (!e.target.closest('button,input,label')) card.classList.toggle('open'); });
      card.addEventListener('input', function () { varTitle(card); });
      card.addEventListener('change', function () { varTitle(card); });
      var rm = $('[data-remove-var]', card); rm && rm.addEventListener('click', function () { if (confirm('Remove this option?')) { card.remove(); dirty(varList); } });
      card.addEventListener('image:change', function (e) { var img = $('.var-img', card); img.src = e.detail.url; });
    }
    $$('.var-card', varList).forEach(function (c) { initVar(c); varTitle(c); });
    $('#add-var') && $('#add-var').addEventListener('click', function () {
      if (!currentAttrs().length) { toast('First add an option name and its values above (e.g. Box Type: Pinewood, Teakwood).', false); return; }
      newVar().classList.add('open');
    });
    $('#gen-vars') && $('#gen-vars').addEventListener('click', function () {
      var attrs = currentAttrs();
      if (!attrs.length) { toast('Add options above first (e.g. Box Type: Pinewood, Teakwood, Plywood).', false); return; }
      var combos = [{}];
      attrs.forEach(function (a) { var next = []; combos.forEach(function (c) { a.options.forEach(function (o) { var n = Object.assign({}, c); n[a.name] = o; next.push(n); }); }); combos = next; });
      var existing = $$('.var-card', varList).map(function (card) { var o = {}; $$('.var-attr', card).forEach(function (s) { o[s.name.match(/\[attrs\]\[(.*)\]$/)[1]] = s.value; }); return JSON.stringify(o); });
      var added = 0;
      combos.forEach(function (c) { if (existing.indexOf(JSON.stringify(c)) < 0) { newVar(c); added++; } });
      toast(added ? added + ' option' + (added > 1 ? 's' : '') + ' created — add a price to each.' : 'All combinations already exist.');
    });
    $('#bulk-price') && $('#bulk-price').addEventListener('click', function () {
      var v = prompt('Set this regular price (₹) for every option:'); if (!v) return;
      $$('[name$="[regular_price]"]', varList).forEach(function (i) { i.value = v; i.dispatchEvent(new Event('input', { bubbles: true })); });
    });

    // Slug from name
    var nameIn = $('input[name=name]', pf), slugIn = $('input[name=slug]', pf);
    if (nameIn && slugIn && !slugIn.value) {
      nameIn.addEventListener('input', function () { if (!slugIn.dataset.touched) slugIn.value = nameIn.value.toLowerCase().replace(/[’']/g, '').replace(/&/g, 'and').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''); preview(); });
      slugIn.addEventListener('input', function () { slugIn.dataset.touched = 1; });
    }

    // Live Google search + Shopping preview
    var prevBox = $('#previews');
    function preview() {
      if (!prevBox) return;
      var name = nameIn.value || 'Product name';
      $('.shop-card .t', prevBox).textContent = name;
      var prices = isVariable() ? $$('[name$="[regular_price]"]', varList).map(function (i) { return parseFloat(i.value); }).filter(function (n) { return n > 0; }) : [parseFloat($('[name=regular_price]', pf).value)];
      var sale = !isVariable() ? parseFloat($('[name=sale_price]', pf).value) : NaN;
      var min = prices.length ? Math.min.apply(null, prices) : NaN;
      $('.shop-card .p', prevBox).innerHTML = isNaN(min) ? 'Price missing' : (sale > 0 && sale < min ? money(sale) + '<del>' + money(min) + '</del>' : money(min));
      var first = $('.gimg img', pf);
      $('.shop-card img', prevBox).src = first ? first.src : prevBox.dataset.placeholder;
    }
    pf.addEventListener('input', preview); pf.addEventListener('change', preview); pf.addEventListener('gallery:change', preview);
    preview();

    // Custom colour toggle preview
    var cc = $('#use_custom_color');
    if (cc) { var ccBox = $('#custom-colors'); var upd = function () { ccBox.hidden = !cc.checked; }; cc.addEventListener('change', upd); upd(); }
  }

  /* ---------- Box type live preview ---------- */
  var bt = $('#boxtype-form');
  if (bt) {
    var sw = $('#bt-preview');
    var upd = function () {
      sw.style.background = $('[name=bg_color]', bt).value; sw.style.color = $('[name=text_color]', bt).value;
      $('.pillx', sw).style.background = $('[name=accent_color]', bt).value;
      $('strong', sw).textContent = $('[name=name]', bt).value || 'Box type';
      sw.classList.toggle('grain', $('[name=grain]', bt).checked);
    };
    bt.addEventListener('input', upd); bt.addEventListener('change', upd); upd();
  }

  /* ---------- Appearance live preview ---------- */
  var ap = $('#appearance-preview');
  if (ap) {
    var form = ap.closest('.grid').querySelector('form');
    var sync = function () {
      var get = function (n) { var el = form.querySelector('[name=' + n + ']'); return el ? el.value : ''; };
      ap.style.setProperty('--p-bg', get('theme_bg')); ap.style.setProperty('--p-surface', get('theme_surface'));
      ap.style.setProperty('--p-ink', get('theme_text')); ap.style.setProperty('--p-accent', get('theme_accent')); ap.style.setProperty('--p-dark', get('theme_dark'));
      ap.style.setProperty('--p-radius', get('theme_radius') + 'px');
      ap.style.setProperty('--p-head', "'" + get('theme_heading_font') + "', Georgia, serif"); ap.style.setProperty('--p-body', "'" + get('theme_body_font') + "', sans-serif");
      var fonts = [get('theme_heading_font'), get('theme_body_font')].filter(function (v, i, a) { return v && a.indexOf(v) === i; });
      var id = 'preview-fonts', link = document.getElementById(id) || document.head.appendChild(Object.assign(document.createElement('link'), { id: id, rel: 'stylesheet' }));
      link.href = 'https://fonts.googleapis.com/css2?' + fonts.map(function (f) { return 'family=' + f.replace(/ /g, '+'); }).join('&') + '&display=swap';
      if (get('admin_accent')) document.documentElement.style.setProperty('--accent', get('admin_accent'));
    };
    form.addEventListener('input', sync); form.addEventListener('change', sync); sync();
  }

  /* ---------- WooCommerce migration runner ---------- */
  var runner = $('#woo-runner');
  if (runner && runner.dataset.autostart === '1') {
    var logEl = $('#woo-log'), stepEl = $('#woo-step'), bar = $('#woo-bar');
    var steps = ['categories', 'brands', 'products', 'links', 'reviews', 'customers', 'orders', 'done'];
    var tick = function () {
      post('/import/woo/step', {}).then(function (s) {
        logEl.textContent = (s.log || []).slice(-40).join('\n'); logEl.scrollTop = logEl.scrollHeight;
        var i = steps.indexOf(s.step); bar.style.width = Math.round(i / (steps.length - 1) * 100) + '%';
        stepEl.textContent = s.step === 'done' ? 'Finished' : 'Importing ' + s.step + (s.page > 1 ? ' (page ' + s.page + ')' : '') + '…';
        if (s.error) { stepEl.textContent = 'Paused: ' + s.error; $('#woo-retry').hidden = false; return; }
        if (s.step !== 'done') setTimeout(tick, 700); else toast('Migration finished!');
      }).catch(function () { stepEl.textContent = 'Connection lost — press Retry.'; $('#woo-retry').hidden = false; });
    };
    $('#woo-retry') && $('#woo-retry').addEventListener('click', function () { this.hidden = true; tick(); });
    tick();
  }

  /* ---------- SEO panel (focus keyword score, SERP + social previews) ---------- */
  $$('[data-seo]').forEach(function (box) {
    var form = box.closest('form'); if (!form) return;
    var d = box.dataset, kind = d.kind;
    var f = function (n) { return form.querySelector('[name="' + n + '"]'); };
    var val = function (n) { var el = f(n); return el ? el.value.trim() : ''; };
    var plain = function (html) { var t = document.createElement('div'); t.innerHTML = html; return (t.textContent || '').replace(/\s+/g, ' ').trim(); };
    var norm = function (t) { return t.toLowerCase().replace(/[’']/g, '').replace(/[^a-z0-9ऀ-ॿ]+/g, ' ').trim(); };
    var has = function (hay, kw) { return kw && (' ' + norm(hay) + ' ').indexOf(' ' + norm(kw) + ' ') > -1; };
    var trunc = function (t, n) { return t.length > n ? t.slice(0, n - 1).trim() + '…' : t; };
    var minWords = { product: 150, category: 80, page: 300, post: 600 }[kind] || 200;

    $$('[data-seo-tab]', box).forEach(function (b) {
      b.addEventListener('click', function () {
        $$('[data-seo-tab]', box).forEach(function (x) { x.classList.toggle('on', x === b); });
        $$('.seo-pane', box).forEach(function (p) { p.hidden = p.dataset.pane !== b.dataset.seoTab; });
      });
    });
    $$('[data-serp]', box).forEach(function (b) {
      b.addEventListener('click', function () {
        $$('[data-serp]', box).forEach(function (x) { x.classList.toggle('on', x === b); });
        $('[data-serp-box]', box).classList.toggle('mobile', b.dataset.serp === 'mobile');
      });
    });

    function mainImage() {
      var og = f('og_image'); if (og && og.value) { var pv = og.parentNode.querySelector('img.preview'); if (pv) return pv.src; }
      var g = $('.gimg img', form); if (g) return g.src;
      var c = f('cover_image') || f('image'); if (c && c.value) { var p2 = c.parentNode.querySelector('img.preview'); if (p2) return p2.src; }
      return '';
    }

    function run() {
      var name = val(d.nameField), kw = val('focus_keyword');
      var bodyHtml = (f(d.bodyField) || { value: '' }).value;
      var body = plain(bodyHtml);
      if (kind === 'product') body = plain(val('short_description')) + ' ' + body;
      var title = val('seo_title') || name || 'Title';
      var fullTitle = title.indexOf(d.store) > -1 ? title : title + ' ' + d.sep + ' ' + d.store;
      var desc = val('seo_description') || trunc(kind === 'post' ? (val('excerpt') || body) : body, 160);
      var slug = val('slug') || norm(name).replace(/ /g, '-');
      var url = d.host + d.base + slug + (slug ? '/' : '');

      var serp = $('[data-serp-box]', box);
      $('.t', serp).textContent = trunc(fullTitle, 62);
      $('.d', serp).textContent = trunc(desc, 158) || 'Add a meta description so Google shows something useful here.';
      $('.site small', serp).textContent = 'https://' + url.replace(/\/$/, '').split('/').join(' › ');

      var og = $('[data-og-card]', box), img = mainImage();
      $('.t', og).textContent = val('og_title') || title;
      $('.d', og).textContent = trunc(val('og_description') || desc, 110);
      $('img', og).hidden = !img; if (img) $('img', og).src = img;

      var words = body ? body.split(/\s+/).length : 0;
      var kwWords = kw ? norm(kw).split(' ').length : 1;
      var count = 0;
      if (kw) { var hay = ' ' + norm(body) + ' ', k = ' ' + norm(kw) + ' ', i = 0; while ((i = hay.indexOf(k, i)) > -1) { count++; i += k.length - 1; } }
      var density = words ? count * kwWords / words * 100 : 0;
      var first = body.split(/\s+/).slice(0, Math.max(30, Math.round(words * 0.1))).join(' ');
      var tmp = document.createElement('div'); tmp.innerHTML = bodyHtml;
      var heads = $$('h2,h3,h4', tmp).map(function (h) { return h.textContent; }).join(' | ');
      var links = $$('a[href]', tmp).map(function (a) { return a.getAttribute('href'); });
      var internal = links.some(function (h) { return h.charAt(0) === '/' || h.indexOf(d.host) > -1; });
      var external = links.some(function (h) { return /^https?:/.test(h) && h.indexOf(d.host) < 0; });
      var noindex = !!form.querySelector('[name="robots[]"][value=noindex]:checked');

      var checks = [
        ['Basic', 'Focus keyword is set', !!kw, 10, 'Add the phrase you want this page to rank for.'],
        ['Basic', 'Focus keyword in the SEO title', has(title, kw), 12, 'Use it naturally in the title, ideally near the start.'],
        ['Basic', 'Focus keyword in the meta description', has(desc, kw), 8, 'Mention it once in the description.'],
        ['Basic', 'Focus keyword in the URL', has(slug.replace(/-/g, ' '), kw), 8, 'Put the keyword in the URL slug.'],
        ['Basic', 'Focus keyword near the start of the content', has(first, kw), 6, 'Mention it in the first paragraph.'],
        ['Basic', 'Focus keyword in the content', count > 0, 8, 'The text doesn’t mention the keyword yet.'],
        ['Basic', 'Content is ' + words + ' words (aim for ' + minWords + '+)', words >= minWords, 8, 'Longer, genuinely useful text ranks better.'],
        ['Additional', 'Keyword density ' + density.toFixed(1) + '% (' + count + ' times)', density >= 0.5 && density <= 2.5, 6, density > 2.5 ? 'Too many repeats reads unnatural. Use it less.' : 'Mention it a couple more times, naturally.'],
        ['Additional', 'URL is short (' + url.length + ' characters)', url.length <= 75, 4, 'Keep URLs under 75 characters.'],
        ['Additional', 'Share image set', !!img, 4, 'Add an image so shares on WhatsApp and Instagram look good.'],
        ['Title & description', 'Title length ' + fullTitle.length + ' (50–60 ideal)', fullTitle.length >= 30 && fullTitle.length <= 65, 8, 'Google shows about 60 characters.'],
        ['Title & description', 'Keyword at the start of the title', !!kw && norm(title).indexOf(norm(kw)) > -1 && norm(title).indexOf(norm(kw)) < norm(title).length / 2, 4, 'Move the keyword closer to the beginning.'],
        ['Title & description', 'Description length ' + desc.length + ' (120–160 ideal)', desc.length >= 110 && desc.length <= 165, 6, 'Write one or two full sentences.'],
      ];
      if (kind === 'post' || kind === 'page') {
        checks.push(['Additional', 'Keyword in a subheading', has(heads, kw), 4, 'Use it in at least one H2 or H3 heading.']);
        checks.push(['Additional', 'Links to other pages of your site', internal, 4, 'Link to a product or category.']);
        if (kind === 'post') checks.push(['Additional', 'Links to a helpful outside source', external, 2, 'Optional: cite a trusted source.']);
      }
      var sec = val('secondary_keywords');
      if (sec) checks.push(['Additional', 'Secondary keywords used in the content', sec.split(',').filter(function (s) { return s.trim(); }).every(function (s) { return has(body, s.trim()); }), 4, 'Mention each secondary keyword at least once.']);

      var total = 0, got = 0; checks.forEach(function (c) { total += c[3]; if (c[2]) got += c[3]; });
      var score = noindex ? 0 : Math.round(got / total * 100);
      var sc = $('[data-seo-score]', box);
      $('b', sc).textContent = noindex ? '–' : score;
      sc.className = 'seo-score ' + (noindex ? '' : score >= 80 ? 'good' : score >= 50 ? 'ok' : 'bad');
      sc.title = noindex ? 'Hidden from Google (No index is ticked)' : 'SEO score';

      var groups = {}, html = '';
      checks.forEach(function (c) { (groups[c[0]] = groups[c[0]] || []).push(c); });
      Object.keys(groups).forEach(function (g) {
        var fails = groups[g].filter(function (c) { return !c[2]; }).length;
        html += '<details' + (fails ? ' open' : '') + '><summary>' + g + (fails ? '<span class="badge-err">' + fails + ' to fix</span>' : '<span class="badge-ok">All good</span>') + '</summary><ul>';
        groups[g].forEach(function (c) { html += '<li class="' + (c[2] ? 'pass' : 'fail') + '"><i></i><span>' + esc(c[1]) + (c[2] ? '' : '<small>' + esc(c[4]) + '</small>') + '</span></li>'; });
        html += '</ul></details>';
      });
      var list = $('[data-seo-checks]', box), open = $$('details', list).map(function (x) { return x.open; });
      list.innerHTML = html;
      if (open.length) $$('details', list).forEach(function (x, i) { x.open = open[i]; });
    }
    var t; var queue = function () { clearTimeout(t); t = setTimeout(run, 150); };
    form.addEventListener('input', queue); form.addEventListener('change', queue); form.addEventListener('gallery:change', queue);
    run();
  });

  /* ---------- Guided setup wizards ---------- */
  $$('dialog[data-wizard]').forEach(function (dlg) {
    var steps = $$('.wz-step', dlg), i = 0, busy = false, changed = false;
    var back = $('[data-wz-back]', dlg), next = $('[data-wz-next]', dlg), nextLabel = next.innerHTML;
    function show(n) {
      i = n;
      steps.forEach(function (s, k) { s.hidden = k !== i; });
      $('[data-wz-count]', dlg).textContent = 'Step ' + (i + 1) + ' of ' + steps.length;
      $('.wz-progress span', dlg).style.width = Math.round((i + 1) / steps.length * 100) + '%';
      back.hidden = i === 0;
      next.innerHTML = i === steps.length - 1 ? 'Finish' : nextLabel;
      $('.wz-steps', dlg).scrollTop = 0;
    }
    function saveStep() {
      var forms = $$('form[data-wz-save]', steps[i]);
      return forms.reduce(function (p, f) {
        return p.then(function () {
          var fd = new FormData(f); fd.append('_only', f.dataset.only);
          return post(f.dataset.wzSave, fd).then(function (r) { if (!r || !r.ok) throw new Error((r && (r.error || r.message)) || 'Could not save'); changed = true; });
        });
      }, Promise.resolve());
    }
    next.addEventListener('click', function () {
      if (busy) return; busy = true; next.disabled = true;
      saveStep().then(function () {
        if ($$('form[data-wz-save]', steps[i]).length) toast('Saved');
        if (i < steps.length - 1) show(i + 1); else { dlg.close(); }
      }).catch(function (e) { toast(e.message, false); })
        .finally(function () { busy = false; next.disabled = false; });
    });
    back.addEventListener('click', function () { if (i > 0) show(i - 1); });
    $('[data-wz-close]', dlg).addEventListener('click', function () { dlg.close(); });
    dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); });
    dlg.addEventListener('submit', function (e) { e.preventDefault(); next.click(); });
    dlg.addEventListener('close', function () { if (location.hash === '#' + dlg.dataset.wizard) history.replaceState(null, '', location.pathname); if (changed) location.reload(); });
    $$('[data-wz-test]', dlg).forEach(function (b) {
      b.addEventListener('click', function () {
        var out = $('[data-wz-result]', b.parentNode), label = b.innerHTML;
        b.disabled = true; b.textContent = 'Checking…';
        saveStep().then(function () { return post(b.dataset.wzTest, {}); }).then(function (r) {
          out.hidden = false; out.className = 'wz-result ' + (r.ok ? 'ok' : 'err');
          out.textContent = (r.ok ? '✓ ' : '✕ ') + (r.message || (r.ok ? 'Works!' : 'That didn’t work.'));
        }).catch(function (e) { out.hidden = false; out.className = 'wz-result err'; out.textContent = e.message; })
          .finally(function () { b.disabled = false; b.innerHTML = label; });
      });
    });
    dlg._open = function () { show(0); changed = false; dlg.showModal(); };
  });
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-wz-open]'); if (!b) return;
    var d = document.getElementById('wz-' + b.dataset.wzOpen); if (d && d._open) { history.replaceState(null, '', '#' + b.dataset.wzOpen); d._open(); }
  });
  function openFromHash() { var wz = location.hash && document.getElementById('wz-' + location.hash.slice(1)); if (wz && wz._open && !wz.open) wz._open(); }
  openFromHash(); window.addEventListener('hashchange', openFromHash);

  /* ---------- Long actions: show progress on the button ---------- */
  document.addEventListener('submit', function (e) {
    var b = e.target.querySelector('[data-busy]'); if (!b) return;
    setTimeout(function () { b.disabled = true; b.textContent = b.dataset.busy; }, 0);
  }, true);

})();
