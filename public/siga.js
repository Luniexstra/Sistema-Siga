(function () {
    const app = document.querySelector('[data-siga-app]');

    if (!app) {
        return;
    }

    const page = document.querySelector('[data-siga-page]');
    const pageType = page ? page.getAttribute('data-siga-page') : 'home';
    const resource = page ? page.getAttribute('data-siga-resource') : null;
    const refreshButton = document.querySelector('#refresh-button');
    const currentUserName = document.querySelector('#current-user-name');
    const currentUserMeta = document.querySelector('#current-user-meta');
    const currentUserRole = document.querySelector('#current-user-role');
    const activityLog = document.querySelector('#activity-log');
    const downloadPdfButton = document.querySelector('#download-pdf-button');
    const estadoCuentaForm = document.querySelector('#estado-cuenta-form');
    const estadoCuentaSummary = document.querySelector('#estado-cuenta-summary');
    const editModal = document.querySelector('#edit-modal');
    const editModalTitle = document.querySelector('#edit-modal-title');
    const editModalFields = document.querySelector('#edit-modal-fields');
    const editModalForm = document.querySelector('#edit-modal-form');

    const state = {
        userId: app.getAttribute('data-auth-user-id') || '',
        currentUser: {
            id: app.getAttribute('data-auth-user-id') || '',
            role: app.getAttribute('data-auth-role') || '',
            name: app.getAttribute('data-auth-name') || '',
            email: app.getAttribute('data-auth-email') || '',
            alumno: app.getAttribute('data-auth-alumno-id')
                ? { id: Number(app.getAttribute('data-auth-alumno-id')) }
                : null,
        },
        catalogs: {},
        lastEstadoCuentaAlumnoId: null,
        editing: null,
    };

    const resourceConfig = {
        alumnos: {
            endpoint: '/api/alumnos',
            type: 'table',
            emptyCols: 6,
            fields: [
                { key: 'nombre', label: 'Nombre' },
                { key: 'apellido', label: 'Apellido' },
                { key: 'curp', label: 'CURP' },
                { key: 'telefono', label: 'Telefono' },
                { key: 'correo', label: 'Correo' },
                { key: 'fecha_ingreso', label: 'Fecha de ingreso' },
                { key: 'costo_total', label: 'Costo total' },
            ],
        },
        clases: {
            endpoint: '/api/clases',
            type: 'table',
            emptyCols: 5,
            fields: [
                { key: 'fecha', label: 'Fecha' },
                { key: 'hora', label: 'Hora' },
                { key: 'alumno_id', label: 'Alumno', catalog: 'alumnos' },
                { key: 'instructor_id', label: 'Instructor', catalog: 'instructores' },
            ],
        },
        evaluaciones: {
            endpoint: '/api/evaluaciones',
            type: 'list',
            fields: [
                { key: 'clase_id', label: 'Clase', catalog: 'clases' },
                { key: 'senales', label: 'Senales' },
                { key: 'frenado', label: 'Frenado' },
                { key: 'seguridad', label: 'Seguridad' },
            ],
        },
        observaciones: {
            endpoint: '/api/observaciones',
            type: 'list',
            fields: [
                { key: 'clase_id', label: 'Clase', catalog: 'clases' },
                { key: 'comentario', label: 'Comentario' },
            ],
        },
        pagos: {
            endpoint: '/api/pagos',
            type: 'list',
            fields: [
                { key: 'alumno_id', label: 'Alumno', catalog: 'alumnos' },
                { key: 'monto', label: 'Monto' },
                { key: 'fecha_pago', label: 'Fecha de pago' },
                { key: 'estado', label: 'Estado', options: ['pagado', 'pendiente'] },
            ],
        },
        users: {
            endpoint: '/api/users',
            type: 'table',
            emptyCols: 5,
            fields: [
                { key: 'name', label: 'Nombre' },
                { key: 'email', label: 'Correo' },
                { key: 'role', label: 'Rol', options: ['administrador', 'recepcionista', 'instructor', 'alumno'] },
                { key: 'password', label: 'Contrasena nueva', optional: true },
            ],
        },
    };

    const catalogConfig = {
        alumnos: {
            endpoint: '/api/alumnos',
            map: function (item) {
                return {
                    value: item.id,
                    label: item.nombre + ' ' + item.apellido + ' · ' + item.correo,
                };
            },
        },
        instructores: {
            endpoint: '/api/catalogos/instructores',
            map: function (item) {
                return {
                    value: item.id,
                    label: item.name + ' · ' + item.email,
                };
            },
        },
        clases: {
            endpoint: '/api/clases',
            map: function (item) {
                const alumno = item.alumno ? item.alumno.nombre + ' ' + item.alumno.apellido : 'Alumno #' + item.alumno_id;
                return {
                    value: item.id,
                    label: item.fecha + ' ' + item.hora + ' · ' + alumno,
                };
            },
        },
    };

    if (refreshButton) {
        refreshButton.addEventListener('click', async function () {
            await hydratePage();
        });
    }

    Array.prototype.slice.call(document.querySelectorAll('form[data-endpoint]')).forEach(function (form) {
        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            clearFormFeedback(form);
            const payload = sanitisePayload(Object.fromEntries(new FormData(form).entries()));

            try {
                const response = await api(form.dataset.endpoint, {
                    method: form.dataset.method || 'POST',
                    body: JSON.stringify(payload),
                });

                showFormFeedback(form, 'Registro guardado correctamente.', false);
                addLog('Operacion exitosa', form.dataset.endpoint + ' respondio ' + response.status + '.');
                form.reset();
                await populatePageCatalogs();
                await hydratePage(false);
            } catch (error) {
                showFormFeedback(form, buildValidationMessage(error), true);
                addLog('Error de captura', normaliseError(error), true);
            }
        });
    });

    if (estadoCuentaForm) {
        estadoCuentaForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            const alumnoId = new FormData(estadoCuentaForm).get('alumno_id');

            if (!alumnoId) {
                return;
            }

            state.lastEstadoCuentaAlumnoId = String(alumnoId);

            try {
                const data = await apiJson('/api/alumnos/' + alumnoId + '/estado-cuenta');
                renderEstadoCuenta(data);
                addLog('Estado de cuenta consultado', 'Alumno ' + alumnoId + ' cargado correctamente.');
            } catch (error) {
                estadoCuentaSummary.innerHTML = '<p class="siga-log__placeholder">' + escapeHtml(normaliseError(error)) + '</p>';
                addLog('Error de estado de cuenta', normaliseError(error), true);
            }
        });
    }

    if (downloadPdfButton) {
        downloadPdfButton.addEventListener('click', async function () {
            if (!state.lastEstadoCuentaAlumnoId) {
                addLog('PDF pendiente', 'Primero consulta un estado de cuenta antes de descargar el comprobante.', true);
                return;
            }

            try {
                const response = await api('/api/alumnos/' + state.lastEstadoCuentaAlumnoId + '/estado-cuenta/pdf');
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const anchor = document.createElement('a');
                anchor.href = url;
                anchor.download = 'estado-cuenta-alumno-' + state.lastEstadoCuentaAlumnoId + '.pdf';
                anchor.click();
                window.URL.revokeObjectURL(url);
                addLog('PDF generado', 'Se descargo el comprobante del alumno ' + state.lastEstadoCuentaAlumnoId + '.');
            } catch (error) {
                addLog('Error al descargar PDF', normaliseError(error), true);
            }
        });
    }

    document.addEventListener('click', async function (event) {
        const closeButton = event.target.closest('[data-modal-close]');

        if (closeButton) {
            closeEditModal();
            return;
        }

        const actionButton = event.target.closest('[data-action]');

        if (!actionButton) {
            return;
        }

        const action = actionButton.getAttribute('data-action');
        const resourceName = actionButton.getAttribute('data-resource');
        const itemId = actionButton.getAttribute('data-id');

        if (!resourceName || !itemId) {
            return;
        }

        if (action === 'delete') {
            await handleDelete(resourceName, itemId);
            return;
        }

        if (action === 'edit') {
            await handleEdit(resourceName, itemId);
        }
    });

    if (editModalForm) {
        editModalForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            if (!state.editing) {
                return;
            }

            clearFormFeedback(editModalForm);
            const payload = sanitisePayload(Object.fromEntries(new FormData(editModalForm).entries()));

            Object.keys(payload).forEach(function (key) {
                if (payload[key] === '' && state.editing.optionalKeys.indexOf(key) !== -1) {
                    delete payload[key];
                }
            });

            try {
                await api(state.editing.endpoint + '/' + state.editing.id, {
                    method: 'PUT',
                    body: JSON.stringify(payload),
                });

                addLog('Registro actualizado', state.editing.name + ' #' + state.editing.id + ' se actualizo correctamente.');
                closeEditModal();
                await populatePageCatalogs();
                await hydratePage(false);
            } catch (error) {
                showFormFeedback(editModalForm, buildValidationMessage(error), true);
                addLog('Error al editar', normaliseError(error), true);
            }
        });
    }

    hydratePage();

    async function hydratePage(logSuccess) {
        if (logSuccess === undefined) {
            logSuccess = true;
        }

        try {
            state.currentUser = await apiJson('/api/me');
            syncCurrentUser();
            syncRoleSpecificUi();
            await populatePageCatalogs();

            if (pageType === 'home') {
                await loadStats();
                await loadHomeInsights();
            }

            if (resource) {
                await loadResource(resource);
            }

            if (pageType === 'pagos') {
                await loadResource('pagos');
                await maybeLoadOwnEstadoCuenta();
            }

            if (logSuccess) {
                addLog('Panel actualizado', 'Datos recargados para el rol ' + state.currentUser.role + '.');
            }
        } catch (error) {
            currentUserName.textContent = 'Conexion rechazada';
            currentUserMeta.textContent = normaliseError(error);
            currentUserRole.textContent = 'Sin acceso';
            renderProtectedPlaceholders(normaliseError(error));
            addLog('Error de conexion', normaliseError(error), true);
        }
    }

    async function loadStats() {
        await Promise.all([
            setCountStat('alumnos', '/api/alumnos', canReadStat('alumnos')),
            setCountStat('clases', '/api/clases', canReadStat('clases')),
            setCountStat('evaluaciones', '/api/evaluaciones', canReadStat('evaluaciones')),
            setCountStat('pagos', '/api/pagos', canReadStat('pagos')),
            setDerivedStat('riesgo-rojo', '/api/evaluaciones', canReadStat('evaluaciones'), function (items) {
                return items.filter(function (item) {
                    return item.nivel === 'rojo';
                }).length;
            }),
            setDerivedStat('pagos-pendientes', '/api/pagos', canReadStat('pagos'), function (items) {
                return items.filter(function (item) {
                    return item.estado === 'pendiente';
                }).length;
            }),
        ]);
    }

    async function setCountStat(key, endpoint, allowed) {
        const node = document.querySelector('#stat-' + key);

        if (!node) {
            return;
        }

        if (!allowed) {
            node.textContent = '--';
            return;
        }

        try {
            const data = await apiJson(endpoint);
            node.textContent = Array.isArray(data) ? data.length : 0;
        } catch (error) {
            node.textContent = '--';
        }
    }

    async function setDerivedStat(key, endpoint, allowed, resolver) {
        const node = document.querySelector('#stat-' + key);

        if (!node) {
            return;
        }

        if (!allowed) {
            node.textContent = '--';
            return;
        }

        try {
            const data = await apiJson(endpoint);
            node.textContent = Array.isArray(data) ? resolver(data) : 0;
        } catch (error) {
            node.textContent = '--';
        }
    }

    async function loadHomeInsights() {
        await Promise.all([
            renderSemaforoOverview(),
            renderUpcomingClasses(),
            renderPortfolioOverview(),
        ]);
    }

    async function renderSemaforoOverview() {
        const node = document.querySelector('#home-semaforo');

        if (!node) {
            return;
        }

        if (!canReadStat('evaluaciones')) {
            node.innerHTML = '<p class="siga-log__placeholder">Tu rol no tiene acceso a este resumen.</p>';
            return;
        }

        try {
            const evaluaciones = await apiJson('/api/evaluaciones');
            const total = Array.isArray(evaluaciones) ? evaluaciones.length : 0;
            const counts = {
                verde: 0,
                amarillo: 0,
                rojo: 0,
            };

            evaluaciones.forEach(function (evaluacion) {
                if (counts[evaluacion.nivel] != null) {
                    counts[evaluacion.nivel] += 1;
                }
            });

            node.innerHTML = ['verde', 'amarillo', 'rojo'].map(function (nivel) {
                const value = counts[nivel];
                const percent = total ? Math.round((value / total) * 100) : 0;
                return '<div class="siga-report-card"><strong>' + nivel.charAt(0).toUpperCase() + nivel.slice(1) + '</strong><p>' + value + ' evaluaciones · ' + percent + '%</p><div class="siga-progress"><div class="siga-progress__fill siga-progress__fill--' + nivel + '" style="width:' + percent + '%"></div></div></div>';
            }).join('');
        } catch (error) {
            node.innerHTML = '<p class="siga-log__placeholder">' + escapeHtml(normaliseError(error)) + '</p>';
        }
    }

    async function renderUpcomingClasses() {
        const node = document.querySelector('#home-agenda');

        if (!node) {
            return;
        }

        try {
            const clases = await apiJson('/api/clases');
            const ordered = Array.isArray(clases)
                ? clases.slice().sort(function (left, right) {
                    return parseClassDate(left) - parseClassDate(right);
                }).slice(0, 5)
                : [];

            if (!ordered.length) {
                node.innerHTML = '<p class="siga-log__placeholder">No hay clases programadas por ahora.</p>';
                return;
            }

            node.innerHTML = ordered.map(function (clase) {
                const alumno = clase.alumno ? clase.alumno.nombre + ' ' + clase.alumno.apellido : 'Alumno #' + clase.alumno_id;
                const instructor = clase.instructor ? clase.instructor.name : 'Instructor #' + clase.instructor_id;

                return '<div class="siga-report-row"><div class="siga-report-row__meta"><strong>' + escapeHtml(clase.fecha + ' · ' + clase.hora) + '</strong><p>' + escapeHtml(alumno) + '</p></div><div class="siga-report-row__aside"><strong>' + escapeHtml(instructor) + '</strong><p>Clase #' + clase.id + '</p></div></div>';
            }).join('');
        } catch (error) {
            node.innerHTML = '<p class="siga-log__placeholder">' + escapeHtml(normaliseError(error)) + '</p>';
        }
    }

    async function renderPortfolioOverview() {
        const node = document.querySelector('#home-cartera');

        if (!node) {
            return;
        }

        if (!canReadStat('pagos')) {
            node.innerHTML = '<p class="siga-log__placeholder">Tu rol no tiene acceso a este resumen.</p>';
            return;
        }

        try {
            const pagos = await apiJson('/api/pagos');

            if (state.currentUser.role === 'alumno' && state.currentUser.alumno && state.currentUser.alumno.id) {
                const estadoCuenta = await apiJson('/api/alumnos/' + state.currentUser.alumno.id + '/estado-cuenta');
                const resumen = estadoCuenta.resumen;

                node.innerHTML = [
                    reportMetricCard('Costo total', '$' + formatMoney(resumen.costo_total), 'Programa registrado'),
                    reportMetricCard('Total pagado', '$' + formatMoney(resumen.total_pagado), 'Pagos aplicados'),
                    reportMetricCard('Saldo pendiente', '$' + formatMoney(resumen.saldo_pendiente), 'Por liquidar'),
                    reportMetricCard('Estado', resumen.estado_cuenta, 'Situacion actual'),
                ].join('');
                return;
            }

            const alumnos = await apiJson('/api/alumnos');
            const pagosPagados = Array.isArray(pagos) ? pagos.filter(function (pago) { return pago.estado === 'pagado'; }) : [];
            const pagosPendientes = Array.isArray(pagos) ? pagos.filter(function (pago) { return pago.estado === 'pendiente'; }) : [];
            const totalIngresado = pagosPagados.reduce(function (sum, pago) { return sum + Number(pago.monto || 0); }, 0);
            const totalPendiente = pagosPendientes.reduce(function (sum, pago) { return sum + Number(pago.monto || 0); }, 0);

            const topPendientes = Array.isArray(alumnos)
                ? alumnos
                    .map(function (alumno) {
                        const alumnoPagos = Array.isArray(pagos) ? pagos.filter(function (pago) {
                            return Number(pago.alumno_id) === Number(alumno.id) && pago.estado === 'pagado';
                        }) : [];
                        const pagado = alumnoPagos.reduce(function (sum, pago) { return sum + Number(pago.monto || 0); }, 0);
                        const saldo = Math.max(Number(alumno.costo_total || 0) - pagado, 0);

                        return {
                            nombre: alumno.nombre + ' ' + alumno.apellido,
                            saldo: saldo,
                        };
                    })
                    .sort(function (left, right) { return right.saldo - left.saldo; })
                    .slice(0, 3)
                : [];

            const cards = [
                reportMetricCard('Cobrado', '$' + formatMoney(totalIngresado), 'Pagos marcados como pagados'),
                reportMetricCard('Pendiente', '$' + formatMoney(totalPendiente), 'Movimientos aun pendientes'),
                reportMetricCard('Movimientos', String(Array.isArray(pagos) ? pagos.length : 0), 'Registros de pago'),
            ];

            topPendientes.forEach(function (item) {
                cards.push(reportMetricCard(item.nombre, '$' + formatMoney(item.saldo), 'Saldo estimado pendiente'));
            });

            node.innerHTML = cards.join('');
        } catch (error) {
            node.innerHTML = '<p class="siga-log__placeholder">' + escapeHtml(normaliseError(error)) + '</p>';
        }
    }

    async function loadResource(name) {
        const config = resourceConfig[name];
        const listNode = document.querySelector('#resource-list');
        const tableNode = document.querySelector('#resource-table');

        if (!config) {
            return;
        }

        try {
            const data = await apiJson(config.endpoint);
            const items = Array.isArray(data) ? data : [];

            if (tableNode && config.type === 'table') {
                tableNode.innerHTML = renderTableRows(name, items);
            }

            if (listNode && config.type === 'list') {
                listNode.innerHTML = renderListItems(name, items);
            }
        } catch (error) {
            if (tableNode) {
                tableNode.innerHTML = '<tr><td colspan="' + (config.emptyCols || 4) + '">' + escapeHtml(normaliseError(error)) + '</td></tr>';
            }

            if (listNode) {
                listNode.innerHTML = '<p class="siga-log__placeholder">' + escapeHtml(normaliseError(error)) + '</p>';
            }
        }
    }

    async function handleEdit(name, id) {
        const config = resourceConfig[name];

        if (!config || !canEditResource(name)) {
            return;
        }

        try {
            await ensureCatalogsForFields(config.fields);
            const item = await apiJson(config.endpoint + '/' + id);
            openEditModal(name, id, config, item);
        } catch (error) {
            addLog('Error al editar', normaliseError(error), true);
        }
    }

    async function handleDelete(name, id) {
        const config = resourceConfig[name];

        if (!config || !canDeleteResource(name)) {
            return;
        }

        if (!window.confirm('Seguro que quieres eliminar este registro?')) {
            return;
        }

        try {
            await api(config.endpoint + '/' + id, {
                method: 'DELETE',
            });

            addLog('Registro eliminado', singularLabel(name) + ' #' + id + ' se elimino correctamente.');
            await hydratePage(false);
        } catch (error) {
            addLog('Error al eliminar', normaliseError(error), true);
        }
    }

    function renderTableRows(name, items) {
        const config = resourceConfig[name];

        if (!items.length) {
            return '<tr><td colspan="' + (config.emptyCols || 4) + '">Sin datos disponibles.</td></tr>';
        }

        return items.map(function (item) {
            if (name === 'alumnos') {
                return '<tr><td>' + item.id + '</td><td>' + escapeHtml(item.nombre + ' ' + item.apellido) + '</td><td>' + escapeHtml(item.curp) + '</td><td>' + escapeHtml(item.correo) + '</td><td>$' + formatMoney(item.costo_total) + '</td><td>' + actionButtons(name, item.id) + '</td></tr>';
            }

            if (name === 'clases') {
                return '<tr><td>' + item.id + '</td><td>' + escapeHtml(item.fecha + ' ' + item.hora) + '</td><td>' + escapeHtml(item.alumno ? item.alumno.nombre + ' ' + item.alumno.apellido : String(item.alumno_id)) + '</td><td>' + escapeHtml(item.instructor ? item.instructor.name : String(item.instructor_id)) + '</td><td>' + actionButtons(name, item.id) + '</td></tr>';
            }

            if (name === 'users') {
                return '<tr><td>' + item.id + '</td><td>' + escapeHtml(item.name) + '</td><td>' + escapeHtml(item.email) + '</td><td>' + pill(item.role) + '</td><td>' + actionButtons(name, item.id) + '</td></tr>';
            }

            return '';
        }).join('');
    }

    function renderListItems(name, items) {
        if (!items.length) {
            return '<p class="siga-log__placeholder">Sin datos disponibles.</p>';
        }

        return items.slice(0, 15).map(function (item) {
            if (name === 'evaluaciones') {
                return cardTemplate(
                    'Clase ' + item.clase_id,
                    'Promedio ' + item.promedio + ' · Nivel ' + pill(item.nivel),
                    actionButtons(name, item.id)
                );
            }

            if (name === 'observaciones') {
                return cardTemplate(
                    'Clase ' + item.clase_id,
                    escapeHtml(item.comentario),
                    actionButtons(name, item.id)
                );
            }

            if (name === 'pagos') {
                return cardTemplate(
                    'Alumno ' + escapeHtml(item.alumno ? item.alumno.nombre + ' ' + item.alumno.apellido : String(item.alumno_id)),
                    '$' + formatMoney(item.monto) + ' · ' + pill(item.estado) + ' · ' + escapeHtml(item.fecha_pago),
                    actionButtons(name, item.id)
                );
            }

            return '';
        }).join('');
    }

    function renderEstadoCuenta(data) {
        const alumno = data.alumno;
        const resumen = data.resumen;
        const pagos = data.pagos;
        const pagosHtml = pagos.length
            ? pagos.map(function (pago) {
                return '<li>' + escapeHtml(pago.fecha_pago) + ' · $' + formatMoney(pago.monto) + ' · ' + escapeHtml(pago.estado) + '</li>';
            }).join('')
            : '<li>Sin movimientos registrados.</li>';

        estadoCuentaSummary.innerHTML = '<div class="siga-account__card"><strong>' +
            escapeHtml(alumno.nombre + ' ' + alumno.apellido) +
            '</strong><p>Costo total: $' + formatMoney(resumen.costo_total) +
            '</p><p>Total pagado: $' + formatMoney(resumen.total_pagado) +
            '</p><p>Saldo pendiente: $' + formatMoney(resumen.saldo_pendiente) +
            '</p><p>Estado: ' + pill(resumen.estado_cuenta) +
            '</p><ul class="siga-mini-list">' + pagosHtml + '</ul></div>';
    }

    async function populatePageCatalogs() {
        const catalogFields = Array.prototype.slice.call(document.querySelectorAll('[data-catalog]'));

        if (!catalogFields.length) {
            return;
        }

        const pendingCatalogs = catalogFields.map(function (field) {
            return field.getAttribute('data-catalog');
        }).filter(Boolean);

        await Promise.all(unique(pendingCatalogs).map(loadCatalog));

        catalogFields.forEach(function (field) {
            const catalogName = field.getAttribute('data-catalog');
            const selectedValue = field.value;
            const placeholder = field.dataset.placeholder || field.querySelector('option')?.textContent || 'Selecciona una opcion';
            populateSelect(field, state.catalogs[catalogName] || [], placeholder, selectedValue);
        });
    }

    async function ensureCatalogsForFields(fields) {
        const catalogs = fields
            .filter(function (field) { return field.catalog; })
            .map(function (field) { return field.catalog; });

        await Promise.all(unique(catalogs).map(loadCatalog));
    }

    async function loadCatalog(name) {
        if (!catalogConfig[name]) {
            return [];
        }

        if (state.catalogs[name]) {
            return state.catalogs[name];
        }

        const data = await apiJson(catalogConfig[name].endpoint);
        const items = Array.isArray(data) ? data.map(catalogConfig[name].map) : [];
        state.catalogs[name] = items;

        return items;
    }

    function populateSelect(select, items, placeholder, selectedValue) {
        const options = ['<option value="">' + escapeHtml(placeholder) + '</option>'].concat(items.map(function (item) {
            const selected = String(item.value) === String(selectedValue) ? ' selected' : '';
            return '<option value="' + escapeAttribute(String(item.value)) + '"' + selected + '>' + escapeHtml(item.label) + '</option>';
        }));

        select.innerHTML = options.join('');
    }

    function renderProtectedPlaceholders(message) {
        const safeMessage = escapeHtml(message || 'No fue posible cargar este modulo.');
        const tableNode = document.querySelector('#resource-table');
        const listNode = document.querySelector('#resource-list');

        if (tableNode) {
            const firstRow = tableNode.closest('table').querySelectorAll('thead th').length || 4;
            tableNode.innerHTML = '<tr><td colspan="' + firstRow + '">' + safeMessage + '</td></tr>';
        }

        if (listNode) {
            listNode.innerHTML = '<p class="siga-log__placeholder">' + safeMessage + '</p>';
        }
    }

    function actionButtons(name, id) {
        const actions = [];

        if (canEditResource(name)) {
            actions.push('<button class="siga-button siga-button--tiny siga-button--ghost" type="button" data-action="edit" data-resource="' + name + '" data-id="' + id + '">Editar</button>');
        }

        if (canDeleteResource(name)) {
            actions.push('<button class="siga-button siga-button--tiny siga-button--danger" type="button" data-action="delete" data-resource="' + name + '" data-id="' + id + '">Eliminar</button>');
        }

        return actions.length
            ? '<div class="siga-row-actions">' + actions.join('') + '</div>'
            : '<span class="siga-table__hint">Solo lectura</span>';
    }

    function addLog(title, message, error) {
        if (!activityLog) {
            return;
        }

        const item = document.createElement('div');
        item.className = 'siga-log__item';
        item.innerHTML = '<strong>' + escapeHtml(title) + '</strong><p>' + escapeHtml(message) + '</p><span class="siga-status-pill ' +
            (error ? 'siga-status-pill--rojo' : 'siga-status-pill--verde') + '">' +
            (error ? 'Atencion' : 'OK') + '</span>';
        activityLog.prepend(item);

        if (activityLog.children.length > 10) {
            activityLog.removeChild(activityLog.lastElementChild);
        }
    }

    function openEditModal(name, id, config, item) {
        if (!editModal || !editModalFields || !editModalTitle) {
            return;
        }

        state.editing = {
            name: name,
            id: id,
            endpoint: config.endpoint,
            optionalKeys: config.fields.filter(function (field) {
                return field.optional;
            }).map(function (field) {
                return field.key;
            }),
        };

        editModalTitle.textContent = 'Editar ' + singularLabel(name) + ' #' + id;
        editModalFields.innerHTML = '<div class="siga-form-feedback siga-field--wide" data-form-feedback hidden></div>' + config.fields.map(function (field) {
            const value = item[field.key] == null ? '' : item[field.key];
            const extra = field.optional ? ' placeholder="Opcional"' : ' required';
            const control = buildControl(field, value, extra);

            return '<label class="siga-field"><span>' + field.label + '</span>' + control + '</label>';
        }).join('');

        editModal.hidden = false;
        document.body.style.overflow = 'hidden';
    }

    function closeEditModal() {
        if (!editModal) {
            return;
        }

        editModal.hidden = true;
        document.body.style.overflow = '';
        state.editing = null;

        if (editModalForm) {
            editModalForm.reset();
            clearFormFeedback(editModalForm);
        }
    }

    function buildControl(field, value, extra) {
        if (field.catalog) {
            const options = state.catalogs[field.catalog] || [];
            const select = document.createElement('select');
            select.name = field.key;
            if (!field.optional) {
                select.required = true;
            }
            select.innerHTML = ['<option value="">Selecciona una opcion</option>'].concat(options.map(function (item) {
                return '<option value="' + escapeAttribute(String(item.value)) + '"' + (String(item.value) === String(value) ? ' selected' : '') + '>' + escapeHtml(item.label) + '</option>';
            })).join('');
            return select.outerHTML;
        }

        if (field.options) {
            return '<select name="' + field.key + '"' + extra + '>' + field.options.map(function (option) {
                return '<option value="' + option + '"' + (String(option) === String(value) ? ' selected' : '') + '>' + option + '</option>';
            }).join('') + '</select>';
        }

        if (field.key === 'comentario') {
            return '<textarea name="' + field.key + '" rows="5"' + extra + '>' + escapeHtml(String(value)) + '</textarea>';
        }

        const type = guessInputType(field.key);
        return '<input name="' + field.key + '" type="' + type + '" value="' + escapeAttribute(String(value)) + '"' + extra + buildInputAttributes(field.key) + ' />';
    }

    function buildInputAttributes(key) {
        if (key === 'monto' || key === 'costo_total') {
            return ' min="0" step="0.01"';
        }

        if (['senales', 'frenado', 'seguridad'].indexOf(key) !== -1) {
            return ' min="1" max="5"';
        }

        return '';
    }

    function guessInputType(key) {
        if (key.indexOf('fecha') !== -1) {
            return 'date';
        }

        if (key === 'hora') {
            return 'time';
        }

        if (key.indexOf('correo') !== -1 || key === 'email') {
            return 'email';
        }

        if (key.indexOf('password') !== -1) {
            return 'password';
        }

        if (key.indexOf('monto') !== -1 || key.indexOf('costo') !== -1) {
            return 'number';
        }

        if (key.indexOf('_id') !== -1 || ['senales', 'frenado', 'seguridad'].indexOf(key) !== -1) {
            return 'number';
        }

        return 'text';
    }

    function renderListItems(name, items) {
        if (!items.length) {
            return '<p class="siga-log__placeholder">Sin datos disponibles.</p>';
        }

        return items.slice(0, 15).map(function (item) {
            if (name === 'evaluaciones') {
                return cardTemplate(
                    formatClaseLabel(item),
                    '<div class="siga-eval-card__summary"><strong>Promedio ' + escapeHtml(String(item.promedio)) + '</strong>' + pill(item.nivel) + '</div>' +
                    renderTrafficLight(item.nivel) +
                    renderEvaluacionMetrics(item),
                    actionButtons(name, item.id)
                );
            }

            if (name === 'observaciones') {
                return cardTemplate(
                    'Clase ' + item.clase_id,
                    escapeHtml(item.comentario),
                    actionButtons(name, item.id)
                );
            }

            if (name === 'pagos') {
                return cardTemplate(
                    'Alumno ' + escapeHtml(item.alumno ? item.alumno.nombre + ' ' + item.alumno.apellido : String(item.alumno_id)),
                    '$' + formatMoney(item.monto) + ' Â· ' + pill(item.estado) + ' Â· ' + escapeHtml(item.fecha_pago),
                    actionButtons(name, item.id)
                );
            }

            return '';
        }).join('');
    }

    function buildControl(field, value, extra) {
        if (field.catalog) {
            const options = state.catalogs[field.catalog] || [];
            const select = document.createElement('select');
            select.name = field.key;
            if (!field.optional) {
                select.required = true;
            }
            select.innerHTML = ['<option value="">Selecciona una opcion</option>'].concat(options.map(function (item) {
                return '<option value="' + escapeAttribute(String(item.value)) + '"' + (String(item.value) === String(value) ? ' selected' : '') + '>' + escapeHtml(item.label) + '</option>';
            })).join('');
            return select.outerHTML;
        }

        if (field.options) {
            return '<select name="' + field.key + '"' + extra + '>' + field.options.map(function (option) {
                return '<option value="' + option + '"' + (String(option) === String(value) ? ' selected' : '') + '>' + option + '</option>';
            }).join('') + '</select>';
        }

        if (field.key === 'comentario') {
            return '<textarea name="' + field.key + '" rows="5"' + extra + '>' + escapeHtml(String(value)) + '</textarea>';
        }

        if (isEvaluacionMetricField(field.key)) {
            return buildMetricScaleControl(field.key, value);
        }

        const type = guessInputType(field.key);
        return '<input name="' + field.key + '" type="' + type + '" value="' + escapeAttribute(String(value)) + '"' + extra + buildInputAttributes(field.key) + ' />';
    }

    function isEvaluacionMetricField(key) {
        return ['senales', 'frenado', 'seguridad'].indexOf(key) !== -1;
    }

    function buildMetricScaleControl(name, value) {
        const selectedValue = String(value || '');
        const levels = [
            { value: '1', tone: 'rojo' },
            { value: '2', tone: 'rojo' },
            { value: '3', tone: 'amarillo' },
            { value: '4', tone: 'verde' },
            { value: '5', tone: 'verde' },
        ];

        return '<div class="siga-score-scale" role="radiogroup" aria-label="' + escapeAttribute(name) + '">' + levels.map(function (level) {
            const checked = level.value === selectedValue ? ' checked' : '';
            return '<label class="siga-score-scale__option siga-score-scale__option--' + level.tone + '">' +
                '<input type="radio" name="' + name + '" value="' + level.value + '"' + checked + ' required>' +
                '<span>' + level.value + '</span>' +
                '</label>';
        }).join('') + '</div><small class="siga-score-scale__legend">1-2 rojo, 3 amarillo, 4-5 verde.</small>';
    }

    function formatClaseLabel(item) {
        if (item.clase) {
            const alumno = item.clase.alumno
                ? item.clase.alumno.nombre + ' ' + item.clase.alumno.apellido
                : 'Alumno #' + item.clase.alumno_id;

            if (item.clase.fecha && item.clase.hora) {
                return escapeHtml(item.clase.fecha + ' ' + item.clase.hora + ' Â· ' + alumno);
            }
        }

        return 'Clase ' + escapeHtml(String(item.clase_id));
    }

    function renderEvaluacionMetrics(item) {
        const metricas = [
            { key: 'senales', label: 'Senales' },
            { key: 'frenado', label: 'Frenado' },
            { key: 'seguridad', label: 'Seguridad' },
        ];

        return '<div class="siga-eval-metrics">' + metricas.map(function (metrica) {
            const value = Number(item[metrica.key]);
            return '<div class="siga-eval-metrics__item">' +
                '<span>' + metrica.label + '</span>' +
                '<strong class="siga-eval-metrics__value siga-eval-metrics__value--' + metricTone(value) + '">' + escapeHtml(String(item[metrica.key])) + '/5</strong>' +
                '</div>';
        }).join('') + '</div>';
    }

    function renderTrafficLight(level) {
        const levels = ['rojo', 'amarillo', 'verde'];
        return '<div class="siga-traffic-light" aria-label="Nivel ' + escapeAttribute(level) + '">' + levels.map(function (item) {
            const active = item === level ? ' siga-traffic-light__lamp--active' : '';
            return '<span class="siga-traffic-light__lamp siga-traffic-light__lamp--' + item + active + '"></span>';
        }).join('') + '</div>';
    }

    function metricTone(value) {
        if (value <= 2) {
            return 'rojo';
        }

        if (value >= 4) {
            return 'verde';
        }

        return 'amarillo';
    }

    function singularLabel(name) {
        const labels = {
            alumnos: 'alumno',
            clases: 'clase',
            evaluaciones: 'evaluacion',
            observaciones: 'observacion',
            pagos: 'pago',
            users: 'usuario',
        };

        return labels[name] || 'registro';
    }

    function syncCurrentUser() {
        currentUserName.textContent = state.currentUser.name;
        currentUserMeta.textContent = state.currentUser.email + ' · ID ' + state.currentUser.id;
        currentUserRole.textContent = state.currentUser.role;
    }

    function syncRoleSpecificUi() {
        if (pageType !== 'pagos' || !estadoCuentaForm) {
            return;
        }

        const alumnoIdInput = estadoCuentaForm.querySelector('input[name="alumno_id"]');

        if (!alumnoIdInput) {
            return;
        }

        if (state.currentUser.role === 'alumno' && state.currentUser.alumno && state.currentUser.alumno.id) {
            alumnoIdInput.value = state.currentUser.alumno.id;
            alumnoIdInput.readOnly = true;
        }
    }

    async function maybeLoadOwnEstadoCuenta() {
        if (pageType !== 'pagos' || state.currentUser.role !== 'alumno' || !state.currentUser.alumno || !state.currentUser.alumno.id) {
            return;
        }

        if (state.lastEstadoCuentaAlumnoId) {
            return;
        }

        state.lastEstadoCuentaAlumnoId = String(state.currentUser.alumno.id);

        try {
            const data = await apiJson('/api/alumnos/' + state.currentUser.alumno.id + '/estado-cuenta');
            renderEstadoCuenta(data);
        } catch (error) {
            estadoCuentaSummary.innerHTML = '<p class="siga-log__placeholder">' + escapeHtml(normaliseError(error)) + '</p>';
        }
    }

    function canReadStat(key) {
        const role = state.currentUser.role;

        if (key === 'alumnos') {
            return role === 'administrador' || role === 'recepcionista';
        }

        if (key === 'pagos' || key === 'pagos-pendientes') {
            return role === 'administrador' || role === 'recepcionista' || role === 'alumno';
        }

        return role === 'administrador' || role === 'recepcionista' || role === 'instructor' || role === 'alumno';
    }

    function canEditResource(name) {
        const role = state.currentUser.role;

        if (name === 'alumnos' || name === 'clases' || name === 'pagos') {
            return role === 'administrador' || role === 'recepcionista';
        }

        if (name === 'evaluaciones' || name === 'observaciones') {
            return role === 'instructor';
        }

        if (name === 'users') {
            return role === 'administrador';
        }

        return false;
    }

    function canDeleteResource(name) {
        return canEditResource(name);
    }

    function formatMoney(value) {
        const number = Number(value || 0);
        return number.toFixed(2);
    }

    function parseClassDate(clase) {
        return new Date(clase.fecha + 'T' + normaliseClassTime(clase.hora));
    }

    function normaliseClassTime(time) {
        return String(time).length === 5 ? time + ':00' : time;
    }

    function reportMetricCard(title, value, hint) {
        return '<div class="siga-report-card"><strong>' + escapeHtml(title) + '</strong><p>' + escapeHtml(value) + '</p><p>' + escapeHtml(hint) + '</p></div>';
    }

    function sanitisePayload(payload) {
        Object.keys(payload).forEach(function (key) {
            if (typeof payload[key] === 'string') {
                payload[key] = payload[key].trim();
            }
        });

        return payload;
    }

    function clearFormFeedback(form) {
        const feedback = form.querySelector('[data-form-feedback]');

        if (!feedback) {
            return;
        }

        feedback.hidden = true;
        feedback.textContent = '';
        feedback.classList.remove('is-error', 'is-success');
    }

    function showFormFeedback(form, message, isError) {
        const feedback = form.querySelector('[data-form-feedback]');

        if (!feedback) {
            return;
        }

        feedback.hidden = false;
        feedback.textContent = message;
        feedback.classList.remove('is-error', 'is-success');
        feedback.classList.add(isError ? 'is-error' : 'is-success');
    }

    function buildValidationMessage(error) {
        if (error && error.payload && error.payload.errors) {
            return Object.values(error.payload.errors)
                .flat()
                .join(' ');
        }

        return normaliseError(error);
    }

    function unique(values) {
        return Array.from(new Set(values));
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function escapeAttribute(value) {
        return escapeHtml(value).replace(/"/g, '&quot;');
    }

    function cardTemplate(title, body, actions) {
        return '<article class="siga-stack__item"><strong>' + title + '</strong><div class="siga-stack__body">' + body + '</div>' + (actions || '') + '</article>';
    }

    function pill(status) {
        const safeStatus = String(status).toLowerCase();
        return '<span class="siga-status-pill siga-status-pill--' + safeStatus + '">' + escapeHtml(status) + '</span>';
    }

    async function apiJson(url, options) {
        const response = await api(url, options);
        return response.json();
    }

    async function api(url, options) {
        const finalOptions = options || {};
        const headers = Object.assign({
            'Accept': 'application/json',
        }, finalOptions.headers || {});

        if (finalOptions.body) {
            headers['Content-Type'] = 'application/json';
        }

        if (state.userId) {
            headers['X-User-Id'] = state.userId;
        }

        const response = await fetch(url, {
            method: finalOptions.method || 'GET',
            body: finalOptions.body,
            headers: headers,
        });

        if (!response.ok) {
            let payload = null;

            try {
                payload = await response.json();
            } catch (error) {
                payload = null;
            }

            const message = (payload && (payload.message || payload.error)) || ('La API respondio con status ' + response.status + '.');
            const apiError = new Error(message);
            apiError.payload = payload;
            apiError.status = response.status;
            throw apiError;
        }

        return response;
    }

    function normaliseError(error) {
        return error instanceof Error ? error.message : 'Ocurrio un error inesperado.';
    }
})();
