# Reporte de defectos encontrados en SIGA

Este documento registra los defectos verificados contra el repositorio original del sistema SIGA:

```txt
C:\Users\Jony Bravo\Sistema-Siga
```

Se eliminaron del reporte los errores que pertenecian a copias anteriores del proyecto, como `mi-proyecto`, el modulo viejo de `agenda`, el modulo `registros` y referencias a archivos que no existen en el sistema original.

## Tabla 1

Resumen de defectos aplicables al sistema original.

| ID | Modulo | Defecto | Severidad | Estado |
| --- | --- | --- | --- | --- |
| DEF-01 | Clases | La edicion permitia guardar fechas pasadas, fines de semana, festivos y horarios fuera de rango | Alta | Corregido |
| DEF-02 | Clases | La edicion podia permitir duplicar una clase en la misma fecha y hora | Alta | Corregido |
| DEF-09 | Despliegue Render | Login o recuperacion de contrasena mostro error 500 en produccion | Alta | En correccion |
| DEF-10 | Seguridad | El captcha se mantiene igual al recargar dentro de la misma sesion | Media | Pendiente |
| DEF-11 | Despliegue Render | El redespliegue fallo con estado 1 por conexion incorrecta a PostgreSQL | Alta | En correccion |
| DEF-13 | Base de datos | Confusion entre las columnas `role` y `rol` al crear usuarios administradores | Alta | Corregido manualmente |

## DEF-01. La edicion de clases omitia reglas de negocio

**Modulo:** Clases  
**Archivo relacionado:** `app/Http/Controllers/ClaseController.php`  
**Metodo relacionado:** `update`

**Descripcion:**  
El modulo de clases debia impedir fechas pasadas, fines de semana, dias festivos y horarios fuera del rango laboral. La validacion se reforzo para que aplique tanto al crear como al editar clases.

**Pasos para reproducir:**
1. Iniciar sesion en SIGA.
2. Entrar al modulo **Clases**.
3. Crear o editar una clase.
4. Usar una fecha pasada, un sabado, un domingo, un festivo o una hora fuera del rango `08:00` a `18:00`.
5. Guardar la clase.

**Resultado esperado:**  
El sistema debe rechazar la operacion con un mensaje de validacion.

**Correccion aplicada:**  
Se agrego la funcion `validarReglasClase` en `ClaseController.php` y se reutilizo en `store` y `update`.

**Validaciones cubiertas:**
- No permitir fechas pasadas.
- No permitir fines de semana.
- No permitir dias festivos.
- No permitir horarios menores a `08:00` o mayores a `18:00`.

**Verificacion tecnica:**  
Se valido la sintaxis del controlador con:

```bash
php -l app/Http/Controllers/ClaseController.php
```

Resultado:

```txt
No syntax errors detected in app\Http\Controllers\ClaseController.php
```

## DEF-02. La edicion podia duplicar horarios

**Modulo:** Clases  
**Archivo relacionado:** `app/Http/Controllers/ClaseController.php`  
**Metodo relacionado:** `update`

**Descripcion:**  
El sistema debe impedir que un mismo instructor o un mismo alumno tenga dos clases en la misma fecha y hora.

**Estado real en el sistema original:**  
El defecto ya se encuentra corregido en el repositorio original. El controlador valida duplicados al crear y al editar clases.

**Evidencia tecnica:**  
En `update`, el sistema ignora la clase que se esta editando para evitar falsos positivos:

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

**Resultado esperado:**  
Si existe choque de horario, el sistema debe responder con error `422`.

## DEF-09. Error 500 al iniciar sesion o recuperar contrasena en Render

**Modulo:** Autenticacion y despliegue cloud  
**Archivos relacionados:** `routes/web.php`, `app/Http/Controllers/AuthController.php`, `app/Http/Controllers/SecurityController.php`, variables de entorno de Render

**Descripcion:**  
En la version publicada en Render, se observo error `500` al intentar iniciar sesion. El error se relaciono con problemas de conexion a la base de datos.

**Log observado en Render:**

```txt
[Mon Jun 15 16:44:46 2026] 10.25.121.133:53672 [500]: POST /login
```

**Causa probable:**  
El login consulta la tabla `users`. Si la aplicacion no puede conectarse a PostgreSQL, si faltan migraciones o si las variables de entorno apuntan a una base incorrecta, Laravel responde con error `500`.

**Estado:**  
En correccion. Se actualizo la configuracion de base de datos en Render y se creo una base PostgreSQL nueva.

**Recomendacion:**  
Mantener configuradas estas variables en el Web Service:

```env
DB_CONNECTION=pgsql
DATABASE_URL=postgresql://...
LOG_CHANNEL=stderr
LOG_LEVEL=debug
```

## DEF-10. Captcha no cambia al recargar en la misma sesion

**Modulo:** Seguridad y autenticacion  
**Archivo relacionado:** `app/Http/Controllers/AuthController.php`

**Descripcion:**  
El captcha del login se genera de forma aleatoria, pero se guarda en sesion. Por eso, al recargar la pagina dentro de la misma sesion, puede mantenerse la misma pregunta.

**Codigo relacionado:**

```php
$captchaQuestion = $request->session()->get('login_captcha_question');

if (! is_string($captchaQuestion) || ! $request->session()->has('login_captcha_answer')) {
    $captcha = $this->generateCaptcha($request);
    $captchaQuestion = $captcha['question'];
}
```

**Resultado obtenido:**  
La pregunta puede mantenerse igual al recargar la pagina de login.

**Resultado esperado:**  
El captcha deberia regenerarse en cada carga de la pantalla o al menos en cada intento de inicio de sesion.

**Recomendacion:**  
Modificar `showLogin` para generar siempre un nuevo captcha al cargar `/login`, o agregar un boton para regenerarlo.

## DEF-11. Deploy fallido en Render por conexion a PostgreSQL

**Modulo:** Despliegue cloud  
**Archivos relacionados:** variables de entorno en Render, PostgreSQL, `Dockerfile`, comandos de arranque

**Descripcion:**  
Al redesplegar en Render, el servicio fallo porque Laravel no pudo resolver el host configurado para PostgreSQL.

**Evidencia observada:**

```txt
Deploy failed for a8f4dcb: Actualizar branding visual de SIGA
Exited with status 1 while running your code.
```

Tambien se observo:

```txt
SQLSTATE[08006] [7] could not translate host name "dpg-d7jdaf58nd3s73a89mr0-a" to address: Name or service not known
Connection: pgsql, Host: dpg-d7jdaf58nd3s73a89mr0-a, Port: 5432, Database: siga_1sss
```

**Causa:**  
La variable `DATABASE_URL` o los datos equivalentes de PostgreSQL apuntaban a un host inexistente, eliminado o no accesible desde el Web Service.

**Correccion aplicada:**  
Se creo una nueva base PostgreSQL en Render y se actualizo el Web Service para usar la nueva `Internal Database URL`.

**Recomendacion:**  
Copiar siempre la `Internal Database URL` desde el servicio PostgreSQL activo y pegarla en `DATABASE_URL` del Web Service.

## DEF-13. Confusion entre `role` y `rol` al crear usuarios administradores

**Modulo:** Base de datos, autenticacion y permisos  
**Archivos relacionados:** `app/Models/User.php`, `database/migrations/2026_04_17_002000_add_role_to_users_table.php`

**Descripcion:**  
Durante la creacion manual de un usuario administrador en Render se intento usar la columna `rol`, pero el sistema original SIGA utiliza la columna `role`.

**Evidencia del sistema original:**

```php
// app/Models/User.php
protected $fillable = [
    'name',
    'email',
    'email_verified_at',
    'role',
    'password',
];
```

La migracion correspondiente tambien crea la columna `role`:

```php
$table->string('role')->default('administrador')->after('email');
```

**Resultado obtenido:**  
Al usar `rol`, PostgreSQL respondio que la columna no existia.

**Resultado esperado:**  
La creacion manual de usuarios debe usar la columna correcta:

```php
$user = \App\Models\User::firstOrNew(['email' => 'admin@siga.test']);
$user->name = 'Admin SIGA';
$user->password = \Illuminate\Support\Facades\Hash::make('Admin12345');
$user->role = \App\Models\User::ROLE_ADMINISTRADOR;
$user->save();
```

**Correccion aplicada:**  
Se identifico que el campo correcto para el repo original es `role`, no `rol`.

**Recomendacion:**  
Evitar instrucciones manuales basadas en copias antiguas del proyecto. Para crear administradores, usar `role` o crear un seeder oficial.

## Defectos descartados

Los siguientes defectos fueron retirados del reporte porque pertenecian a copias antiguas del proyecto y no al repositorio original `Sistema-Siga`:

| Defecto anterior | Motivo de descarte |
| --- | --- |
| DEF-04 | El `AlumnoController` real si valida `curp` y `correo` como unicos al editar. |
| DEF-05 | La tabla real `clases` no usa `onDelete('cascade')` para borrar clases automaticamente al eliminar alumnos. |
| DEF-06 | No existe `RegistroController` en el repositorio original. |
| DEF-07 | No existe modulo `registros` en el repositorio original. |
| DEF-08 | No se encontraron caracteres corruptos en las vistas reales de `Sistema-Siga`. |
| DEF-12 | En el repositorio original si existen `public/siga-logo.png` y `public/siga.css`. |

## Conclusion

El reporte queda alineado con el sistema original `Sistema-Siga`. Los defectos actualmente aplicables se concentran en reglas de clases, despliegue en Render, configuracion de base de datos, captcha y manejo correcto de roles.
