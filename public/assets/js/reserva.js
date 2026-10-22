/* =====================================================================
   Asistente de nueva reserva (CU-11)
   1) Consulta de disponibilidad (CU-12)  2) Selección de habitaciones
   3) Huésped: búsqueda o registro rápido (CU-05)  4) Confirmación
   Muestra la alerta del modelo de sobreventa cuando corresponde (CU-19).
   ===================================================================== */
(function () {
  'use strict';
  const cfg = window.HE_RESERVA || {};
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];
  const form = $('#form-reserva');
  const ingreso = $('#fecha_ingreso');
  const salida = $('#fecha_salida');
  const personas = $('#cantidad_personas');
  const lista = $('#lista-habitaciones');
  const alertaCaja = $('#alerta-sobreventa');
  const idHuesped = $('#id_huesped');
  let datos = null;
  let huesped = cfg.huesped || null;
  let seleccion = new Set((cfg.preseleccion || []).map(Number));
  let sobreventa = cfg.sobreventa || 0;

  const fechaTexto = (f) => {
    const d = new Date(f + 'T12:00:00');
    return d.toLocaleDateString('es-BO', { day: 'numeric', month: 'short' });
  };

  function marcarPaso() {
    const hechos = [!!datos, seleccion.size > 0 || sobreventa > 0, !!idHuesped.value, false];
    $$('#pasos .paso').forEach((p, i) => {
      p.classList.toggle('hecho', hechos[i]);
      p.classList.toggle('activo', !hechos[i] && hechos.slice(0, i).every(Boolean));
    });
  }

  // --------------------------------------------------------------
  // 1. Disponibilidad
  // --------------------------------------------------------------
  async function consultar() {
    $('#error-fechas').textContent = '';
    if (!ingreso.value || !salida.value) return;
    if (salida.value <= ingreso.value) {
      $('#error-fechas').textContent = 'La fecha de salida debe ser posterior a la fecha de ingreso.';
      return;
    }
    lista.innerHTML = '<div class="text-center py-4 texto-tenue"><span class="spinner-border spinner-border-sm"></span> Consultando disponibilidad…</div>';
    try {
      datos = await HE.api(`panel/api/disponibilidad?ingreso=${ingreso.value}&salida=${salida.value}&personas=${personas.value}`);
      pintarHabitaciones();
      pintarAlerta();
    } catch (e) {
      datos = null;
      lista.innerHTML = `<div class="aviso aviso-rojo"><i class="bi bi-exclamation-circle"></i><div><strong>${HE.escapar(e.message)}</strong></div></div>`;
      alertaCaja.innerHTML = '';
    }
    resumen();
  }

  function pintarHabitaciones() {
    const libres = datos.categorias.reduce((s, c) => s + c.habitaciones.length, 0);
    $('#contador-libres').textContent = libres + ' libres · ' + datos.noches + ' noche(s)';
    const disponibles = new Set(datos.categorias.flatMap((c) => c.habitaciones.map((h) => h.id)));
    seleccion = new Set([...seleccion].filter((id) => disponibles.has(id)));
    const alerta = datos.alerta;
    const sobrevendible = alerta && (alerta.decision === 'ACEPTAR' || datos.puede_autorizar);
    let html = '';
    datos.categorias.forEach((c) => {
      const temporadas = c.temporadas.length ? `<span class="insignia insignia-oro"><i class="bi bi-tag"></i>${HE.escapar(c.temporadas.join(', '))}</span>` : '';
      const destacada = cfg.categoriaSolicitud === c.id ? ' style="border-color: var(--he-oro-500)!important"' : '';
      html += `<div class="rounded-4 border p-3 mb-3"${destacada}>
        <div class="d-flex flex-wrap gap-3 align-items-center mb-2">
          <img src="${c.imagen}" alt="" width="76" height="56" style="object-fit:cover;border-radius:10px">
          <div class="flex-grow-1 min-w-0"><div class="d-flex flex-wrap gap-2 align-items-center"><strong class="fs-6">${HE.escapar(c.nombre)}</strong>${temporadas}</div>
            <div class="fs-xs texto-tenue">${c.capacidad} pers. · ${HE.escapar(c.camas || '')} · ${HE.bs(c.promedio, 0)} / noche promedio</div></div>
          <div class="text-end"><div class="fw-semibold tabular">${HE.bs(c.total)}</div><div class="fs-xs texto-tenue">${datos.noches} noche(s)</div></div>
        </div>`;
      if (c.habitaciones.length) {
        html += '<div class="d-flex flex-wrap gap-2">';
        c.habitaciones.forEach((h) => {
          html += `<label class="opcion-tarjeta habitacion-opcion" style="min-width:128px">
            <input type="checkbox" name="habitaciones[]" value="${h.id}" data-categoria="${c.id}" ${seleccion.has(h.id) ? 'checked' : ''}>
            <span class="contenido py-2"><span class="numero">${HE.escapar(h.numero)}</span><span class="fs-xs texto-tenue lh-sm">Piso ${h.piso}${h.estado === 'LIMPIEZA' ? '<br><span class="texto-oro">En limpieza</span>' : ''}</span></span></label>`;
        });
        html += '</div>';
      } else {
        html += '<div class="d-flex flex-wrap align-items-center gap-2 fs-sm"><span class="insignia insignia-rojo"><i class="bi bi-x-circle"></i>Sin habitaciones libres</span>';
        if (alerta) {
          html += sobrevendible
            ? `<label class="form-check ms-2 mb-0"><input class="form-check-input" type="radio" name="sobreventa_categoria" value="${c.id}" ${sobreventa === c.id ? 'checked' : ''}> <span class="form-check-label">Aceptar en <strong>sobreventa</strong> (sin habitación asignada)</span></label>`
            : '<span class="texto-tenue">El modelo recomienda rechazar: sólo un administrador puede sobrevender.</span>';
        }
        html += '</div>';
      }
      html += '</div>';
    });
    if (sobrevendible) {
      html += `<label class="form-check fs-xs texto-tenue"><input class="form-check-input" type="radio" name="sobreventa_categoria" value="0" ${!sobreventa ? 'checked' : ''}> <span class="form-check-label">No usar sobreventa</span></label>`;
    }
    lista.innerHTML = html;
  }

  function pintarAlerta() {
    const a = datos && datos.alerta;
    $('#alerta_vista').value = a ? '1' : '';
    if (!a) { alertaCaja.innerHTML = ''; return; }
    const aceptar = a.decision === 'ACEPTAR';
    alertaCaja.innerHTML = `
      <div class="veredicto ${aceptar ? 'veredicto-aceptar' : 'veredicto-rechazar'} mb-4">
        <span class="icono-chip"><i class="bi ${aceptar ? 'bi-shield-check' : 'bi-shield-x'}"></i></span>
        <div class="flex-grow-1">
          <div class="sobretitulo" style="color:inherit">Alerta de sobreventa · CU-19</div>
          <h3>La ocupación del ${HE.escapar(a.fecha_texto)} llega al ${HE.numero(a.ocupacion, 0)} % (umbral ${HE.numero(a.umbral, 0)} %)</h3>
          <div class="fs-sm mt-1">${a.reservas} reservas para ${a.capacidad} habitaciones · no-show histórico ${HE.numero(a.p_no_show, 1)} % ·
            prob. de que se libere una habitación ${HE.numero(a.p_libera, 1)} %</div>
          <div class="fs-sm">VE(aceptar) = <strong>${HE.bs(a.ve_aceptar)}</strong> frente a VE(rechazar) = Bs 0 ·
            Recomendación del modelo: <strong>${aceptar ? 'ACEPTAR' : 'RECHAZAR'}</strong>. ${HE.escapar(a.motivo)}</div>
        </div>
      </div>`;
  }

  // --------------------------------------------------------------
  // 2. Resumen
  // --------------------------------------------------------------
  function resumen() {
    $('#r-fechas').textContent = ingreso.value && salida.value ? `${fechaTexto(ingreso.value)} → ${fechaTexto(salida.value)}` : '—';
    $('#r-noches').textContent = datos ? datos.noches : '—';
    $('#r-huesped').textContent = huesped ? `${huesped.nombres} ${huesped.apellidos}` : 'Sin seleccionar';
    let total = 0;
    let html = '';
    if (datos) {
      datos.categorias.forEach((c) => {
        c.habitaciones.filter((h) => seleccion.has(h.id)).forEach((h) => {
          total += c.total;
          html += `<div class="resumen-linea"><span>Hab. <strong>${HE.escapar(h.numero)}</strong> · ${HE.escapar(c.nombre)}</span><span class="tabular">${HE.bs(c.total)}</span></div>`;
        });
        if (sobreventa === c.id) {
          total += c.total;
          html += `<div class="resumen-linea"><span>${HE.escapar(c.nombre)} <span class="insignia insignia-violeta">sobreventa</span></span><span class="tabular">${HE.bs(c.total)}</span></div>`;
        }
      });
    }
    $('#r-habitaciones').innerHTML = html || '<span class="texto-tenue">Ninguna habitación seleccionada</span>';
    $('#r-total').textContent = HE.bs(total);
    $('#r-anticipo').textContent = total ? `Anticipo sugerido (${cfg.anticipoPorcentaje} %): ${HE.bs(total * cfg.anticipoPorcentaje / 100)}` : '';
    $('#anticipo').max = total || '';
    marcarPaso();
  }

  lista.addEventListener('change', (e) => {
    if (e.target.name === 'habitaciones[]') {
      const id = Number(e.target.value);
      e.target.checked ? seleccion.add(id) : seleccion.delete(id);
    }
    if (e.target.name === 'sobreventa_categoria') sobreventa = Number(e.target.value);
    resumen();
  });

  // --------------------------------------------------------------
  // 3. Huésped
  // --------------------------------------------------------------
  function pintarHuesped() {
    const caja = $('#huesped-seleccionado');
    if (!huesped) {
      caja.innerHTML = '';
      $('#huesped-buscador').classList.remove('d-none');
      idHuesped.value = '';
    } else {
      idHuesped.value = huesped.id_huesped;
      $('#huesped-buscador').classList.add('d-none');
      const iniciales = (huesped.nombres[0] || '') + (huesped.apellidos[0] || '');
      caja.innerHTML = `<div class="d-flex align-items-center gap-3 p-3 rounded-4" style="background: var(--he-oro-suave); border: 1px solid rgba(198,161,91,.35)">
        <span class="avatar">${HE.escapar(iniciales.toUpperCase())}</span>
        <div class="flex-grow-1 min-w-0"><div class="fw-semibold">${HE.escapar(huesped.nombres + ' ' + huesped.apellidos)}</div>
          <div class="fs-xs texto-suave">${HE.escapar((huesped.sigla || 'Doc.') + ' ' + huesped.nro_documento)} · ${HE.escapar(huesped.nacionalidad || '')} · ${HE.escapar(huesped.ciudad_procedencia || '')}</div></div>
        <button type="button" class="btn btn-claro btn-sm" id="cambiar-huesped"><i class="bi bi-arrow-left-right"></i> Cambiar</button></div>`;
      $('#cambiar-huesped').addEventListener('click', () => { huesped = null; pintarHuesped(); resumen(); $('#buscar-huesped').focus(); });
    }
    resumen();
  }

  let temporizador = null;
  $('#buscar-huesped').addEventListener('input', (e) => {
    clearTimeout(temporizador);
    const q = e.target.value.trim();
    const caja = $('#resultados-huesped');
    if (q.length < 2) { caja.innerHTML = ''; return; }
    temporizador = setTimeout(async () => {
      try {
        const r = await HE.api('panel/api/huespedes?q=' + encodeURIComponent(q));
        if (!r.huespedes.length) {
          caja.innerHTML = '<div class="fs-sm texto-tenue py-2"><i class="bi bi-person-x"></i> No se encontró el huésped. Regístrelo con el botón de abajo.</div>';
          if (/^[A-Za-z0-9-]{4,}$/.test(q)) $('[data-h="nro_documento"]').value = q;
          return;
        }
        caja.innerHTML = '<div class="border rounded-4 overflow-hidden">' + r.huespedes.map((h, i) =>
          `<a href="#" class="item-lista py-2" data-indice="${i}"><span class="avatar avatar-sm avatar-noche">${HE.escapar((h.nombres[0] + h.apellidos[0]).toUpperCase())}</span>
            <span class="flex-grow-1 min-w-0"><span class="d-block fw-medio">${HE.escapar(h.nombres + ' ' + h.apellidos)}</span>
            <span class="d-block fs-xs texto-tenue">${HE.escapar(h.sigla + ' ' + h.nro_documento + ' · ' + h.ciudad_procedencia)}</span></span>
            <span class="insignia insignia-gris">${h.estadias} estadías</span></a>`).join('') + '</div>';
        $$('[data-indice]', caja).forEach((a) => a.addEventListener('click', (ev) => {
          ev.preventDefault();
          huesped = r.huespedes[Number(a.dataset.indice)];
          caja.innerHTML = '';
          $('#buscar-huesped').value = '';
          pintarHuesped();
        }));
      } catch (err) { caja.innerHTML = ''; }
    }, 250);
  });

  $('#btn-registrar-huesped').addEventListener('click', async () => {
    const cuerpo = {};
    $$('[data-h]').forEach((c) => { cuerpo[c.dataset.h] = c.value.trim(); });
    $('#error-huesped').textContent = '';
    try {
      const r = await HE.api('panel/api/huespedes', { method: 'POST', body: JSON.stringify(cuerpo) });
      huesped = r.huesped;
      HE.notificar(r.mensaje, r.existente ? 'info' : 'exito');
      bootstrap.Collapse.getOrCreateInstance('#nuevo-huesped').hide();
      pintarHuesped();
    } catch (e) {
      const errores = e.datos && e.datos.errores ? Object.values(e.datos.errores) : [e.message];
      $('#error-huesped').textContent = errores.join(' ');
    }
  });

  // --------------------------------------------------------------
  // 4. Confirmación y envío
  // --------------------------------------------------------------
  $$('[data-seg]').forEach((b) => b.parentElement.querySelector('input').addEventListener('change', () => {
    $$('[data-seg]').forEach((x) => x.classList.toggle('active', x.parentElement.querySelector('input').checked));
  }));
  $('#id_metodo_pago').addEventListener('change', (e) => {
    $('#referencia').placeholder = e.target.selectedOptions[0].dataset.ref === '1' ? 'Requerido para este método' : 'Opcional';
  });

  form.addEventListener('submit', (e) => {
    const errores = [];
    if (!datos) errores.push('Consulte la disponibilidad para las fechas elegidas.');
    if (!seleccion.size && !sobreventa) errores.push('Seleccione al menos una habitación.');
    if (!idHuesped.value) errores.push('Seleccione o registre al huésped titular.');
    if (!$('input[name="id_canal"]:checked')) errores.push('Indique el canal de la reserva.');
    if (errores.length) {
      e.preventDefault();
      e.stopImmediatePropagation();
      HE.notificar(errores[0], 'aviso');
    }
  }, true);

  // Inicialización
  [ingreso, salida, personas].forEach((c) => c.addEventListener('change', () => {
    if (c === ingreso && salida.value <= ingreso.value) {
      const d = new Date(ingreso.value + 'T12:00:00');
      d.setDate(d.getDate() + 1);
      salida.value = d.toISOString().slice(0, 10);
    }
    consultar();
  }));
  $('#btn-consultar').addEventListener('click', consultar);
  if (!huesped && cfg.idHuesped) huesped = null;
  pintarHuesped();
  if (cfg.idHuesped && !huesped) idHuesped.value = cfg.idHuesped;
  if (ingreso.value && salida.value) consultar();
})();
