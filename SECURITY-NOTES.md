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

## Sesion 2026-03-12

### Cambios realizados hoy
- Se convirtio `admin/pages/index.php` en un listado basado solo en registros reales de `site_pages`, eliminando la mezcla con paginas esperadas hardcodeadas.
- Se agrego CRUD base para `admin/pages/` con creacion, edicion, activacion, desactivacion y eliminacion, manteniendo protegida la pagina `Inicio`.
- Se creo `admin/pages/edit.php` para crear y editar paginas reales de `site_pages` con validaciones de unicidad, CSRF y mensajes en espanol.
- Se bloqueo la edicion de `page_key` despues de crear una pagina y se mejoro el autocompletado del formulario para slugs, clave interna, H1, SEO y canonicidad.
- `meta_robots` dejo de ser visible en admin y ahora se fuerza internamente a `index,follow`.
- `template_key` en `admin/pages/edit.php` paso de texto libre a `select` validado contra `page_templates` activas.
- Se mejoro la generacion automatica de `canonical_url` usando deteccion conservadora de base URL del entorno actual.
- Se ajusto `page.php` para resolver plantillas publicas con compatibilidad entre `template_key` nuevos y nombres fisicos heredados en `templates/pages/`.
- Se reorganizo `admin/menu/` para separar listado y formularios: `admin/menu/index.php` quedo como vista resumida, `admin/menu/edit.php` para crear/editar opciones, y `admin/menu/button-edit.php` para el boton principal.
- Se incorporo soporte admin para tipos de opcion del menu `PÃ¡gina interna` y `Enlace personalizado`, con persistencia compatible en `menu_items` usando `link_type`, `site_page_id` y `url`.
- Se mantuvo `Inicio` como opcion protegida del menu, pero ahora con edicion controlada de destino/tipo/target sin permitir ocultarla, eliminarla o moverla.
- Se mejoro la UX del listado de paginas y menu ocultando IDs tecnicos cuando no aportan valor visual y usando numeracion o posicion como referencia principal.
- Se corrigio HTML invalido por formularios anidados en el bloque del boton principal del admin menu antes de separar su edicion.

### Archivos creados
- `admin/pages/edit.php`
- `admin/menu/edit.php`
- `admin/menu/button-edit.php`
- `admin/menu/menu-helpers.php`

### Archivos modificados
- `admin/pages/index.php`
- `admin/pages/edit.php`
- `page.php`
- `admin/menu/index.php`
- `admin/menu/edit.php`
- `admin/menu/menu-helpers.php`
- `includes/header.php`

### Verificacion realizada
- Se ejecuto `php -l` sobre los archivos PHP creados o modificados en las iteraciones principales del dia.
- Los archivos validados quedaron sin errores de sintaxis en las comprobaciones ejecutadas.

### Pendientes recomendados
- Ejecutar manualmente y verificar en todos los entornos el `ALTER TABLE` de `menu_items` para `link_type` y `site_page_id` si aun no se aplico.
- Probar de punta a punta en navegador los flujos de crear/editar/toggle/eliminar en `admin/pages/` y `admin/menu/`.
- Revisar dependencias futuras antes de permitir eliminar paginas si despues se enlazan desde otros modulos como contenido o bloques.
- Reemplazar caracteres con codificacion rota heredados en algunos textos antiguos del admin para evitar artefactos visuales.
- Consolidar estilos compartidos del admin si el numero de pantallas sigue creciendo.

### Como retomar en una nueva sesion
- Indicar: "Revisa `SECURITY-NOTES.md` y continuemos desde la sesion 2026-03-12".

## Sesion 2026-03-16

### Cambios realizados hoy
- Se implemento edicion de contenido por pagina para la plantilla `about` usando `site_page_content_fields`, `site_page_content_repeater_items` y `site_page_content_repeater_item_fields`, sin alterar la metadata base de `site_pages`.
- Se agrego `admin/pages/content.php` como pantalla de administracion del contenido por plantilla, con soporte inicial para campos simples y bloques repetibles fijos del `about`.
- Se creo el registro/schema de contenido para `about` y se conecto a helpers reutilizables en `includes/page-content.php`.
- `page.php` y `templates/pages/about.php` quedaron leyendo contenido dinamico desde BD con fallback a valores por defecto cuando no existe contenido guardado.
- Se agrego soporte para CTA con `Pagina interna` o `URL personalizada` en el bloque `Botones`, reutilizando `site_pages` y resolviendo la URL publica con `publicPageUrl()` sin romper compatibilidad con `*_cta_url` ya guardados.
- Se mejoro la UX de `admin/pages/content.php` con agrupacion visual por subbloques (`Texto introductorio`, `Botones`, `Im�genes`, `Certificaciones`) y labels mas claros para el administrador.
- Se agrego subida de imagenes en el editor de contenido para campos `image`, validando extension/MIME, guardando la ruta relativa en `assets/img/uploads/pages/` y preservando la ruta previa si no se sube un archivo nuevo.
- Se agrego preview inmediata en admin para imagenes usando `FileReader`, sin esperar a guardar el formulario.
- Se corrigieron varios detalles de render del admin para evitar duplicacion de titulos, toggles mal ubicados y previews vacios en campos no image.

### Archivos creados
- `admin/pages/content.php`
- `includes/page-content.php`
- `templates/page-schemas/registry.php`
- `templates/page-schemas/about.php`
- `database/site-page-content.sql`

### Archivos modificados
- `admin/pages/index.php`
- `admin/pages/edit.php`
- `admin/pages/content.php`
- `includes/page-content.php`
- `page.php`
- `templates/pages/about.php`
- `templates/page-schemas/about.php`

### Verificacion realizada
- Se ejecuto `php -l` sobre los archivos PHP creados o modificados en las iteraciones principales del dia.
- Los archivos validados quedaron sin errores de sintaxis en las comprobaciones ejecutadas.
- Se confirmo que el editor conserva la ruta previa de una imagen cuando no se selecciona un archivo nuevo.
- Se confirmo que la preview del admin puede actualizarse en navegador al elegir una nueva imagen antes de guardar.

### Pendientes recomendados
- Probar manualmente en navegador el flujo completo de contenido `about`: guardado, toggles, uploads y lectura en frontend.
- Revisar si conviene extraer helpers visuales del editor de contenido para reducir duplicacion antes de extender el sistema a otras plantillas.
- Verificar permisos de escritura del directorio `assets/img/uploads/pages/` en todos los entornos.
- Corregir textos heredados con codificacion rota que todavia aparezcan en algunas pantallas del admin.
- Antes de sumar nuevas plantillas, definir el criterio estable para bloques especiales como CTA internos/externos e imagenes con preview.

### Como retomar en una nueva sesion
- Indicar: "Revisa `SECURITY-NOTES.md` y continuemos desde la sesion 2026-03-16".
