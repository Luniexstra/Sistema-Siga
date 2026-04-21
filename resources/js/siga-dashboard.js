const dashboard = document.querySelector('[data-siga-dashboard]');

if (dashboard) {
    const userIdInput = document.querySelector('#user-id-input');
    const connectButton = document.querySelector('#connect-button');
    const refreshButton = document.querySelector('#refresh-button');
    const currentUserName = document.querySelector('#current-user-name');
    const currentUserMeta = document.querySelector('#current-user-meta');
    const currentUserRole = document.querySelector('#current-user-role');
    const activityLog = document.querySelector('#activity-log');
    const downloadPdfButton = document.querySelector('#download-pdf-button');
    const estadoCuentaForm = document.querySelector('#estado-cuenta-form');
    const estadoCuentaSummary = document.querySelector('#estado-cuenta-summary');
    const statNodes = {
        alumnos: document.querySelector('#stat-alumnos'),
        clases: document.querySelector('#stat-clases'),
        evaluaciones: document.querySelector('#stat-evaluaciones'),
        pagos: document.querySelector('#stat-pagos'),
    };

    const endpointConfig = {
        alumnos: { path: '/api/alumnos', target: null },
        clases: { path: '/api/clases', target: 'clases-table' },
        evaluaciones: { path: '/api/evaluaciones', target: 'evaluaciones-list' },
        pagos: { path: '/api/pagos', target: 'pagos-list' },
        observaciones: { path: '/api/observaciones', target: 'observaciones-list' },
    };

    const state = {
        userId: sessionStorage.getItem('siga-user-id') ?? '',
        currentUser: null,
        lastEstadoCuentaAlumnoId: null,
    };

    userIdInput.value = state.userId;

    connectButton?.addEventListener('click', async () => {
        state.userId = userIdInput.value.trim();
        sessionStorage.setItem('siga-user-id', state.userId);
        await hydrateDashboard();
    });

    refreshButton?.addEventListener('click', async () => {
        await hydrateDashboard();
    });

    document.querySelectorAll('form[data-endpoint]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const payload = Object.fromEntries(new FormData(form).entries());

            try {
                const response = await api(form.dataset.endpoint, {
                    method: form.dataset.method ?? 'POST',
                    body: JSON.stringify(payload),
                });

                addLog('Operacion exitosa', `${form.dataset.endpoint} respondio ${response.status}.`);
                form.reset();
                await hydrateDashboard(false);
            } catch (error) {
                addLog('Error de captura', normaliseError(error), true);
            }
        });
    });

    estadoCuentaForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const alumnoId = new FormData(estadoCuentaForm).get('alumno_id');

        if (!alumnoId) {
            return;
        }

        state.lastEstadoCuentaAlumnoId = alumnoId.toString();

        try {
            const data = await apiJson(`/api/alumnos/${alumnoId}/estado-cuenta`);
            renderEstadoCuenta(data);
            addLog('Estado de cuenta consultado', `Alumno ${alumnoId} cargado correctamente.`);
        } catch (error) {
            estadoCuentaSummary.innerHTML = `<p class="siga-log__placeholder">${normaliseError(error)}</p>`;
            addLog('Error de estado de cuenta', normaliseError(error), true);
        }
    });

    downloadPdfButton?.addEventListener('click', async () => {
        if (!state.lastEstadoCuentaAlumnoId) {
            addLog('PDF pendiente', 'Primero consulta un estado de cuenta para saber que alumno descargar.', true);
            return;
        }

        try {
            const response = await api(`/api/alumnos/${state.lastEstadoCuentaAlumnoId}/estado-cuenta/pdf`);
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const anchor = document.createElement('a');
            anchor.href = url;
            anchor.download = `estado-cuenta-alumno-${state.lastEstadoCuentaAlumnoId}.pdf`;
            anchor.click();
            window.URL.revokeObjectURL(url);
            addLog('PDF generado', `Se descargo el comprobante del alumno ${state.lastEstadoCuentaAlumnoId}.`);
        } catch (error) {
            addLog('Error al descargar PDF', normaliseError(error), true);
        }
    });

    hydrateDashboard();

    async function hydrateDashboard(logSuccess = true) {
        if (!state.userId) {
            currentUserName.textContent = 'Sin usuario conectado';
            currentUserMeta.textContent = 'Captura un X-User-Id para consumir la API con permisos.';
            currentUserRole.textContent = 'Sin rol';
            return;
        }

        try {
            state.currentUser = await apiJson('/api/me');
            currentUserName.textContent = state.currentUser.name;
            currentUserMeta.textContent = `${state.currentUser.email} · ID ${state.currentUser.id}`;
            currentUserRole.textContent = state.currentUser.role;

            await Promise.all([
                loadMetric('alumnos'),
                loadMetric('clases'),
                loadMetric('evaluaciones'),
                loadMetric('pagos'),
                loadList('clases'),
                loadList('evaluaciones'),
                loadList('pagos'),
                loadList('observaciones'),
            ]);

            if (logSuccess) {
                addLog('Panel actualizado', `Datos recargados para el rol ${state.currentUser.role}.`);
            }
        } catch (error) {
            currentUserName.textContent = 'Conexion rechazada';
            currentUserMeta.textContent = normaliseError(error);
            currentUserRole.textContent = 'Sin acceso';
            addLog('Error de conexion', normaliseError(error), true);
        }
    }

    async function loadMetric(key) {
        const { path } = endpointConfig[key];

        try {
            const data = await apiJson(path);
            statNodes[key].textContent = Array.isArray(data) ? data.length : 0;
        } catch (error) {
            statNodes[key].textContent = '--';
        }
    }

    async function loadList(key) {
        const { path, target } = endpointConfig[key];

        if (!target) {
            return;
        }

        const node = document.querySelector(`#${target}`);

        try {
            const data = await apiJson(path);
            renderList(key, Array.isArray(data) ? data : [], node);
        } catch (error) {
            renderErrorPlaceholder(node, normaliseError(error));
        }
    }

    function renderList(key, items, node) {
        if (!node) {
            return;
        }

        if (key === 'clases') {
            node.innerHTML = items.length
                ? items.slice(0, 8).map((item) => `
                    <tr>
                        <td>${item.id}</td>
                        <td>${item.fecha} ${item.hora}</td>
                        <td>${item.alumno?.nombre ?? item.alumno_id}</td>
                        <td>${item.instructor?.name ?? item.instructor_id}</td>
                    </tr>
                `).join('')
                : '<tr><td colspan="4">Sin clases disponibles.</td></tr>';

            return;
        }

        if (!items.length) {
            renderErrorPlaceholder(node, 'Sin datos disponibles para este modulo.');
            return;
        }

        node.innerHTML = items.slice(0, 6).map((item) => {
            if (key === 'evaluaciones') {
                return cardTemplate(
                    `Clase ${item.clase_id}`,
                    `Promedio ${item.promedio} · Nivel ${pill(item.nivel)}`
                );
            }

            if (key === 'pagos') {
                return cardTemplate(
                    `Alumno ${item.alumno?.nombre ?? item.alumno_id}`,
                    `$${item.monto} · ${pill(item.estado)} · ${item.fecha_pago}`
                );
            }

            if (key === 'observaciones') {
                return cardTemplate(
                    `Clase ${item.clase_id}`,
                    item.comentario
                );
            }

            return '';
        }).join('');
    }

    function renderEstadoCuenta(data) {
        const { alumno, resumen, pagos } = data;
        const pagosHtml = pagos.length
            ? pagos.map((pago) => `
                <li>${pago.fecha_pago} · $${pago.monto} · ${pago.estado}</li>
            `).join('')
            : '<li>Sin movimientos registrados.</li>';

        estadoCuentaSummary.innerHTML = `
            <div class="siga-account__card">
                <strong>${alumno.nombre} ${alumno.apellido}</strong>
                <p>Costo total: $${resumen.costo_total}</p>
                <p>Total pagado: $${resumen.total_pagado}</p>
                <p>Saldo pendiente: $${resumen.saldo_pendiente}</p>
                <p>Estado: ${pill(resumen.estado_cuenta)}</p>
                <ul class="siga-mini-list">${pagosHtml}</ul>
            </div>
        `;
    }

    function renderErrorPlaceholder(node, message) {
        if (!node) {
            return;
        }

        node.innerHTML = `<p class="siga-log__placeholder">${message}</p>`;
    }

    function addLog(title, message, error = false) {
        if (!activityLog) {
            return;
        }

        const item = document.createElement('div');
        item.className = 'siga-log__item';
        item.innerHTML = `
            <strong>${title}</strong>
            <p>${message}</p>
            <span class="siga-status-pill ${error ? 'siga-status-pill--rojo' : 'siga-status-pill--verde'}">
                ${error ? 'Atencion' : 'OK'}
            </span>
        `;

        activityLog.prepend(item);

        if (activityLog.children.length > 10) {
            activityLog.removeChild(activityLog.lastElementChild);
        }
    }

    function cardTemplate(title, body) {
        return `
            <article class="siga-stack__item">
                <strong>${title}</strong>
                <p>${body}</p>
            </article>
        `;
    }

    function pill(status) {
        const safeStatus = `${status}`.toLowerCase();
        return `<span class="siga-status-pill siga-status-pill--${safeStatus}">${status}</span>`;
    }

    async function apiJson(url, options = {}) {
        const response = await api(url, options);
        return response.json();
    }

    async function api(url, options = {}) {
        const response = await fetch(url, {
            ...options,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-User-Id': state.userId,
                ...(options.headers ?? {}),
            },
        });

        if (!response.ok) {
            let payload = null;

            try {
                payload = await response.json();
            } catch {
                payload = null;
            }

            throw new Error(
                payload?.message ??
                payload?.error ??
                `La API respondio con status ${response.status}.`
            );
        }

        return response;
    }

    function normaliseError(error) {
        return error instanceof Error ? error.message : 'Ocurrio un error inesperado.';
    }
}
