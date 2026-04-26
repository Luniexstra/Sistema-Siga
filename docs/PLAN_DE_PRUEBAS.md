# Plan de Pruebas - SIGA

## 1. Informacion general

| Campo | Detalle |
| --- | --- |
| Proyecto | SIGA |
| Sistema a evaluar | Sistema web para gestion academica y administrativa de una escuela de manejo |
| Version evaluada | Correccion del primer departamental |
| Ambiente principal | Render |
| Tipo de aplicacion | Aplicacion web Laravel con interfaz Blade y API interna |
| Responsable | Equipo del proyecto SIGA |
| Fecha de ejecucion | Abril 2026 |

## 2. Objetivo del plan

Validar que el sistema SIGA funcione correctamente despues de la correccion del primer departamental, comprobando los modulos principales, la seguridad por roles, el registro de informacion, la generacion de reportes y el comportamiento del sistema en el ambiente cloud seleccionado.

El plan busca confirmar que:

- Los usuarios pueden iniciar sesion y acceder solo a las secciones permitidas.
- Los modulos de alumnos, clases, evaluaciones, observaciones y pagos funcionan correctamente.
- Las reglas de negocio se cumplen.
- Las evaluaciones calculan correctamente promedio y nivel de semaforo.
- El estado de cuenta y el PDF de pagos se generan correctamente.
- La aplicacion funciona en Render como ambiente de pruebas.

## 3. Alcance de las pruebas

### 3.1 Modulos incluidos

Las pruebas cubren los siguientes modulos:

- Inicio de sesion y cierre de sesion
- Seguridad de cuenta
- Administracion de usuarios y roles
- Alumnos
- Clases
- Evaluaciones
- Observaciones
- Pagos
- Estado de cuenta
- Generacion de PDF
- Dashboard
- API interna
- Despliegue en Render

### 3.2 Funcionalidades principales a validar

- Autenticacion con credenciales validas e invalidas.
- Recuperacion y cambio de contrasena.
- Restriccion de acceso segun rol.
- Alta, consulta, actualizacion y eliminacion de alumnos.
- Programacion de clases.
- Bloqueo de choques de horario para alumnos e instructores.
- Registro, edicion y eliminacion de evaluaciones.
- Calculo automatico de promedio y semaforo.
- Registro y filtrado de observaciones.
- Registro de pagos pagados y pendientes.
- Calculo de saldo pendiente.
- Generacion de estado de cuenta en PDF.
- Visualizacion correcta de indicadores en dashboard.

### 3.3 Fuera de alcance

No se consideran dentro de este plan:

- Pruebas de carga masiva con miles de usuarios concurrentes.
- Pruebas de penetracion avanzadas.
- Integracion con pasarelas reales de pago.
- Envio real de correos en produccion, salvo que el ambiente de Render tenga configurado un servicio SMTP.

## 4. Estrategia de pruebas

Se aplicaran tres tipos de pruebas:

| Tipo de prueba | Descripcion | Herramienta |
| --- | --- | --- |
| Pruebas manuales funcionales | Validacion desde el navegador como usuario final. | Navegador web |
| Pruebas automatizadas | Validacion de reglas de negocio, API y seguridad. | PHPUnit |
| Pruebas en cloud | Validacion de funcionamiento en el servicio desplegado. | Render |

## 5. Ambiente de pruebas

### 5.1 Ambiente local

| Elemento | Detalle |
| --- | --- |
| Framework | Laravel 12 |
| Lenguaje | PHP |
| Interfaz | Blade, CSS y JavaScript |
| Base de datos local | Configurada por archivo `.env` |
| Ejecucion de pruebas | `php vendor/bin/phpunit --do-not-cache-result` |

### 5.2 Ambiente cloud

| Elemento | Detalle |
| --- | --- |
| Servicio cloud | Render |
| Runtime | Docker |
| Base de datos | PostgreSQL en Render |
| Rama de despliegue | `main` |
| Despliegue | Automatico despues de `git push` |
| URL publica | Agregar URL final de Render |

Nota: no se deben documentar claves privadas, contrasenas reales ni valores sensibles del archivo `.env`.

## 6. Datos de prueba

### 6.1 Usuario administrador inicial

| Campo | Valor |
| --- | --- |
| Correo | `admin@siga.test` |
| Contrasena | `Admin12345` |
| Rol | `administrador` |

### 6.2 Roles requeridos

Se deben crear usuarios de prueba con los siguientes roles:

- `administrador`
- `recepcionista`
- `instructor`
- `alumno`

### 6.3 Registros requeridos

Antes de ejecutar todas las pruebas, se recomienda contar con:

- Dos alumnos registrados.
- Dos instructores registrados.
- Al menos tres clases programadas.
- Al menos dos evaluaciones.
- Al menos dos observaciones.
- Pagos con estado `pagado` y `pendiente`.

## 7. Criterios de entrada

Las pruebas pueden iniciar cuando:

- El proyecto ya fue corregido.
- El repositorio tiene los cambios actualizados.
- Las migraciones se ejecutan correctamente.
- El usuario administrador inicial existe.
- El sistema puede iniciar sesion.
- Render muestra el servicio como activo.
- La base de datos del ambiente de pruebas esta disponible.

## 8. Criterios de salida

Las pruebas se consideran terminadas cuando:

- Todos los casos prioritarios de la matriz fueron ejecutados.
- Los resultados fueron registrados en `docs/PRUEBAS.md` o en una tabla equivalente.
- Las evidencias fueron capturadas.
- Los errores encontrados fueron corregidos o documentados.
- El sistema funciona correctamente desde la URL publica de Render.

## 9. Criterios de aceptacion

El sistema se considera aprobado si:

- El inicio de sesion funciona correctamente.
- Los roles restringen el acceso a modulos no permitidos.
- Los CRUD principales funcionan sin errores.
- Las evaluaciones generan el nivel correcto: `verde`, `amarillo` o `rojo`.
- Las clases no permiten choques de horario.
- Los pagos calculan correctamente el estado de cuenta.
- El PDF del estado de cuenta se genera correctamente.
- Las pruebas automatizadas pasan.
- El sistema desplegado en Render carga y permite ejecutar los flujos principales.

## 10. Casos de prueba principales

La matriz detallada de casos se encuentra en:

```text
docs/PRUEBAS.md
```

Los grupos principales son:

- Acceso y seguridad.
- Alumnos.
- Clases.
- Evaluaciones y observaciones.
- Pagos y estado de cuenta.
- Dashboard.

## 11. Ejecucion de pruebas automatizadas

Desde la raiz del proyecto:

```bash
php vendor/bin/phpunit --do-not-cache-result
```

Para ejecutar solo las pruebas de API:

```bash
php vendor/bin/phpunit tests/Feature/SigaApiTest.php --do-not-cache-result
```

Para ejecutar solo las pruebas de seguridad:

```bash
php vendor/bin/phpunit tests/Feature/SecurityFlowTest.php --do-not-cache-result
```

## 12. Evidencias requeridas

Se deben guardar capturas o registros de:

- Login correcto.
- Login incorrecto.
- Dashboard principal.
- Listado de alumnos.
- Alta o edicion de alumno.
- Programacion de clase.
- Validacion de choque de horario.
- Registro de evaluacion.
- Evaluacion con nivel rojo.
- Registro de observacion.
- Registro de pago.
- Estado de cuenta.
- PDF generado.
- Servicio desplegado en Render.
- Resultado de pruebas automatizadas.

## 13. Registro de resultados

Formato sugerido:

| No. | Fecha | Modulo | Caso de prueba | Resultado esperado | Resultado obtenido | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | 2026-04-26 | Login | Iniciar sesion con administrador | Entra al dashboard | Entra correctamente | Aprobado | Captura |
| 2 | 2026-04-26 | Evaluaciones | Crear evaluacion con metrica critica en 2 | Nivel rojo | Nivel rojo generado | Aprobado | Captura |

Estados permitidos:

- `Aprobado`
- `Fallido`
- `Bloqueado`
- `Pendiente`

## 14. Riesgos

| Riesgo | Impacto | Accion |
| --- | --- | --- |
| Render no despliega por error de variables de entorno | Alto | Revisar logs y variables configuradas |
| Base de datos sin migraciones | Alto | Ejecutar migraciones desde el arranque Docker |
| Usuario administrador no creado | Medio | Ejecutar seeders |
| Error de permisos por rol | Alto | Validar middleware y casos por tipo de usuario |
| PDF no generado en cloud | Medio | Revisar dependencias y respuesta del endpoint |

## 15. Conclusiones esperadas

Al finalizar la ejecucion, se espera demostrar que SIGA cumple con las funcionalidades corregidas del primer departamental y que puede ser evaluado desde un ambiente cloud en Render, con evidencias de pruebas manuales y automatizadas.
