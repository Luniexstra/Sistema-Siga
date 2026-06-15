# Reporte de defectos encontrados en SIGA

Este apartado documenta defectos de programacion detectados durante la revision del sistema SIGA. No se consideran errores normales de captura del usuario, sino comportamientos donde el sistema permite acciones que no deberia permitir, omite validaciones, muestra fallas visuales o puede provocar inconsistencias en la informacion.

Los defectos se relacionan con los modulos de agenda, alumnos, registros, interfaz y relaciones de base de datos.

## Tabla 1

Resumen de defectos detectados.

| ID | Modulo | Defecto | Severidad | Estado |
| --- | --- | --- | --- | --- |
| DEF-01 | Agenda | La edicion permite guardar fechas pasadas, fines de semana, festivos y horarios fuera de rango | Alta | Corregido |
| DEF-02 | Clases | La edicion permite duplicar una clase en la misma fecha y hora | Alta | Corregido |
| DEF-04 | Alumnos | La edicion de alumno no valida CURP ni correo como unicos | Alta | Fallido |
| DEF-05 | Alumnos | La eliminacion de alumno borra tambien sus clases por cascada | Alta | Fallido |
| DEF-06 | Registros | El modulo registros guarda datos vacios o correos invalidos | Media | Fallido |
| DEF-07 | Registros | Editar un registro inexistente puede enviar una vista con datos nulos | Media | Fallido |
| DEF-08 | Interfaz | Los textos e iconos aparecen con caracteres corruptos | Baja | Fallido |
| DEF-09 | Despliegue Render | Login o recuperacion de contraseña muestra error 500 en produccion | Alta | Fallido |
| DEF-10 | Seguridad | El captcha de acceso no cambia y siempre muestra la misma operacion | Media | Fallido |
| DEF-11 | Despliegue Render | El redespliegue falla con estado 1 al ejecutar el codigo | Alta | En correccion |
| DEF-12 | Interfaz y assets | Las vistas referencian logo y CSS de SIGA que no existen en public | Media | Fallido |
| DEF-13 | Base de datos | La tabla users en Render no tenia la columna rol requerida por el sistema | Alta | Corregido |

## DEF-01. La edicion de agenda omite reglas de negocio

**Modulo:** Agenda  
**Archivo relacionado:** `app/Http/Controllers/AgendaController.php`  
**Metodo relacionado:** `update`

**Descripcion:**  
Al crear una clase, el sistema valida que la fecha no sea pasada, que no sea fin de semana, que no sea dia festivo y que la hora este entre `08:00` y `18:00`. Sin embargo, al editar una clase esas mismas reglas no se aplican.

**Pasos para reproducir:**
1. Iniciar sesion.
2. Crear una clase valida desde `/agenda/create`.
3. Entrar a editar la clase.
4. Cambiar la fecha por una fecha pasada o por un domingo.
5. Cambiar la hora por `07:00` o `19:00`.
6. Presionar **Actualizar**.

**Resultado obtenido:**  
El sistema guarda la clase editada aunque incumple las reglas de negocio.

**Resultado esperado:**  
El sistema debe rechazar la actualizacion y mostrar un mensaje de error, igual que ocurre al crear una clase.

**Causa probable:**  
El metodo `store` contiene validaciones adicionales con Carbon, fines de semana, festivos, rango de horario y horarios ocupados. El metodo `update` solo valida que `alumno_id`, `fecha` y `hora` existan.

**Recomendacion:**  
Extraer las reglas de agenda a una funcion privada o a un Form Request para reutilizarlas tanto en `store` como en `update`.

**Correccion aplicada:**  
Se actualizo `app/Http/Controllers/AgendaController.php` para reutilizar las reglas de agenda en creacion y edicion. Ahora el metodo `update` valida que el alumno exista, que la fecha sea valida, que no sea pasada, que no sea fin de semana, que no sea dia festivo y que la hora este dentro del rango permitido de `08:00` a `18:00`.

**Verificacion tecnica:**  
Se valido la sintaxis del controlador con:

```bash
php -l app/Http/Controllers/AgendaController.php
```

Resultado:

```txt
No syntax errors detected in app\Http\Controllers\AgendaController.php
```

## DEF-02. La edicion permite duplicar horarios

**Modulo:** Clases  
**Archivo relacionado:** `app/Http/Controllers/ClaseController.php`  
**Metodo relacionado:** `update`

**Descripcion:**  
El sistema evita horarios duplicados cuando se crea una clase, pero no realiza la misma verificacion cuando se edita una clase existente.

**Pasos para reproducir:**
1. Crear una clase con fecha `2026-06-16` y hora `10:00`.
2. Crear otra clase con fecha `2026-06-17` y hora `11:00`.
3. Editar la segunda clase.
4. Cambiarla a fecha `2026-06-16` y hora `10:00`.
5. Presionar **Actualizar**.

**Resultado obtenido:**  
La segunda clase puede quedar guardada en el mismo horario que la primera.

**Resultado esperado:**  
El sistema debe rechazar la edicion porque ya existe una clase en esa fecha y hora.

**Causa probable:**  
La consulta que revisa horarios ocupados solo existe en el metodo `store`, no en `update`.

**Recomendacion:**  
Agregar validacion de duplicados en `update`, ignorando el registro que se esta editando. Tambien se recomienda crear una restriccion unica en base de datos para `fecha` y `hora`.

**Correccion aplicada:**  
En el repo real `Sistema-Siga`, el modulo correspondiente es `Clases`. El archivo `app/Http/Controllers/ClaseController.php` ya valida choques de horario al crear y editar clases. En `update`, las consultas de conflicto usan `where('id', '!=', $clase->id)` para ignorar la clase que se esta editando y evitar falsos positivos.

**Evidencia tecnica:**  
El controlador revisa duplicados por instructor y por alumno:

```php
$conflictoInstructor = Clase::where('fecha', $payload['fecha'])
    ->where('hora', $payload['hora'])
    ->where('instructor_id', $payload['instructor_id'])
    ->where('id', '!=', $clase->id)
    ->exists();

$conflictoAlumno = Clase::where('fecha', $payload['fecha'])
    ->where('hora', $payload['hora'])
    ->where('alumno_id', $payload['alumno_id'])
    ->where('id', '!=', $clase->id)
    ->exists();
```

## DEF-04. La edicion de alumno no valida CURP ni correo como unicos

**Modulo:** Alumnos  
**Archivo relacionado:** `app/Http/Controllers/AlumnoController.php`  
**Metodo relacionado:** `update`

**Descripcion:**  
Al crear alumnos, el sistema valida que `curp` y `correo` sean unicos. En la edicion, esas validaciones se eliminan. Esto puede provocar errores de base de datos o permitir inconsistencias si la restriccion no se ejecuta como se espera.

**Pasos para reproducir:**
1. Crear dos alumnos diferentes.
2. Editar el segundo alumno.
3. Cambiar su CURP o correo por el de primer alumno.
4. Presionar **Actualizar**.

**Resultado obtenido:**  
El sistema intenta actualizar con datos duplicados. Dependiendo de la base de datos, puede mostrar un error tecnico o fallar sin un mensaje claro para el usuario.

**Resultado esperado:**  
El sistema debe mostrar un mensaje de validacion indicando que la CURP o el correo ya fueron registrados.

**Causa probable:**  
El metodo `store` usa `unique:alumnos`, pero el metodo `update` solo usa `required` y `email`.

**Recomendacion:**  
Usar reglas `unique` que ignoren el alumno actual, por ejemplo `unique:alumnos,correo,$id`.

## DEF-05. Eliminar un alumno borra tambien sus clases

**Modulo:** Alumnos y Agenda  
**Archivos relacionados:** `app/Http/Controllers/AlumnoController.php` y `database/migrations/2026_04_15_221722_create_agendas_table.php`

**Descripcion:**  
Cuando se elimina un alumno, sus clases relacionadas tambien se eliminan automaticamente por la regla `onDelete('cascade')`. Esto puede provocar perdida de historial de clases.

**Pasos para reproducir:**
1. Crear un alumno.
2. Agendar una o mas clases para ese alumno.
3. Eliminar el alumno desde el modulo de alumnos.
4. Entrar al modulo agenda.

**Resultado obtenido:**  
Las clases asociadas al alumno desaparecen junto con el registro del alumno.

**Resultado esperado:**  
El sistema deberia impedir eliminar alumnos con clases registradas, pedir confirmacion especial o conservar el historial con una baja logica.

**Causa probable:**  
La llave foranea de `agendas` usa `onDelete('cascade')`, por lo que la base de datos elimina automaticamente los registros dependientes.

**Recomendacion:**  
Cambiar la estrategia a baja logica, restringir la eliminacion si existen clases o conservar el alumno como inactivo.

## DEF-06. El modulo registros guarda datos vacios o correos invalidos

**Modulo:** Registros  
**Archivo relacionado:** `app/Http/Controllers/RegistroController.php`

**Descripcion:**  
El controlador de registros inserta directamente los datos recibidos sin validar si el nombre esta vacio o si el correo tiene formato valido.

**Pasos para reproducir:**
1. Entrar a `/registros/create`.
2. Dejar el nombre vacio.
3. Escribir un correo invalido, por ejemplo `correo-invalido`.
4. Presionar **Guardar**.

**Resultado obtenido:**  
El sistema intenta guardar los datos sin validar su contenido.

**Resultado esperado:**  
El sistema debe rechazar el registro y mostrar mensajes de validacion.

**Causa probable:**  
El metodo `store` usa `DB::table('registros')->insert(...)` sin ejecutar `$request->validate(...)`.

**Recomendacion:**  
Agregar validaciones para `nombre` y `correo`, y considerar usar un modelo Eloquent para mantener consistencia con el resto del sistema.

## DEF-07. Editar un registro inexistente puede generar datos nulos en la vista

**Modulo:** Registros  
**Archivo relacionado:** `app/Http/Controllers/RegistroController.php`

**Descripcion:**  
Cuando se intenta editar un registro con un ID que no existe, el controlador manda la vista aunque la variable `$registro` sea `null`.

**Pasos para reproducir:**
1. Entrar manualmente a una URL como `/registros/999/edit`, usando un ID que no exista.
2. Observar la respuesta del sistema.

**Resultado obtenido:**  
La vista puede fallar al intentar leer propiedades de un registro inexistente.

**Resultado esperado:**  
El sistema debe devolver error 404 controlado o redirigir con un mensaje claro.

**Causa probable:**  
El metodo `edit` usa `first()` en lugar de una busqueda que falle correctamente cuando no existe el registro.

**Recomendacion:**  
Usar `findOrFail`, `firstOrFail` o validar manualmente si el resultado es `null` antes de cargar la vista.

## DEF-08. Textos e iconos con caracteres corruptos

**Modulo:** Interfaz  
**Archivos relacionados:** vistas Blade de `agenda`, `alumnos`, `layouts` y `routes`

**Descripcion:**  
En varias pantallas aparecen textos con caracteres incorrectos, por ejemplo iconos o acentos convertidos en simbolos extranos.

**Pasos para reproducir:**
1. Entrar a `/agenda`.
2. Revisar titulos, botones y encabezados de tabla.
3. Entrar a `/agenda/create` y `/alumnos/create`.

**Resultado obtenido:**  
Se muestran textos como `ðŸ“…`, `DescripciÃ³n`, `âž•` o similares.

**Resultado esperado:**  
La interfaz debe mostrar textos legibles en espanol, por ejemplo `Agenda de Clases`, `Descripcion` o `Nueva Clase`.

**Causa probable:**  
Los archivos fueron guardados o interpretados con una codificacion incorrecta.

**Recomendacion:**  
Guardar los archivos en UTF-8 y reemplazar los caracteres corruptos. Para evitar futuros problemas, se pueden usar textos sin iconos o iconos controlados por una libreria.

## DEF-09. Error 500 al iniciar sesion o recuperar contraseña en Render

**Modulo:** Autenticacion y despliegue cloud  
**Archivos relacionados:** `.env`, `config/mail.php`, `config/database.php`, `routes/auth.php`

**Descripcion:**  
En la version publicada en Render, el sistema puede mostrar error `500` al intentar iniciar sesion o recuperar la contraseña. El error ocurre del lado del servidor y no muestra la causa exacta en pantalla porque el ambiente de produccion oculta los detalles.

**Pasos para reproducir:**
1. Entrar a la URL publicada en Render.
2. Intentar iniciar sesion con un usuario registrado.
3. Si falla, entrar a la opcion de recuperar contraseña.
4. Capturar un correo valido.
5. Enviar la solicitud.

**Resultado obtenido:**  
La aplicacion muestra una pagina de error `500`.

**Log observado en Render:**  

```txt
[Mon Jun 15 16:44:46 2026] 10.25.121.133:53672 [500]: POST /login
```

**Resultado esperado:**  
El sistema debe iniciar sesion correctamente o, en recuperacion de contraseña, mostrar un mensaje indicando que el enlace fue enviado o que el correo no existe.

**Causa probable:**  
Para recuperacion de contraseña, la causa mas probable es que Render no tenga un servicio SMTP configurado. En ambiente local se usa `MAIL_HOST=mailpit`, pero `mailpit` no existe en Render. Para login, el error puede estar relacionado con variables de entorno, conexion a base de datos, migraciones faltantes o falta de `APP_KEY`.

**Evidencia recomendada:**  
Revisar los logs en `Render > Web Service > Logs` justo despues de reproducir el error. Buscar mensajes como `SQLSTATE`, `Connection refused`, `No application encryption key`, `Base table or view not found` o errores relacionados con SMTP.

**Recomendacion:**  
Configurar correctamente las variables de entorno en Render. Para pruebas sin correo real, usar:

```env
MAIL_MAILER=log
MAIL_FROM_ADDRESS=no-reply@siga.test
MAIL_FROM_NAME=SIGA
```

Tambien validar que existan las variables de base de datos y aplicacion:

```env
APP_KEY=base64:...
APP_URL=https://sistema-siga.onrender.com
DB_CONNECTION=pgsql
DATABASE_URL=postgresql://...
```

## DEF-10. Captcha fijo en el formulario de acceso

**Modulo:** Seguridad y autenticacion  
**Archivo relacionado:** formulario de login en la version desplegada

**Descripcion:**  
En la pagina publicada, el captcha no cambia entre intentos y siempre pregunta la misma operacion: `6 + 9`. Esto reduce la utilidad del captcha, ya que la respuesta puede memorizarse o automatizarse facilmente.

**Pasos para reproducir:**
1. Entrar a la pagina de login publicada en Render.
2. Observar la pregunta del captcha.
3. Recargar la pagina.
4. Intentar entrar nuevamente o abrir la pagina en otra pestaña.
5. Verificar que la pregunta sigue siendo `6 + 9`.

**Resultado obtenido:**  
El captcha permanece fijo y siempre solicita resolver la misma suma.

**Resultado esperado:**  
El captcha debe generar una operacion diferente en cada carga, intento de login o sesion. La respuesta correcta debe validarse en el servidor.

**Causa probable:**  
La operacion del captcha esta escrita de forma estatica en la vista o se inicializa con valores fijos. Tambien es posible que no se este guardando una nueva respuesta en sesion para cada intento.

**Evidencia recomendada:**  
Tomar capturas de la pantalla de login despues de recargar la pagina varias veces, mostrando que la pregunta no cambia.

**Recomendacion:**  
Generar dos numeros aleatorios desde el backend, guardar el resultado en sesion y validar la respuesta al enviar el formulario. Despues de cada intento, correcto o incorrecto, se debe regenerar el captcha.

## DEF-11. Deploy fallido en Render al redesplegar

**Modulo:** Despliegue cloud  
**Archivos relacionados:** configuracion del servicio en Render, `composer.json`, `package.json`, archivos de arranque del despliegue

**Descripcion:**  
Al intentar redesplegar la aplicacion en Render, el proceso falla y el servicio no queda publicado correctamente. Render muestra el mensaje `Deploy failed` y reporta que el codigo termino con estado `1`.

**Pasos para reproducir:**
1. Entrar al servicio web de SIGA en Render.
2. Ejecutar un nuevo deploy.
3. Esperar a que termine el proceso de build o start.
4. Revisar el estado final del despliegue.

**Resultado obtenido:**  
Render muestra el despliegue como fallido.

**Evidencia observada:**  

```txt
Deploy failed for a8f4dcb: Actualizar branding visual de SIGA
Exited with status 1 while running your code.
```

Tambien se observo el siguiente error en los logs de Render:

```txt
SQLSTATE[08006] [7] could not translate host name "dpg-d7jdaf58nd3s73a89mr0-a" to address: Name or service not known
Connection: pgsql, Host: dpg-d7jdaf58nd3s73a89mr0-a, Port: 5432, Database: siga_1sss
```

**Resultado esperado:**  
El deploy debe terminar correctamente y dejar la aplicacion disponible desde la URL publica.

**Causa probable:**  
El error `status 1` indica que algun comando de build o arranque fallo. En este caso, la causa observada es que Render no puede resolver el nombre del host configurado para PostgreSQL. Esto puede ocurrir si la variable `DATABASE_URL` o `DB_HOST` apunta a una base de datos eliminada, mal copiada, incompleta o no accesible desde el servicio web.

**Evidencia recomendada:**  
Abrir el deploy fallido en Render y revisar `deploy logs`. Copiar las ultimas lineas antes del mensaje de fallo, especialmente errores relacionados con `composer install`, `npm install`, `npm run build`, `php artisan migrate`, `APP_KEY`, `DATABASE_URL` o archivos no encontrados.

**Recomendacion:**  
Actualizar las variables de entorno de base de datos en Render usando la cadena correcta del servicio PostgreSQL activo. Se recomienda copiar la `Internal Database URL` desde el panel de la base de datos de Render y pegarla en `DATABASE_URL` del servicio web. Tambien revisar que `DB_CONNECTION=pgsql` y eliminar valores antiguos de `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD` si entran en conflicto con `DATABASE_URL`.

**Seguimiento de correccion:**  
Se creo una nueva base de datos PostgreSQL en Render y se actualizo la configuracion del Web Service para usar la nueva `Internal Database URL`. Despues del cambio se debe ejecutar un nuevo deploy y verificar que las migraciones se ejecuten correctamente.

**Observacion importante:**  
Al crear una base de datos nueva, los datos de la base anterior no se copian automaticamente. Si la base anterior ya no esta disponible o no se migro la informacion, usuarios, contraseñas, alumnos, clases y demas registros pueden no existir en la nueva base. En ese caso sera necesario registrar un nuevo usuario administrador o ejecutar seeders/migraciones con datos iniciales.

## DEF-12. Assets de branding referenciados pero no encontrados

**Modulo:** Interfaz y archivos publicos  
**Archivos relacionados:** `resources/views/auth/login.blade.php`, `resources/views/auth/forgot-password.blade.php`, `resources/views/auth/reset-password.blade.php`, `resources/views/layouts/siga.blade.php`, `public/siga-logo.png`, `public/siga.css`

**Descripcion:**  
En el cambio de branding visual se agregaron referencias a `siga-logo.png` y `siga.css` mediante `asset(...)`. Si esos archivos no existen dentro de la carpeta `public`, el navegador no puede cargarlos correctamente.

**Pasos para reproducir:**
1. Abrir la pagina de login o recuperacion de contraseña.
2. Revisar si carga el logo de SIGA.
3. Abrir las herramientas del navegador.
4. Revisar la pestaña `Network`.
5. Buscar peticiones a `/siga-logo.png` o `/siga.css`.

**Resultado obtenido:**  
Los assets pueden responder `404 Not Found` si no fueron incluidos en el despliegue.

**Resultado esperado:**  
El logo, favicon y hoja de estilos deben cargarse correctamente en login, recuperacion de contraseña y layout principal.

**Causa probable:**  
Las vistas fueron actualizadas para usar archivos publicos nuevos, pero los archivos no existen o no fueron agregados al repositorio/despliegue.

**Evidencia observada:**  
En la copia local revisada no se encontraron los archivos:

```txt
public/siga-logo.png
public/siga.css
```

**Recomendacion:**  
Agregar los archivos faltantes dentro de `public` y confirmar que se suban al repositorio. Si se usan assets compilados por Vite, mover el CSS a `resources/css` y cargarlo con `@vite`.

## DEF-13. Columna rol faltante en la tabla users de Render

**Modulo:** Base de datos, autenticacion y permisos  
**Archivos relacionados:** `database/migrations/2014_10_12_000000_create_users_table.php`, `app/Http/Middleware/RolMiddleware.php`, `app/Models/User.php`

**Descripcion:**  
Al intentar crear un usuario administrador en la base de datos de Render, el sistema mostro un error indicando que la columna `rol` no existia en la tabla `users`. Esto impide asignar roles y puede afectar el acceso a modulos protegidos por middleware.

**Pasos para reproducir:**
1. Entrar al shell o consola de Render.
2. Ejecutar `php artisan tinker`.
3. Intentar crear un usuario con rol `admin`.
4. Observar la respuesta de la base de datos.

**Resultado obtenido:**  
La base de datos rechaza la insercion porque la columna `rol` no existe.

**Evidencia observada:**  

```txt
Illuminate\Database\QueryException
SQLSTATE[42703]: Undefined column: 7 ERROR:
column "rol" of relation "users" does not exist
```

**Resultado esperado:**  
La tabla `users` debe contener la columna `rol`, ya que el sistema la utiliza para diferenciar accesos como `admin`, `alumno` u otros roles.

**Causa probable:**  
La base de datos en Render no coincidia con la estructura esperada por el codigo actual. Es posible que las migraciones se hayan ejecutado antes de agregar la columna `rol`, que la base proviniera de una version anterior o que no se ejecutaran todas las migraciones correctamente.

**Correccion aplicada:**  
Se agrego la columna faltante directamente en la base de datos con una instruccion SQL desde Tinker:

```php
\Illuminate\Support\Facades\DB::statement("ALTER TABLE users ADD COLUMN rol VARCHAR(255) DEFAULT 'alumno'");
```

Despues se pudo crear el usuario administrador con rol `admin`.

**Recomendacion:**  
Crear una migracion formal para agregar la columna `rol` si no existe, en lugar de depender de una correccion manual. Tambien se recomienda revisar que el proceso de deploy ejecute `php artisan migrate --force` correctamente.

## Conclusion

Los defectos principales se concentran en reglas que existen al crear datos, pero no al editarlos, y en configuraciones incompletas del ambiente desplegado. Esto representa un riesgo porque el sistema puede parecer correcto en local, pero fallar al operar en produccion.

Los defectos de mayor prioridad son `DEF-01`, `DEF-02`, `DEF-04`, `DEF-05`, `DEF-09`, `DEF-10`, `DEF-11` y `DEF-13`, ya que afectan reglas de negocio, integridad de datos, historial del sistema, acceso al ambiente publicado, controles basicos de seguridad, disponibilidad del despliegue y control de permisos por rol.
