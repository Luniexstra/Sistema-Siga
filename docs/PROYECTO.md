# Documentacion del Proyecto SIGA

## 1. Descripcion general

SIGA es un sistema web para la gestion academica y administrativa de una escuela de manejo.

El sistema cubre:
- administracion de alumnos
- agenda de clases
- evaluaciones tecnicas con semaforizacion
- observaciones por clase
- control de pagos y estado de cuenta
- administracion de usuarios y roles
- seguridad de acceso, recuperacion de contrasena y verificacion de correo

La aplicacion fue desarrollada con Laravel 12, una interfaz web basada en Blade y una API interna consumida por el panel.

## 2. Objetivo del sistema

Centralizar en una sola plataforma los procesos academicos, operativos y financieros de la escuela, permitiendo que cada tipo de usuario acceda solo a los modulos y acciones que le corresponden.

## 3. Arquitectura general

El proyecto se compone de tres capas principales:

### 3.1 Backend Laravel

Responsable de:
- logica de negocio
- validaciones
- autorizacion por rol
- relaciones entre modelos
- generacion de PDF
- seguridad de acceso

Controladores principales:
- `AlumnoController`
- `ClaseController`
- `EvaluacionController`
- `ObservacionController`
- `PagoController`
- `UserController`
- `AuthController`
- `SecurityController`

### 3.2 Interfaz web

Construida con:
- Blade
- `public/siga.css`
- `public/siga.js`

La interfaz esta dividida por modulos y trabaja sobre las rutas web protegidas por autenticacion.

### 3.3 API interna

La API se encuentra en `routes/api.php` y es utilizada por el panel para obtener, crear, editar y eliminar informacion.

## 4. Modelos principales

### 4.1 User

Representa las cuentas del sistema.

Roles disponibles:
- `administrador`
- `recepcionista`
- `instructor`
- `alumno`

Responsabilidades:
- autenticacion
- verificacion de correo
- control de permisos

### 4.2 Alumno

Representa el expediente academico y administrativo del alumno.

Campos relevantes:
- nombre
- apellido
- curp
- telefono
- correo
- fecha_ingreso
- costo_total
- user_id

Relaciones:
- pertenece opcionalmente a un `User`
- tiene muchas `Clase`
- tiene muchos `Pago`

### 4.3 Clase

Representa una sesion programada.

Campos relevantes:
- fecha
- hora
- alumno_id
- instructor_id

Relaciones:
- pertenece a `Alumno`
- pertenece a `User` como instructor
- tiene una `Evaluacion`
- tiene muchas `Observacion`

### 4.4 Evaluacion

Representa la evaluacion tecnica de una clase.

Campos relevantes:
- clase_id
- senales
- frenado
- seguridad
- promedio
- nivel

`nivel` puede ser:
- `verde`
- `amarillo`
- `rojo`

### 4.5 Observacion

Representa comentarios privados de seguimiento asociados a una clase.

Campos relevantes:
- clase_id
- comentario

### 4.6 Pago

Representa un movimiento financiero del alumno.

Campos relevantes:
- alumno_id
- monto
- fecha_pago
- estado

Estados:
- `pagado`
- `pendiente`

## 5. Reglas de negocio principales

### 5.1 Alumnos

- un alumno puede estar relacionado con un usuario del sistema
- el alumno con rol `alumno` solo puede consultar y modificar su propia informacion permitida
- administracion y recepcion controlan el expediente completo

### 5.2 Clases

- no puede existir choque de horario para un mismo instructor
- no puede existir choque de horario para un mismo alumno
- administracion y recepcion gestionan altas, cambios y bajas
- instructores y alumnos solo ven sus propias clases

### 5.3 Evaluaciones

- solo el instructor asignado a la clase puede crear, editar o eliminar una evaluacion
- una clase solo puede tener una evaluacion
- el promedio se calcula automaticamente
- si una metrica critica es menor o igual a 2, el nivel pasa a rojo

### 5.4 Observaciones

- solo el instructor asignado a la clase puede crear, editar o eliminar observaciones
- alumnos e instructores solo visualizan observaciones relacionadas con sus clases

### 5.5 Pagos

- administracion y recepcion registran pagos
- el alumno solo puede ver sus propios pagos y su estado de cuenta
- el estado de cuenta calcula:
  - costo total
  - total pagado
  - saldo pendiente
  - estado de cuenta

### 5.6 Usuarios

- solo administracion gestiona usuarios
- un usuario no puede eliminar su propia cuenta mientras esta autenticado

## 6. Seguridad

### 6.1 Inicio de sesion

- autenticacion web por sesion
- captcha matematico simple
- opcion para mostrar u ocultar contrasena

### 6.2 Recuperacion de acceso

- solicitud de restablecimiento de contrasena
- formulario para definir nueva contrasena

### 6.3 Verificacion de correo

- el usuario puede generar un enlace de verificacion
- la cuenta puede marcarse como verificada por enlace firmado

### 6.4 Middleware de rol

El middleware `EnsureUserRole` protege rutas web y API segun el rol del usuario.

## 7. Rutas principales

### 7.1 Rutas web

- `/login`
- `/inicio`
- `/alumnos`
- `/clases`
- `/evaluaciones`
- `/observaciones`
- `/pagos`
- `/usuarios`
- `/seguridad`

### 7.2 Rutas API principales

- `/api/alumnos`
- `/api/clases`
- `/api/evaluaciones`
- `/api/observaciones`
- `/api/pagos`
- `/api/users`
- `/api/me`
- `/api/catalogos/instructores`

## 8. Interfaz por modulos

El sistema esta organizado en pantallas separadas:
- dashboard
- alumnos
- clases
- evaluaciones
- observaciones
- pagos
- usuarios
- seguridad

La interfaz adapta menus y acciones segun el rol autenticado.

## 9. Dashboard

El dashboard principal muestra:
- total de alumnos
- total de clases
- total de evaluaciones
- total de pagos
- evaluaciones en rojo
- pagos pendientes
- distribucion de semaforo
- proximas clases
- resumen de cartera

## 10. Estado de cuenta y PDF

El sistema permite consultar el estado de cuenta del alumno y generar un PDF con:
- datos del alumno
- costo total
- total pagado
- saldo pendiente
- historial de pagos

## 11. Despliegue

El proyecto fue preparado para despliegue con Docker.

Archivos relevantes:
- `Dockerfile`
- `docker/start.sh`
- `.dockerignore`

En Render:
- se usa `PostgreSQL`
- el servicio web corre con Docker
- la aplicacion toma `DATABASE_URL`
- al iniciar se ejecutan migraciones automaticamente

## 12. Variables de entorno importantes

- `APP_NAME`
- `APP_ENV`
- `APP_DEBUG`
- `APP_URL`
- `APP_KEY`
- `LOG_CHANNEL`
- `LOG_LEVEL`
- `DB_CONNECTION`
- `DATABASE_URL`
- `SESSION_DRIVER`
- `CACHE_STORE`
- `QUEUE_CONNECTION`

## 13. Usuario inicial

El seeder crea:
- correo: `admin@siga.test`
- contrasena: `Admin12345`

## 14. Pruebas

El sistema cuenta con pruebas automatizadas para:
- creacion de alumnos
- validacion de choques de horario
- evaluaciones y semaforizacion
- observaciones
- pagos y estado de cuenta
- PDF
- permisos por rol
- seguridad de contrasena

Comando:

```bash
php vendor/bin/phpunit --do-not-cache-result
```

## 15. Flujo recomendado de uso

1. Iniciar sesion como administrador
2. Crear usuarios por rol
3. Registrar alumnos
4. Programar clases
5. Capturar evaluaciones y observaciones
6. Registrar pagos
7. Consultar dashboard y estado de cuenta
8. Revisar seguridad y verificacion de correo

## 16. Estado actual del proyecto

El proyecto se encuentra funcional para:
- uso academico y administrativo
- despliegue en Render
- pruebas de demostracion
- presentacion o entrega escolar
