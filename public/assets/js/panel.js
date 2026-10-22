/* =====================================================================
   Hotel Elegance — comportamiento común del panel
   Tema claro/oscuro, menú lateral, notificaciones, confirmaciones,
   búsqueda global (Ctrl+K), validación de formularios y utilidades.
   ===================================================================== */
(function () {
  'use strict';

  const raiz = document.documentElement;
  const plantillaUrl = document.querySelector('meta[name="he-url"]')?.content || '/__ruta__';
  const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

  const HE = window.HE = {
    /** Construye la URL de una ruta respetando la carpeta base del proyecto. */
    url(ruta) {
      ruta = String(ruta || '').replace(/^\//, '');
      const [camino, consulta] = ruta.split('?');
      return plantillaUrl.replace('__ruta__', camino) + (consulta ? '?' + consulta : '');
    },

    async api(ruta, opciones = {}) {
      const respuesta = await fetch(HE.url(ruta), {
        credentials: 'same-origin',
        ...opciones,
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-Token': token,
          ...(opciones.body && !(opciones.body instanceof FormData) ? { 'Content-Type': 'application/json' } : {}),
          ...(opciones.headers || {})
        }
      });
      const datos = await respuesta.json().catch(() => ({}));
      if (!respuesta.ok) {
        const error = new Error(datos.mensaje || 'No se pudo completar la operación.');
        error.datos = datos;
        error.estado = respuesta.status;
        throw error;
      }
      return datos;
    },

    bs(monto, decimales = 2) {
      return 'Bs ' + Number(monto || 0).toLocaleString('es-BO', { minimumFractionDigits: decimales, maximumFractionDigits: decimales });
    },

    numero(valor, decimales = 0) {
      return Number(valor || 0).toLocaleString('es-BO', { minimumFractionDigits: decimales, maximumFractionDigits: decimales });
    },

    escapar(texto) {
      const d = document.createElement('div');
      d.textContent = texto == null ? '' : String(texto);
      return d.innerHTML;
    },

    /** Notificación tipo toast. tipo: exito | error | aviso | info */
    notificar(texto, tipo = 'exito', duracion = 5200) {
      const contenedor = document.getElementById('toasts');
      if (!contenedor) return;
      const estilos = {
        exito: ['chip-verde', 'bi-check-circle-fill', 'Listo', 'var(--he-verde)'],
        error: ['chip-rojo', 'bi-x-octagon-fill', 'No se pudo completar', 'var(--he-rojo)'],
        aviso: ['chip-ambar', 'bi-exclamation-triangle-fill', 'Atención', 'var(--he-ambar)'],
        info: ['chip-azul', 'bi-info-circle-fill', 'Información', 'var(--he-azul)']
      }[tipo] || ['chip-azul', 'bi-info-circle-fill', 'Información', 'var(--he-azul)'];
      const el = document.createElement('div');
      el.className = 'toast-he';
      el.setAttribute('role', tipo === 'error' ? 'alert' : 'status');
      el.style.color = estilos[3];
      el.innerHTML = `
        <span class="icono-chip ${estilos[0]}"><i class="bi ${estilos[1]}"></i></span>
        <div style="color: var(--he-texto)"><strong>${estilos[2]}</strong><p>${HE.escapar(texto)}</p></div>
        <button class="cerrar" type="button" aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>
        <span class="progreso" style="animation-duration:${duracion}ms"></span>`;
      const cerrar = () => {
        el.classList.add('saliendo');
        setTimeout(() => el.remove(), 250);
      };
      el.querySelector('.cerrar').addEventListener('click', cerrar);
      contenedor.appendChild(el);
      setTimeout(cerrar, duracion);
    },

    /** Modal de confirmación reutilizable. Devuelve una promesa booleana. */
    confirmar({ titulo = '¿Confirmar acción?', texto = '', boton = 'Confirmar', tipo = 'peligro', icono = 'bi-exclamation-triangle' } = {}) {
      const modalEl = document.getElementById('modal-confirmar');
      if (!modalEl || !window.bootstrap) return Promise.resolve(window.confirm(titulo + '\n' + texto));
      modalEl.querySelector('[data-confirmar-titulo]').textContent = titulo;
      modalEl.querySelector('[data-confirmar-texto]').textContent = texto;
      const chip = modalEl.querySelector('[data-confirmar-icono]');
      chip.className = 'icono-chip confirmar-icono ' + (tipo === 'peligro' ? 'chip-rojo' : 'chip-oro');
      chip.innerHTML = `<i class="bi ${icono}"></i>`;
      const aceptar = modalEl.querySelector('[data-confirmar-aceptar]');
      aceptar.textContent = boton;
      aceptar.className = 'btn ' + (tipo === 'peligro' ? 'btn-peligro' : 'btn-primary');
      const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
      return new Promise((resolver) => {
        let respuesta = false;
        const alAceptar = () => { respuesta = true; modal.hide(); };
        aceptar.addEventListener('click', alAceptar, { once: true });
        modalEl.addEventListener('hidden.bs.modal', () => {
          aceptar.removeEventListener('click', alAceptar);
          resolver(respuesta);
        }, { once: true });
        modal.show();
      });
    },

    /** Colores de la marca para Chart.js, según el tema activo. */
    colores() {
      const css = getComputedStyle(raiz);
      const v = (n) => css.getPropertyValue(n).trim();
      const oscuro = raiz.getAttribute('data-bs-theme') === 'dark';
      return {
        oscuro,
        texto: v('--he-texto-suave'),
        tenue: v('--he-texto-tenue'),
        rejilla: oscuro ? 'rgba(255,255,255,.06)' : 'rgba(15,27,45,.06)',
        superficie: v('--he-superficie'),
        oro: '#C6A15B',
        oroClaro: '#E2C68E',
        noche: oscuro ? '#7FB0E3' : '#1F3452',
        verde: v('--he-verde'),
        azul: v('--he-azul'),
        ambar: v('--he-ambar'),
        rojo: v('--he-rojo'),
        violeta: v('--he-violeta'),
        gris: v('--he-gris'),
        serie: oscuro
          ? ['#C6A15B', '#7FB0E3', '#5CC293', '#E3A953', '#B49BE6', '#EE8A80', '#A2AAB8']
          : ['#1F3452', '#C6A15B', '#2F7D5B', '#B7791F', '#6B4FA0', '#B4443A', '#6B7280']
      };
    },

    graficos: [],

    /** Crea un gráfico con la configuración visual del sistema. */
    grafico(lienzo, configuracion) {
      if (!window.Chart || !lienzo) return null;
      const c = HE.colores();
      Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
      Chart.defaults.font.size = 11.5;
      Chart.defaults.color = c.texto;
      Chart.defaults.borderColor = c.rejilla;
      Chart.defaults.plugins.legend.labels.usePointStyle = true;
      Chart.defaults.plugins.legend.labels.boxWidth = 8;
      Chart.defaults.plugins.legend.labels.boxHeight = 8;
      Chart.defaults.plugins.tooltip.backgroundColor = '#0F1B2D';
      Chart.defaults.plugins.tooltip.titleColor = '#fff';
      Chart.defaults.plugins.tooltip.bodyColor = '#D5DAE3';
      Chart.defaults.plugins.tooltip.borderColor = 'rgba(198,161,91,.4)';
      Chart.defaults.plugins.tooltip.borderWidth = 1;
      Chart.defaults.plugins.tooltip.padding = 10;
      Chart.defaults.plugins.tooltip.cornerRadius = 10;
      Chart.defaults.plugins.tooltip.titleFont = { weight: '600' };
      Chart.defaults.maintainAspectRatio = false;
      const g = new Chart(lienzo, typeof configuracion === 'function' ? configuracion(c) : configuracion);
      g._fabrica = configuracion;
      HE.graficos.push(g);
      return g;
    },

    degradado(lienzo, color, alfaArriba = 0.28) {
      const ctx = lienzo.getContext('2d');
      const alto = lienzo.clientHeight || 300;
      const d = ctx.createLinearGradient(0, 0, 0, alto);
      d.addColorStop(0, HE.alfa(color, alfaArriba));
      d.addColorStop(1, HE.alfa(color, 0));
      return d;
    },

    alfa(color, a) {
      if (color.startsWith('#')) {
        const n = parseInt(color.slice(1), 16);
        return `rgba(${(n >> 16) & 255},${(n >> 8) & 255},${n & 255},${a})`;
      }
      return color;
    }
  };

  // ------------------------------------------------------------------
  // Tema claro / oscuro
  // ------------------------------------------------------------------
  function actualizarIconoTema() {
    const oscuro = raiz.getAttribute('data-bs-theme') === 'dark';
    document.querySelectorAll('[data-icono-tema]').forEach((i) => {
      i.className = 'bi ' + (oscuro ? 'bi-sun' : 'bi-moon-stars');
    });
  }
  actualizarIconoTema();

  function cambiarTema() {
    const nuevo = raiz.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
    raiz.setAttribute('data-bs-theme', nuevo);
    try { localStorage.setItem('he-tema', nuevo); } catch (e) { /* almacenamiento no disponible */ }
    actualizarIconoTema();
    // Redibuja los gráficos con la nueva paleta
    HE.graficos.forEach((g) => {
      if (typeof g._fabrica === 'function') {
        const nueva = g._fabrica(HE.colores());
        g.data = nueva.data;
        g.options = nueva.options;
      }
      g.update('none');
    });
  }

  // ------------------------------------------------------------------
  // Menú lateral
  // ------------------------------------------------------------------
  const tooltipsMenu = [];
  function sincronizarTooltipsMenu() {
    const compacta = raiz.classList.contains('sidebar-compacta') && window.innerWidth >= 992;
    tooltipsMenu.forEach((t) => (compacta ? t.enable() : t.disable()));
  }

  document.addEventListener('click', (evento) => {
    const disparador = evento.target.closest('[data-accion]');
    if (!disparador) return;
    const accion = disparador.dataset.accion;
    if (accion === 'cambiar-tema') cambiarTema();
    if (accion === 'abrir-sidebar') raiz.classList.add('sidebar-abierta');
    if (accion === 'cerrar-sidebar') raiz.classList.remove('sidebar-abierta');
    if (accion === 'compactar-sidebar') {
      raiz.classList.toggle('sidebar-compacta');
      try { localStorage.setItem('he-sidebar', raiz.classList.contains('sidebar-compacta') ? 'compacta' : 'normal'); } catch (e) { /* sin almacenamiento */ }
      sincronizarTooltipsMenu();
    }
    if (accion === 'imprimir') window.print();
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') raiz.classList.remove('sidebar-abierta');
  });

  // ------------------------------------------------------------------
  // Confirmaciones antes de acciones destructivas
  //   <form data-confirmar="¿Eliminar?" data-confirmar-texto="..." data-confirmar-boton="Eliminar">
  // ------------------------------------------------------------------
  document.addEventListener('submit', async (evento) => {
    const formulario = evento.target;
    if (formulario.dataset.confirmar && !formulario.dataset.confirmado) {
      evento.preventDefault();
      const ok = await HE.confirmar({
        titulo: formulario.dataset.confirmar,
        texto: formulario.dataset.confirmarTexto || '',
        boton: formulario.dataset.confirmarBoton || 'Confirmar',
        tipo: formulario.dataset.confirmarTipo || 'peligro',
        icono: formulario.dataset.confirmarIcono || 'bi-exclamation-triangle'
      });
      if (ok) {
        formulario.dataset.confirmado = '1';
        if (formulario.reportValidity()) formulario.requestSubmit ? formulario.requestSubmit() : formulario.submit();
      }
      return;
    }
    // Validación en el cliente con estilos de Bootstrap
    if (formulario.classList.contains('validar') && !formulario.checkValidity()) {
      evento.preventDefault();
      evento.stopPropagation();
      formulario.classList.add('was-validated');
      formulario.querySelector(':invalid')?.focus();
      HE.notificar('Revise los campos marcados en el formulario.', 'aviso');
      return;
    }
    // Evita envíos dobles
    const boton = formulario.querySelector('button[type="submit"]:not([data-no-bloquear])');
    if (boton && !formulario.dataset.permitirReenvio && formulario.method.toLowerCase() === 'post') {
      setTimeout(() => {
        if (evento.defaultPrevented) return;
        boton.disabled = true;
        boton.dataset.textoOriginal = boton.innerHTML;
        boton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Procesando…';
      }, 0);
    }
  }, true);

  // Filtros que se envían solos al cambiar
  document.addEventListener('change', (e) => {
    const campo = e.target.closest('[data-autoenviar]');
    if (campo && campo.form) campo.form.requestSubmit ? campo.form.requestSubmit() : campo.form.submit();
  });

  // Filas de tabla clicables
  document.addEventListener('click', (e) => {
    const fila = e.target.closest('tr[data-href]');
    if (fila && !e.target.closest('a, button, form, input, select, label')) {
      window.location.href = fila.dataset.href;
    }
  });

  // Mostrar / ocultar contraseña
  document.addEventListener('click', (e) => {
    const b = e.target.closest('[data-ver-clave]');
    if (!b) return;
    const campo = b.parentElement.querySelector('input');
    const visible = campo.type === 'text';
    campo.type = visible ? 'password' : 'text';
    b.innerHTML = `<i class="bi ${visible ? 'bi-eye' : 'bi-eye-slash'}"></i>`;
  });

  // ------------------------------------------------------------------
  // Búsqueda global con resultados en vivo (Ctrl + K)
  // ------------------------------------------------------------------
  const buscador = document.getElementById('buscador-global');
  const resultados = document.getElementById('buscador-resultados');
  if (buscador && resultados) {
    let temporizador = null;
    let indice = -1;
    document.addEventListener('keydown', (e) => {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        buscador.focus();
        buscador.select();
      }
    });
    const pintar = (datos) => {
      const grupos = [
        ['huespedes', 'Huéspedes', 'bi-person', 'chip-oro'],
        ['reservas', 'Reservas', 'bi-journal-bookmark', 'chip-azul'],
        ['habitaciones', 'Habitaciones', 'bi-door-closed', 'chip-verde']
      ];
      let html = '';
      grupos.forEach(([clave, titulo, icono, chip]) => {
        const lista = datos[clave] || [];
        if (!lista.length) return;
        html += `<div class="resultado-grupo">${titulo}</div>`;
        lista.forEach((r) => {
          html += `<a class="resultado-item" href="${HE.escapar(r.url)}"><span class="icono-chip ${chip}"><i class="bi ${icono}"></i></span><span class="min-w-0"><span class="d-block truncar fw-medio">${HE.escapar(r.titulo)}</span><small class="truncar">${HE.escapar(r.detalle || '')}</small></span></a>`;
        });
      });
      if (!html) html = '<div class="text-center py-4 texto-tenue fs-sm"><i class="bi bi-search d-block fs-4 mb-1"></i>Sin coincidencias</div>';
      resultados.innerHTML = html;
      resultados.classList.add('abierto');
      indice = -1;
    };
    buscador.addEventListener('input', () => {
      clearTimeout(temporizador);
      const q = buscador.value.trim();
      if (q.length < 2) { resultados.classList.remove('abierto'); return; }
      temporizador = setTimeout(async () => {
        try { pintar(await HE.api('panel/api/buscar?q=' + encodeURIComponent(q))); } catch (e) { /* silencioso */ }
      }, 220);
    });
    buscador.addEventListener('keydown', (e) => {
      const items = [...resultados.querySelectorAll('.resultado-item')];
      if (!items.length) return;
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        indice = (indice + (e.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length;
        items.forEach((it, i) => it.classList.toggle('activo', i === indice));
        items[indice].scrollIntoView({ block: 'nearest' });
      } else if (e.key === 'Enter' && indice >= 0) {
        e.preventDefault();
        window.location.href = items[indice].href;
      } else if (e.key === 'Escape') {
        resultados.classList.remove('abierto');
      }
    });
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.buscador')) resultados.classList.remove('abierto');
    });
  }

  // ------------------------------------------------------------------
  // Inicialización
  // ------------------------------------------------------------------
  document.addEventListener('DOMContentLoaded', () => {
    (window.HE_MENSAJES || []).forEach((m, i) => setTimeout(() => HE.notificar(m.texto, m.tipo), 150 + i * 180));
    if (window.bootstrap) {
      document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
        const t = new bootstrap.Tooltip(el, { trigger: 'hover' });
        if (el.hasAttribute('data-solo-compacta')) tooltipsMenu.push(t);
      });
      sincronizarTooltipsMenu();
    }
    // Animación de barras de progreso
    document.querySelectorAll('.barra-progreso > span[data-valor]').forEach((b) => {
      requestAnimationFrame(() => { b.style.width = Math.min(100, parseFloat(b.dataset.valor)) + '%'; });
    });
  });
  window.addEventListener('resize', sincronizarTooltipsMenu);
})();
