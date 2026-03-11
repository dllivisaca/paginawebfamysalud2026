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

## Sesion 2026-03-11

### Cambios realizados hoy
- Se mejoro el login admin con opcion para ver/ocultar contrasena y recordatorio de credenciales sin guardar la contrasena en texto plano del lado de la app.
- Se reforzo el cambio de contrasena en `admin/change-password-process.php` y `admin/change-password.php`.
- Se evito usar `trim()` sobre contrasenas.
- Se aplico una politica de nueva contrasena de 12 a 128 caracteres con mayusculas, minusculas, numeros y simbolos.
- Se bloqueo la reutilizacion de la contrasena actual y de historial reciente.
- Despues del cambio exitoso se regenera la sesion y el token CSRF.
- Se integro correo de seguridad por cambio de contrasena usando Composer, `phpmailer/phpmailer`, `vlucas/phpdotenv` y variables en `.env`.
- El cambio de contrasena sigue finalizando en exito aunque falle el envio del correo de alerta.
- Se dejo el envio SMTP con Brevo y resolucion de `MAIL_ENCRYPTION` hacia las constantes correctas de PHPMailer.
- Se limpio el diagnostico temporal del mailer y se dejo solo el manejo de errores util.
- Se agrego control de sesiones admin usando `admin_user_sessions`.
- El login ahora registra la sesion activa, `auth-check.php` valida que siga activa, `logout.php` la marca como inactiva y el cambio de contrasena invalida las otras sesiones del mismo admin.
- Se creo el modulo admin `Menú de navegación` en `admin/menu/index.php`.
- El modulo permite crear, editar, activar, desactivar y eliminar opciones del menu y el boton principal.
- Se fijo la opcion `Inicio` como elemento reservado del sistema en `menu_items` y se protege contra edicion, ocultamiento o eliminacion.
- Se conecto el header reutilizable del sitio a `menu_items` para leer opciones visibles y el boton principal desde la base de datos.
- Se creo la base para paginas dinamicas desde `site_pages` con `page.php` y la primera plantilla `templates/pages/about_v1.php`.
- Se separaron `includes/header.php` e `includes/footer.php` y se tradujo a espanol visible la base publica de `Nosotros`.
- Se ajusto el sidebar del admin para mantenerse visible durante el scroll con `sticky` y scroll interno sin romper el responsive.
- Se agrego la seccion admin `Páginas del sitio` en `admin/pages/index.php` y el enlace correspondiente en el sidebar.

### Archivos creados
- `composer.json`
- `composer.lock`
- `.gitignore`
- `.env`
- `admin/security-mailer.php`
- `admin/admin-session-store.php`
- `admin/menu/index.php`
- `page.php`
- `templates/pages/about_v1.php`
- `includes/header.php`
- `includes/footer.php`
- `admin/pages/index.php`

### Archivos modificados
- `admin/login.php`
- `admin/login-process.php`
- `admin/auth-check.php`
- `admin/logout.php`
- `admin/change-password.php`
- `admin/change-password-process.php`
- `admin/dashboard.php`
- `admin/menu/index.php`
- `admin/admin-session-store.php`
- `admin/security-mailer.php`
- `page.php`
- `includes/header.php`
- `includes/footer.php`
- `templates/pages/about_v1.php`

### Verificacion realizada
- Se ejecuto `php -l` en los archivos PHP creados o modificados durante los cambios principales.
- Los archivos revisados quedaron sin errores de sintaxis en las validaciones ejecutadas.
- Se confirmo que `vendor/autoload.php` existe y que `.env` no queda trackeado por Git.
- Se confirmo que el correo de seguridad usa `.env` y que el fallo de envio no rompe el cambio de contrasena.

### Pendientes recomendados
- Probar el envio SMTP real en un entorno con credenciales definitivas de Brevo y monitoreo de logs.
- Revertir cualquier bypass TLS temporal si siguiera activo en el mailer antes de pasar a produccion.
- Unificar el sidebar del admin en un include comun para evitar repetir cambios en varias pantallas.
- Migrar de forma gradual el resto del frontend publico para que use los includes reutilizables y las paginas dinamicas.
- Definir los `page_key`, `slug` y `template_key` faltantes en `site_pages` para Especialidades, Servicios, Salud Ocupacional, Doctores, Promociones y Contacto.
- Crear la siguiente fase del modulo `admin/pages/` para alta y edicion basica de paginas pendientes.

### Como retomar en una nueva sesion
- Indicar: "Revisa `SECURITY-NOTES.md` y continuemos desde la sesion 2026-03-11".
