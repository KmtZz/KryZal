# KryZal

Aplicación de gestión de un gimnasio. Proyecto académico de DAW de Kimetz Andrés Elorza. Incluye web pública, cuentas de socios y entrenadores, reservas, asistencia, puntos y administración.

## Instalación desde cero

1. Extrae el ZIP completo en una carpeta fuera de `www`.
2. Abre `Instalar_KryZal.exe` y acepta los permisos de administrador.
3. El asistente detecta Wamp. Si está en otra ubicación, indica la carpeta que contiene `wampmanager.exe`. Si falta, descarga y prepara Visual C++ y Wamp.
4. El asistente inicia Wamp, espera a MySQL, sustituye `www/FKryzal` y crea la estructura de la base.
5. Crea el acceso directo **KryZal** en el escritorio, con el logotipo del proyecto, y abre `http://localhost/FKryzal/`.
6. Crea el administrador. Cargar demostración completa añade socios, entrenadores, administradores y actividad del gimnasio.

**Cada ejecución del instalador elimina los datos anteriores de KryZal.** Para abrir la web después de instalar, utiliza el acceso directo del escritorio. Este inicia Wamp si está cerrado y espera a que la web responda.

El asistente reutiliza los datos de conexión del proyecto instalado para reiniciar su base, pero restablece la configuración de correo. La base debe empezar por `kryzal` y estar en localhost. No elimina otras bases ni sitios de Wamp. Los registros de instalación se crean cuando se ejecuta el asistente y conservan la fecha real de la operación.

## Instalación manual

El ejecutable es opcional. Con Wamp u otro servidor compatible con PHP 8 y PDO MySQL:

1. Copia únicamente `FKryzal` a `www`.
2. En phpMyAdmin crea `kryzal_mejorado` con colación `utf8mb4_unicode_ci`.
3. Selecciona esa base e importa `FKryzal/sql/schema.sql`.
4. Revisa la conexión en `FKryzal/app/config.php`.
5. Abre `http://localhost/FKryzal/` y crea el administrador.

Debe existir `www/FKryzal/index.php`, sin duplicar la carpeta. La web también puede preparar las tablas durante la configuración inicial. La instalación manual no crea automáticamente el acceso directo.

## Requisitos

El asistente requiere Windows x64 e Internet cuando debe descargar Wamp y sus dependencias, aproximadamente 400 MB. Comprueba el SHA-256 de las descargas antes de ejecutarlas. Los enlaces del proyecto presuponen Apache en el puerto 80 y la carpeta `FKryzal`.

PHP 8.3 con PDO MySQL y MySQL 8 o MariaDB. La conexión predeterminada es `localhost:3306`, usuario `root`, contraseña vacía y base `kryzal_mejorado`. Los datos pueden cambiarse en `app/config.php`.

El ejecutable no está firmado digitalmente. La instalación de Wamp y Visual C++ requiere permisos de administrador. Las fuentes y sumas de comprobación están en `Documentacion/FUENTES.txt`.

## Primer acceso y demostración

Sin usuarios, la web solicita crear el administrador. Si ya existen, abre la portada y permite iniciar sesión. Volver a abrir la web no reinicia los datos.

| Cuenta | Email | Contraseña |
| --- | --- | --- |
| Administrador | El elegido durante la configuración | La elegida durante la configuración |
| Entrenador | maria.lopez@example.com | KryzalDemo!7 |
| Socio | luis.fernandez@example.com | KryzalDemo!7 |
| Socio | carlos.sanchez@example.com | KryzalDemo!7 |

Cargar demostración completa genera 150 socios, 8 entrenadores y 2 administradores adicionales. Si un correo coincide con el administrador inicial, no se duplica ni se cambia su cuenta. Sin marcar la casilla, solo se crea el administrador inicial.

La demostración incluye unas 800 clases: seis semanas de histórico y sesiones del día de instalación y de los siguientes 120 días. De lunes a sábado se programan varias sesiones al mismo tiempo con entrenadores distintos, sin duplicar horarios de un mismo entrenador. Hay reservas, aforos completos, clases pendientes, archivadas con asistencia y canceladas. Las cantidades exactas varían con el calendario y la selección aleatoria.

Cada inscripción corresponde a un socio generado. Las asistencias completadas suman 10 puntos y las faltas restan 10. Las canceladas no aplican puntos. El saldo parte de un ajuste inicial aleatorio registrado y después incorpora asistencias y canjes. El ranking conserva los puntos gastados en premios. Se incluyen códigos activos, utilizados y caducados.

También se generan 48 consultas, 36 respuestas y 24 mensajes de Recuperación de cuentas. Los estados de envío son simulados y los correos utilizan example.com. No se envía ningún mensaje externo durante la carga. Ocho enlaces de recuperación del buzón tienen 30 minutos de validez desde la instalación; el resto son ejemplos caducados.

Todas las cuentas generadas utilizan **KryzalDemo!7**. Además de las tres anteriores, se puede entrar como `admin.irati@example.com`, `admin.aitor@example.com`, `ander.garcia@example.com`, `nerea.martin@example.com`, `jon.ruiz@example.com`, `leire.alonso@example.com`, `unai.perez@example.com`, `ane.fernandez@example.com` o `iker.santos@example.com`. Los demás socios usan `socio001@example.com` a `socio148@example.com`. El administrador inicial conserva su propia contraseña.

## Acceso y perfil

Inicio, Quiénes somos y Contacto son públicos. La portada conserva las imágenes, instalaciones, servicios y testimonios del gimnasio. El logotipo y Página principal vuelven a ella. Cerrar sesión también vuelve a la portada.

Recordar usuario guarda únicamente el correo en este navegador durante 90 días tras un acceso correcto. No guarda la contraseña ni prolonga la sesión. Para olvidarlo, desmarca la casilla e inicia sesión correctamente.

Mi perfil es la primera opción del menú y también se abre al pulsar el nombre de la cuenta. Permite modificar el nombre y la contraseña. El entrenador puede cambiar su especialidad. Pulsa la foto o inicial para abrir la ventana de selección, comprobar la vista previa y guardar. Admite JPG, PNG o WebP de hasta 2 MB y 6000 píxeles por lado. La foto se guarda en MySQL asociada al usuario. Eliminarla recupera la inicial.

Las contraseñas nuevas requieren al menos 10 caracteres, mayúscula, minúscula, número y símbolo, con un máximo de 72 bytes. Las cuentas creadas por el administrador deben cambiar su contraseña temporal en el primer acceso. Las contraseñas anteriores siguen siendo válidas hasta que se cambien. El gestor de contraseñas del navegador puede ofrecer guardar o actualizar las credenciales.

## Socios

Resumen muestra el saldo para recompensas, próximas reservas y actividad. Clases permite buscar sesiones y alternar Tarjetas y Calendario. El buscador ignora tildes y mayúsculas. La búsqueda recorre todos los resultados y se conserva al cambiar de vista o de mes. El filtro Mostrar permite ir a las reservas propias.

Reservar plaza exige una clase futura, activa y con plazas. La aplicación impide reservas duplicadas y dos reservas activas del mismo socio a la misma fecha y hora. Una clase cancelada no bloquea ese horario. Mis clases separa Próximas y Archivadas. Las próximas reservas se ordenan desde la más cercana; el archivo muestra primero las más recientes, con +10 por asistencia, −10 por ausencia y 0 por cancelación. La cancelación requiere al menos 2 horas de antelación, también desde el calendario. Fuera de plazo se mantiene la reserva y puede contactarse con el entrenador. Reservar o cancelar conserva la sección y la vista actuales.

Clasificación muestra foto o inicial, puntuación y posición de los socios activos, con páginas de 25 resultados y sin límite de participantes. Ir a mi posición abre la página correspondiente y destaca la fila propia. Las posiciones primera, segunda y tercera usan distintivos discretos dorado, plateado y bronce. Los socios con la misma puntuación comparten posición. El siguiente puesto salta las posiciones ocupadas por el empate: 1, 1, 3. La ordenación por nombre e identificador solo organiza a las personas empatadas y permite calcular en qué página aparece cada una. Los bloques superiores separan puntuación, saldo para canjear y recompensas activas. En móvil se apilan.

Cada asistencia suma 10 y cada ausencia resta 10. La puntuación del ranking y el saldo de recompensas se llevan por separado: gastar puntos no reduce la posición por actividad. El saldo de la barra lateral, del Resumen y de Clasificación abre Recompensas.

El catálogo permite canjear premios cuando hay saldo suficiente. El canje descuenta puntos y genera un código de un solo uso válido durante 30 días. Mis recompensas separa Activas, Utilizadas y Caducadas. El icono Copiar junto al código activo lo guarda en el portapapeles. Una marca de verificación y el aviso verde «¡Código copiado!» confirman la acción durante unos segundos. La caducidad no devuelve puntos. El administrador valida el código al entregar el premio.

## Entrenadores

Clases reúne las sesiones asignadas y su gestión en una sola agenda. Nueva clase permite crear sesiones futuras con entre 1 y 200 plazas. El sistema impide fechas pasadas y horarios duplicados para un mismo entrenador. El selector de agenda permite consultar las próximas sesiones de todo el gimnasio.

Antes del inicio se pueden consultar inscritos, descargar el informe TXT y retirar un alumno con confirmación. Después del inicio, el entrenador completa Asistió o No asistió para todos los inscritos y pulsa Guardar y archivar. Al alcanzar la hora de inicio, una sesión sin alumnos se cancela automáticamente y pasa al archivo sin aplicar puntos.

Guardar aplica los puntos y mueve la sesión a Clases archivadas, en verde. Repetir el guardado no duplica los puntos. El resumen avisa de sesiones pendientes de asistencia. Las tarjetas de las clases asignadas al entrenador aparecen primero y se distinguen por un borde más ancho y una chincheta. Administración consulta las asistencias pendientes como información general, sin chincheta ni aviso de tarea personal, y conserva Gestionar asistencia para consultar o corregir el registro. El buscador de la agenda personal del entrenador busca por nombre de clase, sin un filtro de entrenador innecesario. Las canceladas se conservan en el archivo con sus inscripciones, sin penalizar a los socios. El archivo se abre desde Archivadas en Clases del entrenador o en Clases de administración; no tiene una entrada independiente en el menú lateral.

## Administración

Usuarios busca por nombre o email, sin distinguir tildes y mayúsculas, filtra por rol y pagina las cuentas de 12 en 12. El administrador puede crear cuentas y modificar nombre, email, rol, especialidad y activación. No puede dejar la aplicación sin un administrador activo ni quitarse sus propios permisos.

Cambiar un email conserva el identificador y las reservas, cierra las sesiones anteriores y anula enlaces de recuperación pendientes. Restablecer contraseña define una clave temporal que debe cambiarse en el primer acceso. Ninguna de estas acciones activa una cuenta deshabilitada.

Modificar puntos permite editar por separado el saldo para recompensas y la puntuación del ranking. Admite enteros de −1000000 a 1000000. El sistema rechaza un ajuste si los valores cambiaron mientras el formulario estaba abierto. Los ajustes registran administrador, socio, diferencias y fecha, y conservan canjes y asistencias.

Clases permite crear, cancelar y reasignar sesiones. Validar recompensas consulta titular, premio y caducidad antes de marcar un código como utilizado. Los códigos inexistentes, usados o caducados no se aceptan.

## Correo y contacto

Olvidaste tu contraseña genera un enlace de un solo uso válido durante 30 minutos. La respuesta no revela si existe una cuenta con ese email. Por defecto, el modo local guarda los mensajes sin enviar correos externos. El administrador consulta Recuperación de cuentas desde localhost.

Configuración permite seleccionar SMTP y definir servidor, puerto, cifrado, usuario, contraseña de aplicación, remitente y URL de la web. Para Gmail: `smtp.gmail.com`, puerto 587 y STARTTLS con una contraseña de aplicación.

Contacto guarda las consultas en Mensajes → Formulario web. Con SMTP configurado, intenta enviarlas al remitente indicado, con el email del visitante como dirección de respuesta. Si el envío falla, la consulta permanece guardada.

## Navegación

Las cabeceras permanecen visibles al desplazarse. En Mi espacio, una franja sólida del fondo oculta el contenido que pasa por detrás de la tarjeta blanca. En ventanas con menos de 500 píxeles de altura la cabecera deja de fijarse. En móvil, el menú público contiene la identidad de la cuenta y Cerrar sesión. Los botones de recompensas quedan alineados al pie de las tarjetas.

## Archivos del paquete

- `Instalar_KryZal.exe`: asistente de instalación.
- `FKryzal/`: aplicación PHP, vistas, estilos, JavaScript, imágenes, PHPMailer y esquema SQL.
- `Instalador/`: scripts, icono y código fuente del lanzador.
- `IMPORTANTE.txt`: guía rápida de instalación y uso diario.
- `Documentacion/GUIA_WAMP.txt`: instalación manual y problemas habituales.
- `Documentacion/Guia_de_uso_KryZal.pptx`: guía visual de uso.
- Los documentos DOCX de `Documentacion/` conservan la memoria académica y el manual de Wamp elaborados durante DAW.

## Mantenimiento

Para actualizar archivos sin borrar datos, conserva `app/config.php`, sustituye el resto de los archivos de la aplicación y recarga con Ctrl+F5. No ejecutes el instalador ni vuelvas a importar SQL sobre una base con datos. Las tablas auxiliares se preparan automáticamente.

Las fechas de clases, reservas, códigos y registros se calculan durante el uso. El proyecto académico corresponde a 2025. La guía visual utiliza datos de demostración.


## Informes de clases
Las clases archivadas con asistencia completada permiten descargar un informe TXT que indica cuándo se archivó la clase, la asistencia de cada alumno y los puntos aplicados. En las clases pendientes se descarga únicamente el listado de inscritos, identificado como pendiente y sin resultados de asistencia. Las canceladas indican la cancelación y conservan el listado de inscritos.


## Gestión de clases
Los controles permiten elegir próximas y pendientes o clases archivadas, cambiar entre tarjetas y calendario, y buscar sesiones. Nueva clase despliega el formulario de creación. Para reasignar una sesión futura, el administrador despliega Cambiar entrenador en Clases o la ficha de la sesión, selecciona uno y pulsa Guardar entrenador. Las reservas se conservan.


## Historial de recompensas

Validar recompensas incluye Validar código e Historial de códigos. Tras marcar una entrega como utilizada, el código permanece en pantalla con su estado, fecha y administrador que lo validó. El historial permite buscar por socio, correo, nombre del premio o código, filtrar por tipo de recompensa y estado, y consultar el titular y las fechas. Archivadas reúne utilizadas y caducadas. Los resultados se paginan de 12 en 12.

## Respuestas a consultas

Mensajes → Formulario web permite buscar por nombre, correo o mensaje y filtrar pendientes y respondidas por correo. Abrir consulta muestra la conversación. El administrador puede escribir y confirmar una respuesta. Con SMTP se envía al correo del visitante; en modo local solo se guarda, identificado expresamente como no enviado. Tras configurar SMTP se puede enviar una respuesta local. Un fallo conserva el texto para reintentar. Las respuestas enviadas no pueden enviarse de nuevo y las peticiones repetidas del mismo formulario no duplican respuestas.

El historial registra administrador, texto, fecha, estado y fecha de envío. Las consultas con respuestas únicamente locales siguen pendientes de envío. La tabla contact_replies se prepara automáticamente al abrir Mensajes → Formulario web y también está incluida en sql/schema.sql. Configuración es la última opción del menú de administración.


## Peticiones y comunicación

Los socios y el equipo disponen de peticiones internas con asunto, conversación y estados Abierta o Cerrada. Los participantes pueden responder, cerrar y reabrir la conversación. La lista se pagina de 12 en 12 y permite buscar por asunto, nombre, correo o contenido. Los mensajes no leídos se indican en el menú y en Resumen. Abrir una conversación marca como leídos los mensajes que contiene para esa cuenta.

Un socio puede abrir una petición desde Mensajes o desde el enlace de ayuda de Clasificación. Administración es el destinatario predeterminado, pero puede buscar y seleccionar un entrenador activo por nombre o especialidad, sin necesitar una reserva previa. En Clases, la foto o inicial y el nombre del entrenador abren su tarjeta con nombre y especialidad. Contactar con el entrenador abre una petición con ese entrenador seleccionado. Si el socio tiene una reserva, se conserva también la clase, fecha y hora de origen. Sin reserva, se trata de una consulta general sin una clase asociada. En el calendario está disponible la misma tarjeta.

El entrenador consulta las peticiones de sus alumnos y responde desde Mensajes, en Recibidas. Allí también puede abrir una consulta para Administración o seleccionar a otro entrenador activo. El administrador recibe las peticiones dirigidas a Administración, las que ha creado y las dirigidas a su propia cuenta. Puede iniciar conversaciones con socios y entrenadores activos, y filtrar las peticiones por el rol de origen. Los administradores no acceden a conversaciones privadas entre un alumno y su entrenador salvo que participen en ellas. Conocer un identificador no permite leer ni responder a peticiones ajenas.

La comunicación interna funciona sin SMTP y no se envía a una bandeja de email. El historial muestra el nombre y correo de los participantes. Las cuentas desactivadas no pueden iniciar sesión. No se comparten contraseñas ni enlaces de recuperación dentro de las peticiones.

El formulario público de Contacto sigue disponible para visitantes. Con una sesión iniciada, ofrece abrir una petición identificada desde la cuenta. Mensajes → Formulario web conserva las consultas recibidas sin sesión, con filtros de correo de cuenta registrada o visitante. Esa coincidencia de email no verifica la identidad del remitente. Las peticiones identificadas se consultan en la pestaña Internos. Las respuestas a visitantes se guardan localmente o se envían por SMTP según la configuración.

## Recuperación de cuentas y recuperación

Recuperación de cuentas es un registro de correos de recuperación simulados, accesible solo al administrador desde localhost en modo local. No es la bandeja de peticiones ni la del correo personal. Su buscador recorre nombres y emails y los resultados se paginan de 12 en 12. Cada ficha indica si el enlace sigue vigente, caducó o fue utilizado o sustituido.

Copiar enlace permite probar uno vigente en una ventana privada o sin sesión iniciada. Generar nuevo enlace anula los anteriores y crea otro válido durante 30 minutos, guardado localmente. No vuelve a enviar un token antiguo. Para enviar a una bandeja real se configura SMTP. Las solicitudes desde ¿Olvidaste tu contraseña? utilizan el modo de correo seleccionado. No se envían correos externos durante la carga de demostración.

La demostración añade 36 peticiones de socios y 8 del equipo, con respuestas y conversaciones abiertas y cerradas. Se garantiza una ausencia registrada para el primer socio de demostración si tiene una clase archivada, ajustando también sus puntos. Los administradores no se inscriben a clases: las reservas generadas pertenecen exclusivamente a socios.


## Listados y estados de las clases

Los listados paginados de clasificación, clases, cuentas, recompensas, consultas, peticiones y Recuperación de cuentas muestran la navegación encima y debajo de los resultados. Los filtros se mantienen al cambiar de página. La clasificación alinea posición, socio y puntos en columnas estables. Ir a mi posición abre la página donde aparece la cuenta aunque haya empates y destaca su fila sin desplazar las columnas.

Las tarjetas de clases futuras sin plazas muestran Aforo completo, fondo gris y un botón de reserva deshabilitado. En Mis clases del socio, tanto en tarjetas como en calendario, se ocultan «Reservada por ti», el aforo y las plazas disponibles. Las reservas futuras conservan el aspecto normal aunque la clase esté llena y pueden cancelarse dentro del plazo. El catálogo general mantiene los indicadores de disponibilidad. En el historial del socio, Asistió se muestra en verde y No asistió en rojo, tanto en tarjetas como en calendario. Las canceladas permanecen neutras y no aplican puntos. El registro de asistencia completada de los entrenadores y administradores describe el cierre de la clase, no el resultado individual de cada alumno.

En Nueva petición, Buscar destinatario filtra las cuentas disponibles sin distinguir tildes ni mayúsculas. Es necesario seleccionar la persona en Destinatario. Buscar otra persona no cambia una selección anterior. Los socios y entrenadores pueden dirigirse a Administración o a entrenadores activos distintos de sí mismos. El administrador puede seleccionar cualquier otra cuenta activa. Si se abre una petición desde una reserva, el destinatario queda vinculado al entrenador de esa clase; Elegir otro destinatario sin asociar una clase abre una consulta general.


## Avisos y clases de hoy

El menú muestra un punto junto a las secciones que requieren atención y el número de avisos: mensajes sin leer, asistencias pendientes del entrenador y próximas clases de hoy del socio o entrenador. Resumen agrupa esos avisos. Al leer una conversación se actualiza el contador. Completar una asistencia retira ese aviso. El recordatorio de una clase de hoy se mantiene hasta su inicio o cancelación.

En Mis clases del socio y en Clases del entrenador, la lista de días permite elegir Hoy. El filtro funciona en tarjetas y calendario, conserva la búsqueda y se puede quitar con Cualquier día. En administración también está disponible dentro de Clases. El resumen del socio y entrenador muestra el número de sesiones que todavía tienen hoy y un acceso a su agenda.

## Archivo y correcciones de asistencia

El archivo muestra primero la última clase archivada o cancelada, aunque su fecha de celebración sea anterior a otras. La tarjeta indica cuándo se archivó. En registros anteriores que no tenían guardada la fecha de cancelación, se utiliza la fecha de la sesión como referencia. La vista de calendario conserva las fechas de celebración.

Desde Ver registro, el administrador o el entrenador asignado puede Desarchivar clase si su asistencia estaba completada. La confirmación revierte exactamente los puntos anteriores, deja todas las asistencias pendientes y devuelve la sesión a gestión. Al volver a guardar se aplica el resultado corregido una sola vez. Si se habían gastado los puntos en recompensas, la corrección puede dejar un saldo negativo. Las recompensas ya emitidas se conservan. Un formulario antiguo no puede deshacer una revisión posterior.

La reapertura conserva un registro de quién la realizó, cuándo y la asistencia anterior. El historial de la clase muestra sus cambios de estado. Las clases canceladas no se reabren con este botón.

La cancelación automática sin inscritos se comprueba al abrir o utilizar la aplicación, a partir de la hora de inicio, nunca a medianoche. Sin visitas a la aplicación no se ejecutan tareas en segundo plano: la siguiente petición actualiza las sesiones que correspondan. Las clases con inscritos se mantienen para registrar la asistencia y las futuras siguen admitiendo reservas. La comprobación no duplica cancelaciones ni aplica puntos.

## Origen de las peticiones

Cada petición muestra el rol de quien la inició: Socio, Entrenador o Administrador. La distinción también aparece dentro de la conversación y junto a cada autor. Recibidas y Enviadas separan el sentido de las conversaciones. Los filtros de participante, estado y lectura se combinan con la búsqueda.

## Fijar reservas personales

Solo el socio, dentro de Mis clases, dispone de Fijar y Desfijar. La selección se guarda en su cuenta y funciona en Próximas y Archivadas. En tarjetas, las reservas fijadas aparecen antes de las demás, también al paginar; dentro de cada grupo se mantiene el orden de fechas. El calendario permite cambiar la selección sin mover las sesiones de su día. Las búsquedas y el filtro de hoy siguen limitando los resultados. Al cancelar o retirar una reserva se elimina su fijación; volver a reservar no la recupera. Esta selección es personal y no cambia la asistencia ni la agenda del entrenador.

## Peticiones sin resultados

Mensajes distingue entre no tener conversaciones abiertas o cerradas y una búsqueda sin coincidencias. Si no hay resultados, oculta el contador de páginas y muestra un mensaje dentro de un panel. Limpiar búsqueda conserva la pestaña actual.

## Organización del código

Consulta `Documentacion/ESTRUCTURA_CODIGO.md` para localizar los módulos, controladores y vistas. Las reservas fijadas del socio se distinguen por un borde dorado y una franja lateral, conservando el color de asistencia en el historial.

## Agenda, cuentas y fotos

En Clases, Por gestionar reúne próximas y pendientes. Completar asistencia y Gestionar alumnos las separan por tarea. Se combina con el día, la búsqueda y el calendario.

Usuarios permite ordenar por nombre en ambos sentidos o por fecha de inscripción, usando el campo `users.created_at` que ya forma parte del esquema. Las fichas de edición se despliegan desde la lista.

Las fotos de las cuentas activas están disponibles para otros usuarios con sesión iniciada. Las peticiones sin sesión siguen bloqueadas. Si una imagen no está disponible, el avatar conserva su inicial.

Los datos de demostración incluyen sesiones e inscripciones del día de instalación, además del historial y las sesiones posteriores.

## Bandejas de mensajes

Mensajes agrupa la correspondencia bajo una sola entrada. En administración aparece después de Validar recompensas. Internos reúne las conversaciones identificadas; Formulario web conserva las consultas enviadas sin sesión y sus respuestas por correo. Recuperación de cuentas mantiene separados los enlaces de restablecimiento.

Recibidas muestra conversaciones iniciadas por otra persona; Enviadas muestra las iniciadas por la cuenta actual. Responder no cambia la bandeja de origen. Todas combina ambas. Administración recibe también la bandeja compartida, sin acceder a conversaciones privadas ajenas. Los filtros rápidos se refieren al otro participante: en Enviadas filtran al destinatario, y en Recibidas al remitente. El estado, la búsqueda y Solo sin leer se pueden combinar y se conservan al paginar o cambiar de bandeja.

Los avisos de Recibidas y Enviadas muestran los mensajes sin leer del estado seleccionado. El aviso general de Mensajes reúne todas las conversaciones pendientes de lectura; en administración también incluye las consultas del formulario web.

Usuarios ofrece filtros rápidos Todos, Socios, Entrenadores y Administradores. Se combinan con la búsqueda y la ordenación por nombre o fecha de inscripción, disponible junto al buscador. El orden se aplica al seleccionarlo. Cambiar de rol vuelve a la primera página y conserva la búsqueda y el orden.

## Agenda unificada del entrenador

El menú del entrenador tiene una sola entrada Clases. Por gestionar reúne sus próximas sesiones y asistencias pendientes; Completar asistencia muestra las iniciadas sin revisar; Gestionar alumnos muestra las futuras abiertas; Archivadas reúne sus sesiones completadas y canceladas. Mis clases es la agenda inicial. Todo el gimnasio permite consultar las próximas sesiones generales, sin acceso a registros ajenos. Los enlaces anteriores a Mis clases o al archivo siguen funcionando.

La búsqueda, el día, la tarea y el ámbito se conservan en tarjetas, calendario y paginación. El archivo se ordena por la fecha de archivo o cancelación. Las fichas de asistencia del entrenador regresan a la tarea correspondiente dentro de Clases. En administración, Clases utiliza los mismos accesos por tarea.

Usuarios mantiene los filtros rápidos por rol y presenta búsqueda y ordenación en una sola fila. Intro o la lupa ejecutan la búsqueda; cambiar el orden lo aplica directamente. La selección del rol conserva búsqueda y orden y vuelve a la primera página.


Clases reúne también la agenda de administración en una sola sección. Los enlaces antiguos a gestión o archivo abren la pestaña correspondiente. En Mensajes > Formulario web, las pestañas separan todas las consultas, pendientes de envío y respondidas por correo. La búsqueda funciona con Intro o la lupa; los filtros de correo y lectura se aplican al seleccionarlos.


Las consultas vinculadas a una clase incluyen un acceso directo. Administración y el entrenador asignado abren su ficha con el socio de la consulta resaltado y su situación de asistencia. El socio abre solo su reserva. Si cambia el entrenador o desaparece la inscripción, el enlace respeta los permisos actuales. La ficha permite volver a la conversación. El pie público enlaza con LinkedIn y GitHub de Kimetz Andrés Elorza.

Los avisos de reserva se pueden cerrar y desaparecen a los cinco segundos. El temporizador se pausa al situar el ratón sobre el aviso o al darle el foco con el teclado.

Si otra reserva ocupa la misma fecha y hora, el aviso ofrece Ver mi reserva para abrir la sesión que provoca el conflicto.
