/* RCT · comportamiento común de la interfaz */
(function () {
  'use strict';

  var CSRF = (document.querySelector('meta[name=csrf-token]') || {}).content;

  function esc(t) {
    return String(t == null ? '' : t)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }
  window.rctEsc = esc;

  /* ── Barra lateral en móvil ─────────────────────────────── */
  var sidebar = document.getElementById('sidebar');
  var backdrop = document.getElementById('sidebarBackdrop');
  var toggle = document.getElementById('sidebarToggle');
  function cerrarMenu() { sidebar && sidebar.classList.remove('open'); backdrop && backdrop.classList.remove('open'); }
  toggle && toggle.addEventListener('click', function () {
    var abierto = sidebar.classList.toggle('open');
    backdrop.classList.toggle('open', abierto);
  });
  backdrop && backdrop.addEventListener('click', cerrarMenu);

  /* ── Mensajes de sesión ─────────────────────────────────── */
  document.addEventListener('click', function (e) {
    var b = e.target.closest('.flash-close');
    if (b) b.closest('.flash').remove();
  });

  /* ── Ventanas modales ───────────────────────────────────── */
  window.rctModal = {
    abrir: function (id) { var m = document.getElementById(id); if (m) { m.classList.add('show'); document.body.classList.add('modal-open'); } },
    cerrar: function (id) { var m = document.getElementById(id); if (m) m.classList.remove('show'); if (!document.querySelector('.rct-modal.show')) document.body.classList.remove('modal-open'); }
  };
  document.addEventListener('click', function (e) {
    var abrir = e.target.closest('[data-modal-open]');
    if (abrir) { e.preventDefault(); rctModal.abrir(abrir.getAttribute('data-modal-open')); return; }
    var cerrar = e.target.closest('[data-modal-close]');
    if (cerrar) { rctModal.cerrar(cerrar.closest('.rct-modal').id); return; }
    if (e.target.classList && e.target.classList.contains('rct-modal')) rctModal.cerrar(e.target.id);
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') document.querySelectorAll('.rct-modal.show').forEach(function (m) { rctModal.cerrar(m.id); });
  });

  /* ── Confirmaciones ─────────────────────────────────────── */
  document.addEventListener('submit', function (e) {
    var f = e.target;
    var msg = f.getAttribute('data-confirm') || (e.submitter && e.submitter.getAttribute('data-confirm'));
    if (msg && !window.confirm(msg)) e.preventDefault();
  });

  /* ── Tablas con búsqueda y paginación (lado cliente) ────── */
  function mejorarTabla(tabla) {
    var tamPagina = parseInt(tabla.getAttribute('data-page-size') || '10', 10);
    var tbody = tabla.tBodies[0];
    if (!tbody) return;
    var filas = Array.prototype.filter.call(tbody.rows, function (r) { return !r.hasAttribute('data-empty'); });
    if (!filas.length) return;

    var cont = tabla.closest('.table-wrap') || tabla;
    var tools = document.createElement('div');
    tools.className = 'table-tools';
    tools.innerHTML =
      '<div class="table-search"><i class="bi bi-search"></i>' +
      '<input type="search" placeholder="' + esc(tabla.getAttribute('data-search-placeholder') || 'Buscar en la tabla') + '" aria-label="Buscar"></div>' +
      '<span class="table-count"></span>';
    cont.parentNode.insertBefore(tools, cont);

    var pager = document.createElement('div');
    pager.className = 'rct-pager';
    pager.innerHTML = '<span class="rct-pager-info"></span><div class="rct-pager-links"></div>';
    cont.parentNode.insertBefore(pager, cont.nextSibling);

    var vacio = document.createElement('tr');
    vacio.setAttribute('data-empty', '');
    vacio.innerHTML = '<td colspan="' + (tabla.tHead ? tabla.tHead.rows[0].cells.length : 1) +
      '" class="empty-state"><i class="bi bi-search"></i><strong>Sin resultados</strong><span>No hay registros que coincidan con la búsqueda.</span></td>';

    var input = tools.querySelector('input');
    var pagina = 1;

    function pintar() {
      var q = input.value.trim().toLowerCase();
      var visibles = filas.filter(function (r) { return !r.hasAttribute('data-oculta') && (!q || r.textContent.toLowerCase().indexOf(q) !== -1); });
      var paginas = Math.max(1, Math.ceil(visibles.length / tamPagina));
      if (pagina > paginas) pagina = paginas;
      var desde = (pagina - 1) * tamPagina;
      filas.forEach(function (r) { r.style.display = 'none'; });
      visibles.slice(desde, desde + tamPagina).forEach(function (r) { r.style.display = ''; });
      if (!visibles.length) { if (!vacio.parentNode) tbody.appendChild(vacio); } else if (vacio.parentNode) vacio.remove();

      tools.querySelector('.table-count').textContent = visibles.length + (visibles.length === 1 ? ' registro' : ' registros');
      pager.querySelector('.rct-pager-info').textContent = visibles.length
        ? 'Mostrando ' + (desde + 1) + '–' + Math.min(desde + tamPagina, visibles.length) + ' de ' + visibles.length : '';
      var links = pager.querySelector('.rct-pager-links');
      links.innerHTML = '';
      pager.style.display = paginas > 1 ? '' : 'none';
      function boton(txt, p, activo, deshab, etiqueta) {
        var b = document.createElement('button');
        b.type = 'button'; b.innerHTML = txt; b.disabled = deshab;
        if (etiqueta) b.setAttribute('aria-label', etiqueta);
        if (activo) b.className = 'active';
        b.addEventListener('click', function () { pagina = p; pintar(); });
        links.appendChild(b);
      }
      boton('<i class="bi bi-chevron-left"></i>', pagina - 1, false, pagina === 1, 'Anterior');
      for (var i = 1; i <= paginas; i++) {
        if (paginas > 7 && i > 2 && i < paginas - 1 && Math.abs(i - pagina) > 1) {
          if (!links.lastChild || links.lastChild.textContent !== '…') { var s = document.createElement('span'); s.className = 'disabled'; s.textContent = '…'; links.appendChild(s); }
          continue;
        }
        boton(String(i), i, i === pagina, false);
      }
      boton('<i class="bi bi-chevron-right"></i>', pagina + 1, false, pagina === paginas, 'Siguiente');
    }
    input.addEventListener('input', function () { pagina = 1; pintar(); });
    pintar();
  }
  document.querySelectorAll('table[data-rct-table]').forEach(mejorarTabla);

  /* ── Carga de documentos con validación automática ─────── */
  var PASOS = ['Verificando formato del documento', 'Detectando firmas', 'Cruzando datos con el expediente'];
  var ICONOS = { conforme: 'bi-check-circle-fill', observado: 'bi-exclamation-triangle-fill', rechazado: 'bi-x-octagon-fill' };
  var TITULOS = { conforme: 'Documento conforme', observado: 'Documento observado', rechazado: 'Documento rechazado' };

  function renderResultado(slot, r) {
    var estado = r.estado;
    slot.classList.remove('is-conforme', 'is-observado', 'is-rechazado');
    slot.classList.add('is-' + estado);
    slot.setAttribute('data-estado', estado);
    var badge = slot.querySelector('.doc-badge');
    if (badge) { badge.className = 'badge doc-badge badge-' + estado; badge.textContent = r.etiqueta; }

    var cuerpo = r.hallazgos && r.hallazgos.length
      ? '<ul class="hallazgos">' + r.hallazgos.map(function (h) {
          return '<li><i class="bi ' + (h.severidad === 'rechazado' ? 'bi-x-circle' : 'bi-exclamation-circle') + '"></i><span>' + esc(h.mensaje) + '</span></li>';
        }).join('') + '</ul>'
      : '<div>' + esc(r.mensaje || '') + '</div>';

    slot.querySelector('.val-result').innerHTML =
      '<div class="val-box ' + estado + '"><div class="val-box-head"><i class="bi ' + ICONOS[estado] + '"></i>' + TITULOS[estado] + '</div>' + cuerpo + '</div>' +
      '<div class="val-caption"><i class="bi bi-shield-check"></i>Validación automática</div>';
  }

  function validarSlot(slot, input) {
    var archivo = input.files[0];
    var resultado = slot.querySelector('.val-result');
    var nombre = slot.querySelector('.doc-file-name');
    var badge = slot.querySelector('.doc-badge');
    slot.classList.remove('is-conforme', 'is-observado', 'is-rechazado');
    slot.setAttribute('data-estado', archivo ? 'analizando' : '');
    if (!archivo) { resultado.innerHTML = ''; if (nombre) nombre.textContent = 'Seleccionar archivo'; return; }

    if (nombre) nombre.textContent = 'Archivo seleccionado';
    if (badge) { badge.className = 'badge doc-badge badge-info'; badge.textContent = 'Analizando'; }
    resultado.innerHTML = '<div class="val-analyzing"><div class="val-analyzing-head"><span class="spinner"></span>Analizando documento…</div>' +
      PASOS.map(function (p) { return '<div class="val-step"><i class="bi bi-circle"></i>' + p + '</div>'; }).join('') + '</div>';
    slot.dispatchEvent(new CustomEvent('rct:validando', { bubbles: true }));

    var form = slot.closest('[data-validar-url]');
    var fd = new FormData();
    fd.append('archivo', archivo);
    fd.append('tipo', slot.getAttribute('data-tipo'));
    fd.append('contexto', form.getAttribute('data-contexto'));
    ['sustento_id', 'expediente_id'].forEach(function (k) {
      var v = slot.getAttribute('data-' + k.replace('_', '-')) || form.getAttribute('data-' + k.replace('_', '-'));
      if (v) fd.append(k, v);
    });
    var ref = form.querySelector('[name=cliente_ref]');
    if (ref && ref.value) fd.append('cliente_ref', ref.value);

    var inicio = Date.now();
    var peticion = fetch(form.getAttribute('data-validar-url'), {
      method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    }).then(function (res) {
      return res.json().then(function (j) { return { ok: res.ok, data: j }; });
    });

    // Animación de los pasos mientras dura el análisis
    var pasos = resultado.querySelectorAll('.val-step');
    var idx = 0;
    var timer = setInterval(function () {
      if (idx > 0) { pasos[idx - 1].className = 'val-step done'; pasos[idx - 1].querySelector('.bi').className = 'bi bi-check-circle-fill'; }
      if (idx < pasos.length) { pasos[idx].className = 'val-step running'; pasos[idx].querySelector('.bi').className = 'bi bi-arrow-repeat'; }
      idx++;
    }, 600);

    peticion.then(function (resp) {
      var espera = resp.ok ? Math.max(0, (resp.data.demora_ms || 1500) - (Date.now() - inicio)) : 0;
      setTimeout(function () {
        clearInterval(timer);
        if (!resp.ok) {
          var msg = resp.data && resp.data.errors ? Object.values(resp.data.errors)[0][0] : 'No se pudo validar el documento.';
          renderResultado(slot, { estado: 'rechazado', etiqueta: 'No admitido', hallazgos: [{ severidad: 'rechazado', mensaje: msg }] });
          input.value = '';
          if (nombre) nombre.textContent = 'Seleccionar archivo';
          slot.setAttribute('data-estado', '');
        } else {
          if (nombre) nombre.textContent = resp.data.nombre;
          renderResultado(slot, resp.data);
        }
        slot.dispatchEvent(new CustomEvent('rct:validado', { bubbles: true, detail: resp.data }));
      }, espera);
    }).catch(function () {
      clearInterval(timer);
      renderResultado(slot, { estado: 'rechazado', etiqueta: 'Error', hallazgos: [{ severidad: 'rechazado', mensaje: 'No se pudo conectar con el servicio de validación. Intente nuevamente.' }] });
      slot.dispatchEvent(new CustomEvent('rct:validado', { bubbles: true }));
    });
  }

  document.addEventListener('change', function (e) {
    var input = e.target;
    if (input.matches && input.matches('.doc-slot input[type=file]')) validarSlot(input.closest('.doc-slot'), input);
  });

  /* Resumen de un formulario de carga: { total, cargados, conformes, observados, rechazados, analizando } */
  window.rctResumenCarga = function (form) {
    var r = { total: 0, cargados: 0, conformes: 0, observados: 0, rechazados: 0, analizando: 0 };
    form.querySelectorAll('.doc-slot').forEach(function (s) {
      r.total++;
      var e = s.getAttribute('data-estado');
      if (e === 'analizando') r.analizando++;
      if (e === 'conforme') { r.cargados++; r.conformes++; }
      if (e === 'observado') { r.cargados++; r.observados++; }
      if (e === 'rechazado') { r.cargados++; r.rechazados++; }
    });
    return r;
  };

  /* ── Modo presentación: sin menú, tipografía mayor, pantalla limpia para capturas ── */
  var CLAVE_PRES = 'rct.presentacion';
  function modoPresentacion(activar) {
    document.body.classList.toggle('modo-presentacion', activar);
    try { localStorage.setItem(CLAVE_PRES, activar ? '1' : '0'); } catch (e) {}
    var salir = document.getElementById('salir-presentacion');
    if (activar && !salir) {
      salir = document.createElement('button');
      salir.type = 'button'; salir.id = 'salir-presentacion'; salir.className = 'salir-presentacion';
      salir.innerHTML = '<i class="bi bi-fullscreen-exit"></i> Salir del modo presentación';
      salir.addEventListener('click', function () { modoPresentacion(false); });
      document.body.appendChild(salir);
    }
    if (!activar && salir) salir.remove();
  }
  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-modo-presentacion]')) modoPresentacion(!document.body.classList.contains('modo-presentacion'));
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && document.body.classList.contains('modo-presentacion')) modoPresentacion(false);
  });
  if (document.body.classList.contains('modo-presentacion')) modoPresentacion(true);

  /* ── Avisos en vivo: consulta cada 10 s, sin recargar (pagos sincronizados, honramiento, cruce TXT) ── */
  var urlAvisos = (document.querySelector('meta[name=rct-avisos-url]') || {}).content;
  var desdeAvisos = (document.querySelector('meta[name=rct-avisos-desde]') || {}).content;
  function contador(el, n, clase) {
    if (!el) return;
    var c = el.querySelector('.' + clase);
    if (!n) { c && c.remove(); return; }
    if (!c) { c = document.createElement('span'); c.className = clase; el.appendChild(c); }
    c.textContent = n > 99 ? '99+' : n;
    c.classList.remove('pulso'); void c.offsetWidth; c.classList.add('pulso');
  }
  function toastEnVivo(n) {
    var cont = document.getElementById('toasts');
    if (!cont) { cont = document.createElement('div'); cont.id = 'toasts'; cont.className = 'toasts'; cont.setAttribute('aria-live', 'polite'); document.body.appendChild(cont); }
    var t = document.createElement('div');
    t.className = 'toast-rct en-vivo ' + esc(n.color);
    t.innerHTML = '<span class="notif-ico ' + esc(n.color) + '"><i class="bi ' + esc(n.icono) + '"></i></span>'
      + '<a href="' + esc(n.url) + '" class="toast-body" data-clave="' + esc(n.clave) + '"><strong>' + esc(n.titulo) + '</strong><span>' + esc(n.mensaje) + '</span></a>'
      + '<button type="button" class="toast-close" aria-label="Cerrar"><i class="bi bi-x-lg"></i></button><span class="toast-bar"></span>';
    cont.prepend(t);
    var cerrar = function () { t.classList.add('saliendo'); setTimeout(function () { t.remove(); }, 300); };
    t.querySelector('.toast-close').addEventListener('click', cerrar);
    setTimeout(cerrar, 9000);
  }
  function agregarALaCampana(n) {
    var lista = document.querySelector('#notif-panel .notif-list');
    if (!lista) return;
    var vacio = lista.querySelector('.empty-state'); vacio && vacio.remove();
    var a = document.createElement('a');
    a.href = n.url; a.className = 'notif-item unread'; a.setAttribute('data-clave', n.clave); a.setAttribute('data-cat', n.categoria);
    a.innerHTML = '<span class="notif-ico ' + esc(n.color) + '"><i class="bi ' + esc(n.icono) + '"></i></span><span class="notif-body"><span class="notif-title">'
      + esc(n.titulo) + '</span><span class="notif-msg">' + esc(n.mensaje) + '</span><span class="notif-time">hace instantes</span></span>';
    lista.prepend(a);
  }
  function revisarAvisos() {
    if (!urlAvisos || document.hidden && !revisarAvisos.forzar) return Promise.resolve();
    revisarAvisos.forzar = false;
    return fetch(urlAvisos + '?desde=' + encodeURIComponent(desdeAvisos), { headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (d) {
        if (!d) return;
        desdeAvisos = d.ahora;
        d.nuevas.forEach(function (n, i) {
          agregarALaCampana(n);
          if (n.toast) setTimeout(function () { toastEnVivo(n); }, i * 400);
        });
        if (d.no_leidas !== null) {
          contador(document.getElementById('notif-btn'), d.no_leidas, 'notif-count');
          contador(document.getElementById('nav-notif'), d.no_leidas, 'nav-count');
        }
      }).catch(function () {});
  }
  window.rctRevisarAvisos = function () { revisarAvisos.forzar = true; return revisarAvisos(); };
  if (urlAvisos) setInterval(revisarAvisos, 10000);
})();
