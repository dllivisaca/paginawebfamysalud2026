# Security Notes

## Sesion 2026-03-10

### Cambios realizados en login admin
- Se agrego `admin/session-bootstrap.php` para centralizar una configuracion de sesion mas segura.
- Se activaron cookies de sesion con `HttpOnly`, `SameSite=Lax`, `use_only_cookies` y `use_strict_mode`.
- `Secure` queda habilitado automaticamente cuando el sitio corre bajo HTTPS.
- Se agrego token CSRF al formulario de login en `admin/login.php`.
- Se valido CSRF en `admin/login-process.php`.
- Se elimino la confianza en `HTTP_CLIENT_IP` y `HTTP_X_FORWARDED_FOR`; ahora se usa `REMOTE_ADDR`.
- Se unificaron los errores de autenticacion para evitar enumeracion de usuarios inactivos.
- Se removio la exposicion directa de errores internos de MySQL con `die(...)`.
- Se elimino la regeneracion duplicada de `session_regenerate_id(true)`.
- El cierre de sesion ahora usa `POST` con CSRF en lugar de `GET`.

### Archivos modificados
- `admin/session-bootstrap.php`
- `admin/login.php`
- `admin/login-process.php`
- `admin/auth-check.php`
- `admin/dashboard.php`
- `admin/logout.php`

### Verificacion realizada
- Se ejecuto `php -l` sobre los archivos modificados.
- Todos quedaron sin errores de sintaxis.

### Pendientes recomendados
- Revisar `db.php` para mover credenciales fuera del codigo y evitar usar `root`.
- Revisar `forms/contact.php` y `forms/appointment.php` por abuso de correo, spam y falta de controles anti-bot.
- Confirmar que el entorno productivo usa HTTPS.
- Revisar permisos y estructura real del modulo `admin/menu/` si existe fuera de este arbol.

### Como retomar en una nueva sesion
- Indicar: "Revisa `SECURITY-NOTES.md` y continuemos desde ahi".
