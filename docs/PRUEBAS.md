# Matriz de Pruebas - SIGA

Esta matriz documenta pruebas manuales y automatizadas para validar los modulos principales del sistema SIGA. El formato puede copiarse a Excel o mantenerse como evidencia en Markdown.

## Datos base recomendados

Antes de iniciar las pruebas:

- Ejecutar migraciones y seeders.
- Iniciar sesion con el administrador inicial.
- Crear usuarios de prueba para los roles `recepcionista`, `instructor` y `alumno`.
- Registrar al menos dos alumnos para validar restricciones y filtros.

Usuario administrador inicial:

- Correo: `admin@siga.test`
- Contrasena: `Admin12345`

## Primer Set de Pruebas: Acceso y Seguridad

| No. | Modulo | Detalle de la prueba | Datos de prueba | Resultado esperado | Verificar en la aplicacion web | Prueba automatizada relacionada |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | Login | Iniciar sesion con credenciales validas. | `admin@siga.test` / `Admin12345` | Acceso correcto al dashboard. | El sistema muestra `/inicio` y el menu del administrador. | Flujo cubierto parcialmente por autenticacion web. |
| 2 | Login | Intentar entrar con contrasena incorrecta. | `admin@siga.test` / `ClaveIncorrecta` | El acceso es rechazado. | Se muestra mensaje de error y no entra al panel. | Recomendado agregar prueba. |
| 3 | Seguridad | Cambiar contrasena desde el panel. | Actual: `Admin12345`, nueva: `NuevaClave123` | La contrasena se actualiza correctamente. | Cerrar sesion e iniciar con la nueva contrasena. | `SecurityFlowTest::test_an_authenticated_user_can_change_their_password` |
| 4 | Seguridad | Restablecer contrasena con token valido. | Correo de usuario existente. | La nueva contrasena queda guardada. | Iniciar sesion con la nueva contrasena. | `SecurityFlowTest::test_a_user_can_reset_password_with_a_valid_token` |
| 5 | Roles | Acceder a un modulo no permitido para el rol. | Usuario `recepcionista` intentando crear evaluaciones. | El sistema bloquea la accion. | La interfaz no debe permitir la accion o la API responde prohibido. | `SigaApiTest::test_it_blocks_a_recepcionista_from_creating_evaluaciones` |

## Segundo Set de Pruebas: Alumnos

| No. | Modulo | Detalle de la prueba | Datos de prueba | Resultado esperado | Verificar en la aplicacion web | Prueba automatizada relacionada |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | Alumnos | Registrar un alumno con campos obligatorios. | Nombre, apellido, CURP, telefono, correo, fecha de ingreso y costo total. | El alumno se guarda correctamente. | El alumno aparece en la tabla/listado de alumnos. | `SigaApiTest::test_it_creates_an_alumno_with_required_fields` |
| 2 | Alumnos | Editar datos generales de un alumno. | Cambiar nombre o costo total. | La informacion se actualiza. | Abrir el registro y verificar los nuevos datos. | `SigaApiTest::test_it_updates_and_deletes_an_alumno` |
| 3 | Alumnos | Eliminar un alumno. | Alumno existente. | El registro se elimina. | El alumno ya no aparece en el listado. | `SigaApiTest::test_it_updates_and_deletes_an_alumno` |
| 4 | Perfil alumno | Alumno modifica solo campos permitidos. | Cambiar telefono y correo; intentar cambiar nombre o costo total. | Solo se actualizan telefono y correo. | Verificar que nombre y costo total no cambian. | `SigaApiTest::test_an_alumno_can_only_update_allowed_profile_fields` |

## Tercer Set de Pruebas: Clases

| No. | Modulo | Detalle de la prueba | Datos de prueba | Resultado esperado | Verificar en la aplicacion web | Prueba automatizada relacionada |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | Clases | Programar una clase con alumno e instructor disponibles. | Fecha, hora, alumno e instructor. | La clase se agenda correctamente. | La clase aparece en el listado y dashboard. | Cubierto dentro de pruebas de evaluaciones y observaciones. |
| 2 | Clases | Programar dos clases para el mismo instructor a la misma hora. | Mismo instructor, misma fecha y hora, alumnos distintos. | El sistema rechaza la segunda clase. | Se muestra error de validacion. | `SigaApiTest::test_it_prevents_overlapping_classes_for_the_same_instructor_or_student` |
| 3 | Clases | Programar dos clases para el mismo alumno a la misma hora. | Mismo alumno, misma fecha y hora, instructores distintos. | El sistema rechaza la segunda clase. | Se muestra error de validacion. | `SigaApiTest::test_it_prevents_overlapping_classes_for_the_same_instructor_or_student` |

## Cuarto Set de Pruebas: Evaluaciones y Observaciones

| No. | Modulo | Detalle de la prueba | Datos de prueba | Resultado esperado | Verificar en la aplicacion web | Prueba automatizada relacionada |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | Evaluaciones | Crear evaluacion con calificaciones altas. | Senales 5, frenado 5, seguridad 5. | Se guarda promedio alto y nivel favorable. | La evaluacion aparece asociada a la clase. | Cubierto parcialmente por pruebas de evaluacion. |
| 2 | Evaluaciones | Crear evaluacion con una metrica critica menor o igual a 2. | Senales 5, frenado 2, seguridad 5. | El nivel queda en `rojo`. | Revisar semaforo de la evaluacion. | `SigaApiTest::test_it_assigns_red_level_when_a_critical_metric_is_two_or_less` |
| 3 | Evaluaciones | Editar una evaluacion para cambiar el nivel. | Cambiar frenado de 4 a 1. | La evaluacion se actualiza y el nivel pasa a `rojo`. | Revisar detalle de evaluacion. | `SigaApiTest::test_an_instructor_can_update_and_delete_an_evaluacion` |
| 4 | Evaluaciones | Eliminar evaluacion existente. | Evaluacion creada por instructor. | La evaluacion se elimina. | Ya no aparece en el listado. | `SigaApiTest::test_an_instructor_can_update_and_delete_an_evaluacion` |
| 5 | Observaciones | Registrar observacion para una clase existente. | Comentario de seguimiento. | La observacion se guarda. | La observacion aparece en la clase correspondiente. | `SigaApiTest::test_it_creates_an_observacion_for_an_existing_clase` |
| 6 | Observaciones | Filtrar observaciones por clase. | Dos clases con observaciones distintas. | Solo se muestran observaciones de la clase seleccionada. | Cambiar filtro y validar resultados. | `SigaApiTest::test_it_filters_observaciones_by_clase` |

## Quinto Set de Pruebas: Pagos y Estado de Cuenta

| No. | Modulo | Detalle de la prueba | Datos de prueba | Resultado esperado | Verificar en la aplicacion web | Prueba automatizada relacionada |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | Pagos | Registrar pago pagado. | Alumno con costo total 5000, pago de 1500. | El pago se guarda como `pagado`. | El pago aparece en historial. | `SigaApiTest::test_it_creates_a_pago_and_generates_estado_de_cuenta` |
| 2 | Pagos | Registrar pago pendiente. | Pago de 500 con estado `pendiente`. | El pago se lista sin sumar al total pagado. | Revisar historial y estado. | `SigaApiTest::test_it_creates_a_pago_and_generates_estado_de_cuenta` |
| 3 | Estado de cuenta | Consultar resumen financiero del alumno. | Costo total 5000, pagado 1500. | Saldo pendiente 3500 y estado `pendiente`. | Abrir estado de cuenta del alumno. | `SigaApiTest::test_it_creates_a_pago_and_generates_estado_de_cuenta` |
| 4 | Estado de cuenta | Descargar PDF del estado de cuenta. | Alumno con pagos registrados. | Se genera archivo PDF valido. | Descargar PDF y abrirlo. | `SigaApiTest::test_it_generates_estado_de_cuenta_pdf` |
| 5 | Pagos alumno | Alumno consulta sus propios pagos. | Usuario alumno relacionado con un registro de alumno. | Solo ve sus propios pagos. | Entrar como alumno y revisar pagos. | `SigaApiTest::test_an_alumno_can_only_list_their_own_pagos` |

## Sexto Set de Pruebas: Dashboard

| No. | Modulo | Detalle de la prueba | Datos de prueba | Resultado esperado | Verificar en la aplicacion web | Prueba automatizada relacionada |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | Dashboard | Revisar indicadores generales. | Alumnos, clases, evaluaciones y pagos creados. | Los contadores muestran informacion del sistema. | Ver tarjetas del dashboard en `/inicio`. | Recomendado agregar prueba. |
| 2 | Dashboard | Revisar evaluaciones en rojo. | Crear una evaluacion con nivel `rojo`. | El dashboard refleja evaluaciones en rojo. | Ver indicador y distribucion de semaforo. | Recomendado agregar prueba. |
| 3 | Dashboard | Revisar pagos pendientes. | Crear un pago con estado `pendiente`. | El dashboard muestra pagos pendientes. | Ver resumen de cartera. | Recomendado agregar prueba. |

## Como ejecutar las pruebas automatizadas

Desde la raiz del proyecto:

```bash
php vendor/bin/phpunit --do-not-cache-result
```

Tambien puedes ejecutar un archivo especifico:

```bash
php vendor/bin/phpunit tests/Feature/SigaApiTest.php --do-not-cache-result
```

```bash
php vendor/bin/phpunit tests/Feature/SecurityFlowTest.php --do-not-cache-result
```

## Formato recomendado para evidencia

Para documentar ejecucion manual, agrega estas columnas si lo pasas a Excel:

| No. | Fecha | Responsable | Modulo | Caso de prueba | Resultado esperado | Resultado obtenido | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | 2026-04-23 | QA / Alumno | Login | Iniciar sesion como administrador | Entra al dashboard | Entra correctamente | Aprobado | Captura de pantalla |

Estados sugeridos:

- `Aprobado`
- `Fallido`
- `Bloqueado`
- `Pendiente`
