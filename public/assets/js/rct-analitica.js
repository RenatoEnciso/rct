/* RCT · tableros de analítica: contadores animados, gráficos y simulador de impacto */
(function () {
  'use strict';

  var reducido = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var NBSP = ' ';

  /* ── Formatos (S/ 1 234 567 · 12.3% · S/ 1.85) ────────────── */
  function miles(n) { return String(Math.abs(Math.round(n))).replace(/\B(?=(\d{3})+(?!\d))/g, NBSP); }
  var formato = {
    soles: function (n) { return (Math.round(n) < 0 ? '−S/ ' : 'S/ ') + miles(n); },
    pct:   function (n) { return n.toFixed(1) + '%'; },
    ratio: function (n) { return 'S/ ' + n.toFixed(2); },
    num:   function (n) { return miles(n); },
    // Formato peruano con céntimos: S/ 2 500,00
    pe:    function (n) {
      var neg = n < 0, p = Math.abs(n).toFixed(2).split('.');
      return (neg ? '−' : '') + 'S/ ' + p[0].replace(/\B(?=(\d{3})+(?!\d))/g, NBSP) + ',' + p[1];
    }
  };
  function corto(n) {
    if (Math.abs(n) >= 1e6) return 'S/ ' + (n / 1e6).toFixed(1) + ' M';
    if (Math.abs(n) >= 1e3) return 'S/ ' + Math.round(n / 1e3) + ' mil';
    return 'S/ ' + Math.round(n);
  }

  /* ── Contadores ─────────────────────────────────────────────── */
  function contar(el) {
    var destino = parseFloat(el.getAttribute('data-contar')) || 0;
    var f = formato[el.getAttribute('data-formato')] || formato.num;
    if (reducido || destino === 0) { el.textContent = f(destino); return; }
    var dur = 1400, t0 = null;
    function paso(t) {
      if (!t0) t0 = t;
      var k = Math.min(1, (t - t0) / dur);
      var e = 1 - Math.pow(1 - k, 3);   // ease-out cúbico
      el.textContent = f(destino * e);
      if (k < 1) requestAnimationFrame(paso); else el.textContent = f(destino);
    }
    el.textContent = f(0);
    requestAnimationFrame(paso);
  }
  function iniciarContadores(raiz) {
    var els = (raiz || document).querySelectorAll('[data-contar]');
    if (!('IntersectionObserver' in window)) { els.forEach(contar); return; }
    var io = new IntersectionObserver(function (entradas) {
      entradas.forEach(function (en) {
        if (en.isIntersecting) { contar(en.target); io.unobserve(en.target); }
      });
    }, { threshold: 0.3 });
    els.forEach(function (el) { io.observe(el); });
  }

  /* ── Tablas alternativas de los gráficos ────────────────────── */
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-ver-tabla]');
    if (!b) return;
    var t = document.getElementById(b.getAttribute('data-ver-tabla'));
    var mostrar = t.hasAttribute('hidden');
    t.toggleAttribute('hidden', !mostrar);
    b.innerHTML = mostrar ? '<i class="bi bi-bar-chart"></i> Ver gráfico' : '<i class="bi bi-table"></i> Ver tabla';
    var canvas = b.closest('.card').querySelector('.cc-chart');
    if (canvas) canvas.toggleAttribute('hidden', mostrar);
  });

  /* ── Estilo común de Chart.js ───────────────────────────────── */
  var COLOR = { recuperado: '#3567ad', condonado: '#b8761a', prioridad: '#a3182c', neutro: '#8a94a3', grid: '#eef0f2', texto: '#5f6b7a' };
  function baseChart() {
    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
    Chart.defaults.font.size = 12;
    Chart.defaults.color = COLOR.texto;
    Chart.defaults.plugins.legend.labels.boxWidth = 10;
    Chart.defaults.plugins.legend.labels.boxHeight = 10;
    Chart.defaults.plugins.tooltip.backgroundColor = '#1c2430';
    Chart.defaults.plugins.tooltip.padding = 10;
    Chart.defaults.plugins.tooltip.cornerRadius = 6;
    if (reducido) Chart.defaults.animation = false;
  }
  function ejeSoles() {
    return { beginAtZero: true, border: { display: false }, grid: { color: COLOR.grid }, ticks: { callback: corto, maxTicksLimit: 6 } };
  }

  /* ── Centro de control ──────────────────────────────────────── */
  function centroControl(d) {
    baseChart();
    tendencia(d.tendencia);
    simulador(d.simulador);
    iniciarContadores();
    requestAnimationFrame(function () { document.body.classList.add('cc-listo'); });
  }

  function tendencia(datos) {
    var el = document.getElementById('chart-tendencia');
    if (!el) return;
    new Chart(el, {
      data: {
        labels: datos.map(function (m) { return m.mes; }),
        datasets: [
          { type: 'bar', label: 'Recuperado', data: datos.map(function (m) { return m.recuperado; }), backgroundColor: COLOR.recuperado,
            borderRadius: { topLeft: 4, topRight: 4 }, borderSkipped: 'bottom', maxBarThickness: 26, order: 2 },
          { type: 'bar', label: 'Condonado', data: datos.map(function (m) { return m.condonado; }), backgroundColor: COLOR.condonado,
            borderRadius: { topLeft: 4, topRight: 4 }, borderSkipped: 'bottom', maxBarThickness: 26, order: 2 },
          { type: 'line', label: 'Meta', data: datos.map(function (m) { return m.meta || null; }), borderColor: COLOR.neutro, borderDash: [5, 4],
            borderWidth: 2, pointRadius: 3, pointBackgroundColor: COLOR.neutro, tension: 0, order: 1, spanGaps: true }
        ]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        animation: { duration: 1100, easing: 'easeOutQuart' },
        interaction: { mode: 'index', intersect: false },
        datasets: { bar: { categoryPercentage: 0.7, barPercentage: 0.86 } },
        plugins: {
          legend: { position: 'bottom', labels: { sort: function (a, b) { return a.datasetIndex - b.datasetIndex; } } },
          tooltip: { callbacks: { label: function (c) { return ' ' + c.dataset.label + ': ' + formato.soles(c.parsed.y || 0); } } }
        },
        scales: { x: { grid: { display: false }, border: { color: '#cfd4da' } }, y: ejeSoles() }
      }
    });
  }

  function simulador(s) {
    var el = document.getElementById('chart-simulador');
    var rango = document.getElementById('sim-rango');
    if (!el || !rango) return;

    function acumulado(filas, i) {
      var a = [0];
      filas.forEach(function (f) { a.push(a[a.length - 1] + f[i]); });
      return a;
    }
    var n = s.prioridad.length;
    var recP = acumulado(s.prioridad, 0), provP = acumulado(s.prioridad, 1), recN = acumulado(s.normal, 0);
    var k = function (pct) { return Math.round(n * pct / 100); };
    var puntos = []; for (var p = 0; p <= 100; p += 5) puntos.push(p);

    var marca = {
      id: 'marca',
      afterDatasetsDraw: function (chart) {
        var x = chart.scales.x.getPixelForValue(puntos.indexOf(+rango.value));
        var a = chart.chartArea, ctx = chart.ctx;
        ctx.save();
        ctx.strokeStyle = 'rgba(28,36,48,.35)'; ctx.setLineDash([4, 4]); ctx.lineWidth = 1;
        ctx.beginPath(); ctx.moveTo(x, a.top); ctx.lineTo(x, a.bottom); ctx.stroke();
        ctx.restore();
      }
    };

    var chart = new Chart(el, {
      type: 'line',
      data: {
        labels: puntos.map(function (p) { return p + '%'; }),
        datasets: [
          { label: 'En orden de prioridad', data: puntos.map(function (p) { return recP[k(p)]; }), borderColor: COLOR.prioridad,
            backgroundColor: 'rgba(163,24,44,.08)', fill: 'origin', borderWidth: 2.5, pointRadius: 0, pointHoverRadius: 5, tension: 0.35 },
          { label: 'Orden habitual', data: puntos.map(function (p) { return recN[k(p)]; }), borderColor: COLOR.neutro,
            borderDash: [6, 4], borderWidth: 2, pointRadius: 0, pointHoverRadius: 5, tension: 0.35, fill: false }
        ]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        animation: { duration: 1400, easing: 'easeOutQuart' },
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: { position: 'bottom' },
          tooltip: { callbacks: {
            title: function (c) { return 'Cartera gestionada: ' + c[0].label; },
            label: function (c) { return ' ' + c.dataset.label + ': ' + formato.soles(c.parsed.y); }
          } }
        },
        scales: {
          x: { grid: { display: false }, border: { color: '#cfd4da' }, title: { display: true, text: '% de la cartera pendiente gestionada' },
               ticks: { callback: function (v, i) { return i % 2 === 0 ? this.getLabelForValue(v) : ''; } } },
          y: ejeSoles()
        }
      },
      plugins: [marca]
    });

    var salida = {
      pct: document.getElementById('sim-pct'), rec: document.getElementById('sim-recupero'), prov: document.getElementById('sim-provision'),
      ventaja: document.getElementById('sim-ventaja'), creditos: document.getElementById('sim-creditos')
    };
    function actualizar() {
      var pct = +rango.value, i = k(pct);
      var dif = recP[i] - recN[i];
      rango.style.setProperty('--avance', ((pct - 5) / 95 * 100) + '%');
      salida.pct.textContent = pct + '%';
      salida.rec.textContent = formato.soles(recP[i]);
      salida.prov.textContent = formato.soles(provP[i]);
      var rel = recN[i] > 0 ? (recP[i] / recN[i] - 1) * 100 : 0;
      salida.ventaja.textContent = (dif >= 0 ? '+' : '') + formato.soles(dif) + (recN[i] > 0 ? ' (' + (rel >= 0 ? '+' : '') + rel.toFixed(1) + '%)' : '');
      salida.creditos.textContent = miles(i) + ' de ' + miles(n) + ' créditos pendientes por gestionar';
      chart.draw();
    }
    rango.addEventListener('input', actualizar);
    actualizar();
  }

  /* ── Análisis de recaída: % al día a 30/60/90 días por mes del beneficio ── */
  function recaida(d) {
    baseChart();
    var el = document.getElementById('chart-curva');
    if (el) {
      // Rampa ordinal de un solo tono: 30 días (claro) → 90 días (oscuro)
      var series = [['d30', '30 días', '#8fb0dc'], ['d60', '60 días', '#4f7fc0'], ['d90', '90 días', '#1f4e8c']];
      new Chart(el, {
        type: 'line',
        data: {
          labels: d.curva.map(function (c) { return c.mes; }),
          datasets: series.map(function (s) {
            return { label: 'Al día a ' + s[1], data: d.curva.map(function (c) { return c[s[0]]; }), borderColor: s[2], backgroundColor: s[2],
                     borderWidth: 2, pointRadius: 4, pointHoverRadius: 6, pointBorderColor: '#fff', pointBorderWidth: 2, tension: 0.25, spanGaps: false };
          })
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          animation: { duration: 1100, easing: 'easeOutQuart' },
          interaction: { mode: 'index', intersect: false },
          plugins: {
            legend: { position: 'bottom' },
            tooltip: { callbacks: { label: function (c) { return ' ' + c.dataset.label + ': ' + (c.parsed.y == null ? 'en curso' : c.parsed.y.toFixed(1) + '%'); } } }
          },
          scales: {
            x: { grid: { display: false }, border: { color: '#cfd4da' } },
            y: { min: 50, max: 100, border: { display: false }, grid: { color: COLOR.grid }, ticks: { callback: function (v) { return v + '%'; }, stepSize: 10 } }
          }
        }
      });
    }
    iniciarContadores();
    requestAnimationFrame(function () { document.body.classList.add('cc-listo'); });
  }

  /* ── Detalle de alerta: esta persona / tienda frente a su referencia ── */
  function alerta(d) {
    requestAnimationFrame(function () { document.body.classList.add('cc-listo'); });
    var el = document.getElementById('chart-alerta'), s = d.serie || {};
    if (!el || !window.Chart) return;
    baseChart();
    var fmt = function (v) {
      if (v == null) return 'sin datos';
      if (d.unidad === 'min') return v >= 120 ? (v / 60).toFixed(1) + ' horas' : Math.round(v) + ' min';
      return d.unidad === '%' ? v.toFixed(1) + '%' : String(v);
    };
    var ejeY = { beginAtZero: true, border: { display: false }, grid: { color: COLOR.grid },
                 ticks: { callback: function (v) { return d.unidad === '%' ? v + '%' : (d.unidad === 'min' && v >= 120 ? Math.round(v / 60) + ' h' : v); } } };

    if (s.tipo === 'mensual') {
      // Dos series: la entidad (rojo institucional) y su referencia (gris neutro)
      new Chart(el, {
        type: 'bar',
        data: { labels: s.etiquetas, datasets: [
          { label: s.series[0].nombre, data: s.series[0].valores, backgroundColor: COLOR.prioridad, borderRadius: { topLeft: 4, topRight: 4 }, borderSkipped: 'bottom', maxBarThickness: 28 },
          { label: s.series[1].nombre, data: s.series[1].valores, backgroundColor: '#b9c0ca', borderRadius: { topLeft: 4, topRight: 4 }, borderSkipped: 'bottom', maxBarThickness: 28 }
        ] },
        options: { responsive: true, maintainAspectRatio: false, animation: { duration: 1000, easing: 'easeOutQuart' },
          datasets: { bar: { categoryPercentage: 0.66, barPercentage: 0.88 } },
          interaction: { mode: 'index', intersect: false },
          plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: function (c) { return ' ' + c.dataset.label + ': ' + fmt(c.parsed.y); } } } },
          scales: { x: { grid: { display: false }, border: { color: '#cfd4da' } }, y: ejeY } }
      });
    } else if (s.tipo === 'barras') {
      // Una sola serie: se resalta la entidad y el resto queda en gris
      new Chart(el, {
        type: 'bar',
        data: { labels: s.items.map(function (i) { return i.etiqueta; }), datasets: [{
          label: s.titulo || '', data: s.items.map(function (i) { return i.valor; }),
          backgroundColor: s.items.map(function (i) { return i.destacar ? COLOR.prioridad : '#c9ced6'; }),
          borderRadius: { topRight: 4, bottomRight: 4 }, borderSkipped: 'left', maxBarThickness: 26 }] },
        options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, animation: { duration: 1000, easing: 'easeOutQuart' },
          layout: { padding: { left: 4, right: 12 } },
          plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return ' ' + fmt(c.parsed.x); } } } },
          scales: { x: Object.assign({}, ejeY, { grid: { color: COLOR.grid } }), y: { grid: { display: false }, border: { color: '#cfd4da' }, afterFit: function (a) { a.width = Math.max(a.width, 170); } } } }
      });
    }
  }

  window.RCTAnalitica = { centroControl: centroControl, recaida: recaida, alerta: alerta, contadores: iniciarContadores, formato: formato };
})();
