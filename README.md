# SIGA

Sistema de Instruccion y Gestion Automotriz desarrollado con Laravel.

SIGA permite administrar una escuela de manejo con modulos separados por rol:
- alumnos
- clases
- evaluaciones con semaforizacion
- observaciones privadas
- pagos y estado de cuenta
- usuarios y roles
- seguridad de acceso

## Roles soportados

- `administrador`
- `recepcionista`
- `instructor`
- `alumno`

Cada rol ve solo los modulos y acciones que le corresponden en la interfaz y en la API.

## Funcionalidad principal

- Login web con captcha y opcion de mostrar contrasena
- CRUD de alumnos, clases, evaluaciones, observaciones, pagos y usuarios
- Restricciones por rol y por propiedad del recurso
- Relacion entre `users` y `alumnos`
- Estado de cuenta por alumno y descarga en PDF
- Dashboard con indicadores, agenda y resumen de cartera
- Recuperacion de contrasena
- Cambio de contrasena desde el panel
- Verificacion de correo con enlace firmado

## Requisitos

- PHP 8.2+
- Composer
- SQLite, MySQL o MariaDB

## Instalacion

1. Instala dependencias:

```bash
composer install
```

2. Copia variables de entorno:

```bash
cp .env.example .env
```

En Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

3. Genera la clave:

```bash
php artisan key:generate
```

4. Configura la base de datos en `.env`

5. Ejecuta migraciones y seeder:

```bash
php artisan migrate
php artisan db:seed
```

6. Levanta el servidor:

```bash
php artisan serve
```

## Acceso inicial

El seeder crea este administrador:

- correo: `admin@siga.test`
- contrasena: `Admin12345`

## Rutas importantes

- Login: `/login`
- Dashboard: `/inicio`
- Seguridad: `/seguridad`
- API base: `/api/...`

## Comandos utiles

Ver rutas:

```bash
php artisan route:list
```

Correr pruebas:

```bash
php vendor/bin/phpunit --do-not-cache-result
```

## Flujo sugerido de prueba

1. Inicia sesion como administrador
2. Crea usuarios por rol
3. Registra alumnos
4. Programa clases
5. Como instructor, captura evaluaciones y observaciones
6. Como recepcionista o administrador, registra pagos
7. Consulta dashboard, estado de cuenta y seguridad

## Notas de desarrollo

- La interfaz usa `public/siga.css` y `public/siga.js` como fallback cuando no hay build frontend.
- En ambiente local, los flujos de recuperacion y verificacion muestran enlaces de respaldo aunque no haya correo real configurado.
- La API todavia admite `X-User-Id` como respaldo para pruebas manuales, pero la interfaz web usa sesion real.

## Estado actual

Proyecto funcional, con pruebas automatizadas y enfocado en presentacion tipo sistema real.
