# Estructura del código

La aplicación utiliza PHP 8 y MySQL. El punto de entrada web es `FKryzal/index.php`.

## Recorrido de una petición

1. `app/core.php` carga la configuración, prepara la sesión y reúne los módulos comunes.
2. `Controlador/cuenta.php` comprueba el acceso a la sección y valida los formularios POST.
3. Cada acción se delega en un archivo de `Controlador/acciones/` según su responsabilidad.
4. `Vista/espacio.php` compone la navegación y carga la vista de la sección solicitada.

Las páginas públicas usan `Vista/publica.php`. `install.php` prepara el primer acceso y `photo.php` sirve las fotos de perfil.

## Módulos comunes

| Archivo en `app/` | Responsabilidad |
| --- | --- |
| `conexion.php` | Conexión y ejecución de consultas |
| `database.php` | Instalación y vinculación de la base de datos |
| `seguridad.php` | CSRF, contraseñas, roles y límites de intentos |
| `sesion.php` | Identificación y vigencia de la sesión del usuario |
| `correo.php` | Recuperación de contraseña, contacto y envío de respuestas |
| `esquema.php` | Creación de tablas auxiliares compatibles con instalaciones existentes |
| `catalogos.php` | Especialidades y catálogo de recompensas |
| `puntuacion.php` | Cálculo de los puntos de clasificación |
| `clases.php` | Conservación de los filtros al volver a la agenda |
| `peticiones.php` | Conversaciones, estados de clases y avisos del espacio privado |
| `interfaz.php` | Escape de HTML, redirecciones, avisos y avatares |
| `formularios.php` | Campos y apertura de formularios con CSRF |
| `legacy.php` | Compatibilidad con las rutas y modelos anteriores |
| `demo.php` | Datos de demostración |

## Controladores y vistas

`Controlador/acciones/` separa acceso, perfil, reservas, clases, asistencia, recompensas, peticiones, correo y usuarios. Las comprobaciones específicas y las transacciones permanecen en la acción correspondiente. Los archivos de acciones se cargan desde el controlador después de validar el formulario.

`Vista/acceso/` contiene las pantallas de inicio de sesión, registro y recuperación. `Vista/espacio/` reúne las secciones privadas, incluido el perfil y la configuración de correo. `Modelo/` contiene las operaciones de los modelos y sus DAO.

`assets/workspace.css` reúne los ajustes de espaciado, filtros, listas y adaptación móvil del espacio privado.

`assets/app.js` gestiona los diálogos y controles de la interfaz. `assets/style.css` contiene el estilo del espacio privado, `site.css` el de las páginas públicas y `legacy.css` el de las vistas anteriores. El orden de las reglas CSS conserva las prioridades del diseño existente.

`sql/schema.sql` define las tablas y vistas de MySQL. `lib/` contiene PHPMailer, una dependencia externa que se conserva sin modificaciones.

## Instalador

`Instalador/instalar.ps1` prepara Wamp y copia la aplicación. `abrir.ps1` abre la web y `preparar_base.php` crea la base de datos. `launcher.c` es el código del lanzador de Windows.

## Convenciones

Se usan cuatro espacios de sangría, bloques de control explícitos y una instrucción por línea en la lógica PHP. Las funciones se agrupan por responsabilidad y las plantillas separan las etiquetas HTML de los bloques PHP. `.editorconfig` recoge las convenciones de edición.

### Comunicación

La entrada Mensajes de navegación agrupa `requests` (conversaciones internas) y `messages` (formulario público). `Vista/includes/mensajes_origen.php` permite cambiar de origen y muestra sus avisos por separado. Las rutas existentes se conservan para mantener los enlaces.

La autorización se aplica siempre con `ticket_scope` antes de añadir los filtros de bandeja, rol, estado o lectura. Recibidas excluye las conversaciones iniciadas por la propia cuenta; Enviadas las selecciona. El filtro de rol usa al otro participante según quién haya iniciado la conversación; el destinatario nulo representa la administración compartida. Los filtros solo reducen el ámbito autorizado.

`Vista/includes/cambiar_entrenador.php` comparte el formulario de reasignación entre tarjetas y ficha de clase. La acción conserva la comprobación de administrador y de sesión futura abierta, y permite regresar únicamente a las rutas locales previstas.

### Agenda y controles de consulta

El entrenador utiliza `classes` con `tab=active|pending|upcoming|archived`. El controlador adapta los enlaces anteriores `manage`, `myclasses` y `archive`; el archivo anterior siempre conserva el ámbito personal. `scope=all` muestra únicamente futuras sesiones generales. El predicado de consulta se comparte entre tarjetas y calendario. La paginación conserva los mismos parámetros.

Los controles de consulta comparten `.collection-toolbar`, `.collection-search` y `.compact-select`. Los selectores con `data-auto-submit` envían el formulario al cambiar de valor; la búsqueda se envía con Intro o su botón accesible. Sin JavaScript, el botón de búsqueda aplica todos los campos del formulario.
