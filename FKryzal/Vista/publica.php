<?php
$page = $_GET['page'] ?? 'home';
$error = '';
$notice = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'contact') {
    try {
        validate_csrf();
        if ($me) {
            flash('Envía tu consulta desde Mensajes para conservar la conversación.');
            go('requests&new=1');
        }
        if (!has_users()) {
            throw new RuntimeException(
                'Completa la instalación del gimnasio antes de enviar mensajes.',
            );
        }
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $body = trim($_POST['body'] ?? '');
        if (
            !$name ||
            strlen($name) > 100 ||
            strlen($email) > 190 ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            strlen($body) < 10 ||
            strlen($body) > 5000
        ) {
            throw new RuntimeException(
                'Introduce un nombre, un email válido y un mensaje de entre 10 y 5000 caracteres.',
            );
        }
        $pdo = db();
        throttle('contact', $_SERVER['REMOTE_ADDR'] ?? '', 5);
        ensure_contacts();
        query('INSERT INTO contact_messages(name,email,body) VALUES(?,?,?)', [
            $name,
            $email,
            $body,
        ]);
        $id = (int) $pdo->lastInsertId();
        if ($config['mail']['mode'] === 'smtp') {
            try {
                mail_contact($name, $email, $body);
                query("UPDATE contact_messages SET delivery='enviado' WHERE id=?", [$id]);
            } catch (Throwable $e) {
                query("UPDATE contact_messages SET delivery='pendiente' WHERE id=?", [$id]);
                error_log('KryZal contacto SMTP: ' . $e->getMessage());
            };
        }
        flash('Tu mensaje ha quedado registrado. Gracias por contactar con KryZal.');
        go('contact');
    } catch (Throwable $e) {
        $error =
            $e instanceof PDOException
                ? 'No se pudo guardar el mensaje. Comprueba que Wamp esté iniciado y la instalación completada.'
                : $e->getMessage();
    };
}
if ($me) {
    ensure_photos();
}
?>
<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title><?= h(['home' => 'Inicio', 'about' => 'Quiénes somos', 'contact' => 'Contacto'][$page]) ?> · KryZal</title>
        <link rel="icon" href="assets/images/ico.png?v=2">
        <link rel="stylesheet" href="assets/style.css">
        <link rel="stylesheet" href="assets/site.css">
        <script src="assets/app.js" defer></script>
    </head>
    <body class="site">
        <header class="site-header">
            <a class="brand" href="?page=home">
                <img class="brand-icon" src="assets/images/logo-claro.svg" alt=""> KryZal
            </a>
            <button type="button" class="menu-toggle" aria-label="Abrir navegación" aria-expanded="false"
                data-menu>Menú</button>
            <nav class="site-nav" aria-label="Navegación principal">
                <a class="<?= $page === 'home' ? 'selected' : '' ?>" href="?page=home">Inicio</a>
                <a class="<?= $page === 'about' ? 'selected' : '' ?>" href="?page=about">Quiénes somos</a>
                <a class="<?= $page === 'contact' ? 'selected' : '' ?>" href="?page=contact">Contacto</a>
                <?php if (!$me): ?>
                <a class="button" href="?page=login">Iniciar sesión</a>
                <?php endif; ?>

                <?php if ($me !== null): ?>
                <a class="account-link" href="?page=profile" aria-label="Editar perfil de <?= h($me['name']) ?>">
                    <?php avatar($me, 'avatar-small'); ?>
                    <span>
                        <strong><?= h($me['name']) ?></strong>
                        <small><?= h($me['role'] === 'usuario' ? 'Socio' : ucfirst($me['role'])) ?></small>
                    </span>
                </a>
                <form class="public-logout" method="post" action="index.php?page=login"><?= csrf() ?>
                    <input type="hidden" name="action" value="logout">
                    <button class="logout-button">Cerrar sesión</button>
                </form>
                <?php endif; ?>
            </nav>
        </header>
        <main>

            <?php require __DIR__ . '/includes/aviso.php'; ?>

            <?php if ($page === 'home'): ?>
            <section class="site-hero" aria-label="Presentación del gimnasio">
                <div class="hero-images" data-gallery>
                    <img src="assets/images/1.jpg" alt="Entrenamiento funcional en el gimnasio">
                    <img src="assets/images/2.jpg" alt="Entrenamiento con cuerdas de resistencia" hidden>
                    <img src="assets/images/115.jpg" alt="Preparación para una sesión de entrenamiento"
                        hidden>
                </div>
                <div class="hero-shade"></div>
                <div class="hero-copy">
                    <span class="eyebrow">GIMNASIO KRYZAL</span>
                    <h1>Cada entrenamiento
                        <br>cuenta.
                    </h1>
                    <p>Un espacio para moverte, superarte y compartir.
                        <br>Encuentra tu ritmo. Nosotros te acompañamos.
                    </p>
                    <div class="hero-actions">
                        <?php if ($me): ?>
                        <a class="button" href="?page=dashboard">Ir a mi espacio</a>
                        <?php else: ?>
                        <a class="button" href="?page=register">Empieza con nosotros</a>
                        <?php endif; ?>
                        <a class="button outline" href="#instalaciones">Conoce el gimnasio</a>
                    </div>
                </div>
                <div class="gallery-controls">
                    <button type="button" data-slide="-1" aria-label="Imagen anterior">←</button>
                    <span data-slide-count>01 / 03</span>
                    <button type="button" data-slide="1" aria-label="Imagen siguiente">→</button>
                </div>
                <div class="hero-label">FUERZA · BIENESTAR · COMUNIDAD</div>
            </section>
            <div class="site-strip">
                <span>Entrenamiento a tu medida</span>
                <span>Clases para compartir</span>
                <span>Un objetivo, tu bienestar</span>
            </div>
            <section class="site-section">
                <div class="section-intro">
                    <span class="eyebrow">MUÉVETE A TU MANERA</span>
                    <h2>Todo lo que necesitas
                        <br>para dar el siguiente paso.
                    </h2>
                    <p>Zonas de cardio, fuerza y peso libre para todos los niveles.</p>
                </div>
                <div class="photo-grid">
                    <?php foreach (
                    [
                        ['16.jpg', 'Máquinas de cardio', 'Cintas de correr, elípticas y bicicletas estáticas.'],
                        ['3.jpg', 'Zona de pesas', 'Mancuernas, barras y resistencia para todos los niveles.'],
                        ['14.jpg', 'Máquinas de fuerza', 'Entrenamiento guiado para trabajar cada grupo muscular.'],
                    ]
                    as [$image, $title, $text]
                    ): ?>
                    <article class="photo-card">
                        <img src="assets/images/<?= h($image) ?>" alt="<?= h($title) ?>" loading="lazy">
                        <div>
                            <h3><?= h($title) ?></h3>
                            <p><?= h($text) ?></p>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </section>
            <section class="site-section facilities" id="instalaciones">
                <img src="assets/images/12.jpg" alt="Interior y equipamiento del gimnasio KryZal"
                    loading="lazy">
                <div>
                    <span class="eyebrow">UN ESPACIO PARA TI</span>
                    <h2>Nuestras instalaciones.</h2>
                    <p>Áreas de pesas, cardio y salas de clases grupales para entrenar a tu ritmo.</p>
                    <ul class="facility-list">
                        <?php foreach (
                        [
                            'Entrenamiento funcional',
                            'Pesas libres y máquinas',
                            'Clases dirigidas',
                            'Vestidores y duchas',
                            'Zona de recuperación con sauna',
                            'Cardio con cintas y bicicletas',
                            'Estiramiento y movilidad',
                            'Entrenamiento HIIT',
                            'Calistenia y street workout',
                            'Entrenamiento en suspensión (TRX)',
                        ]
                        as $item
                        ): ?>
                        <li><?= h($item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="?page=about">Conoce más sobre KryZal →</a>
                </div>
            </section>
            <section class="points-section">
                <div class="site-section">
                    <span class="eyebrow">LA CONSTANCIA TIENE SU PREMIO</span>
                    <h2>Entrena. Suma. Avanza.</h2>
                    <p>Tu asistencia suma puntos y te acerca a los objetivos del club.</p>
                    <div class="steps-grid">
                        <article>
                            <span>01</span>
                            <h3>Reserva tu clase</h3>
                            <p>Consulta las sesiones y elige la que mejor encaje contigo.</p>
                        </article>
                        <article>
                            <span>02</span>
                            <h3>Gana puntos</h3>
                            <p>Cada asistencia registrada suma 10 puntos. Una ausencia registrada resta 10.
                            </p>
                        </article>
                        <article>
                            <span>03</span>
                            <h3>Descubre tus recompensas</h3>
                            <p>Consulta el catálogo de descuentos y premios desde tu cuenta.</p>
                        </article>
                    </div>
                    <a class="button" href="?page=rewards">Ver mis recompensas</a>
                </div>
            </section>

            <?php elseif ($page === 'about'): ?>
            <section class="page-banner">
                <img src="assets/images/9.jpg" alt="Entrenamiento de fuerza en KryZal">
                <div>
                    <span class="eyebrow">MÁS QUE ENTRENAR</span>
                    <h1>Quiénes somos.</h1>
                    <p>Un gimnasio. Una comunidad. Tu siguiente paso.</p>
                </div>
            </section>
            <section class="site-section">
                <div class="section-intro">
                    <span class="eyebrow">NUESTRA FILOSOFÍA</span>
                    <h2>Tu transformación
                        <br>empieza contigo.
                    </h2>
                    <p>En Gimnasio KryZal creemos en la transformación integral de cada persona. Con más de 10
                        años de experiencia, ofrecemos un ambiente acogedor, instalaciones de vanguardia y un
                        equipo de profesionales dedicados a ayudarte a alcanzar tus objetivos de bienestar y
                        salud.</p>
                </div>
                <div class="about-grid">
                    <figure>
                        <img src="assets/images/9.jpg" alt="Zona de entrenamiento de fuerza" loading="lazy">
                        <figcaption>Instalaciones modernas y equipadas para todas tus necesidades.
                        </figcaption>
                    </figure>
                    <figure>
                        <img src="assets/images/7.jpg" alt="Comunidad del gimnasio compartiendo una pausa"
                            loading="lazy">
                        <figcaption>Un entorno cercano para compartir tus objetivos.</figcaption>
                    </figure>
                </div>
            </section>
            <section class="site-section services">
                <div class="section-intro">
                    <span class="eyebrow">TE ACOMPAÑAMOS</span>
                    <h2>Nuestros servicios.</h2>
                </div>
                <div class="photo-grid">
                    <?php foreach (
                    [
                        [
                            '5.jpg',
                            'Entrenamiento personalizado',
                            'Adaptamos cada rutina a tus necesidades. Sesiones individuales para centrarnos en tus metas.',
                        ],
                        [
                            '13.jpg',
                            'Clases grupales',
                            'Desde yoga hasta HIIT, encuentra la clase que más te motive y comparte las ganas de superarte.',
                        ],
                        [
                            '8.jpg',
                            'Nutrición y bienestar',
                            'Alimentación equilibrada, hábitos saludables y seguimiento para acompañar tu entrenamiento.',
                        ],
                    ]
                    as [$image, $title, $text]
                    ): ?>
                    <article class="photo-card">
                        <img src="assets/images/<?= h($image) ?>" alt="<?= h($title) ?>" loading="lazy">
                        <div>
                            <h3><?= h($title) ?></h3>
                            <p><?= h($text) ?></p>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </section>
            <section class="testimonials">
                <div class="site-section">
                    <span class="eyebrow">LAS VOCES DE KRYZAL</span>
                    <h2>Testimonios.</h2>
                    <div class="quote-grid">
                        <?php foreach (
                        [
                            [
                                'Ana M.',
                                'Gimnasio KryZal cambió mi vida. No solo me siento mejor físicamente, sino también mentalmente.',
                            ],
                            [
                                'Luis R.',
                                'El equipo es increíblemente motivador y profesional. Aquí encontré mi segunda familia.',
                            ],
                            [
                                'María S.',
                                '¡Las clases grupales son mi parte favorita del día! Energía y buena vibra garantizadas.',
                            ],
                        ]
                        as [$author, $quote]
                        ): ?>
                        <blockquote>
                            <p>“<?= h($quote) ?>”</p>
                            <cite><?= h($author) ?></cite>
                        </blockquote>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <?php else: ?>
            <section class="page-banner contact-banner">
                <img src="assets/images/1222.jpg" alt="Zona de cardio del gimnasio">
                <div>
                    <span class="eyebrow">HABLEMOS</span>
                    <h1>Contacto.</h1>
                    <p>Estamos aquí para acompañarte.</p>
                </div>
            </section>
            <section class="site-section contact-grid">
                <div>
                    <span class="eyebrow">GIMNASIO KRYZAL</span>
                    <h2>Tu próximo paso
                        <br>empieza aquí.
                    </h2>
                    <p>Pregúntanos por las clases, las instalaciones o tu cuenta.</p>
                    <dl class="contact-details">
                        <dt>Dirección</dt>
                        <dd>Calle Fit 123, Ciudad Saludable</dd>
                        <dt>Teléfono</dt>
                        <dd>+34 123 456 789</dd>
                        <dt>Email</dt>
                        <dd>contacto@gimnasiokryzal.com</dd>
                        <dt>Horario</dt>
                        <dd>Lunes a viernes: 06:00–22:00
                            <br>Sábado y domingo: 08:00–18:00
                        </dd>
                    </dl>
                </div>
                <div class="contact-form">
                    <h2>Envíanos un mensaje.</h2>
                    <?php if ($me): ?>
                    <p>Envía tu consulta desde tu cuenta para consultar las respuestas y continuar la
                        conversación.</p>
                    <a class="button" href="?page=requests&new=1">Contactar con Administración</a>
                    <?php else: ?>

                    <?php if ($error): ?>
                    <div class="notice error" role="alert"><?= h($error) ?></div>
                    <?php endif; ?>
                    <form method="post"><?= csrf() ?><label>Nombre
                            <input name="name" maxlength="100" autocomplete="name" value="<?= h($_POST['name'] ?? '') ?>"
                                required>
                        </label>
                        <label>Email
                            <input name="email" type="email" maxlength="190" autocomplete="email"
                                value="<?= h($_POST['email'] ?? '') ?>" required>
                        </label>
                        <label>Mensaje
                            <textarea name="body" rows="6" minlength="10" maxlength="5000"
                                required><?= h($_POST['body'] ?? '') ?></textarea>
                        </label>
                        <button>Enviar mensaje</button>
                    </form>
                    <?php endif; ?>
                </div>
            </section>
            <?php endif; ?>

        </main>
        <footer class="site-footer">
            <div>
                <a class="brand" href="?page=home">
                    <img class="brand-icon" src="assets/images/logo-claro.svg" alt=""> KryZal
                </a>
                <p>Tu plataforma de entrenamiento
                    <br>y gestión deportiva.
                </p>
            </div>
            <nav aria-label="Enlaces del pie">
                <a href="?page=home">Inicio</a>
                <a href="?page=about">Quiénes somos</a>
                <a href="?page=contact">Contacto</a>
            </nav>
            <div>
                <strong>Tu espacio, tu ritmo.</strong>
                <p>
                    <a href="?page=<?= $me ? 'dashboard' : 'register' ?>"><?= $me ? 'Ir a mi espacio →' : 'Únete a KryZal →' ?></a>
                </p>
            </div>
            <div class="footer-bottom">
                <small>© <?= date('Y') ?> KryZal</small>
                <div class="creator-links" aria-label="Perfiles de Kimetz Andrés Elorza">
                    <span>Kimetz Andrés Elorza</span>
                    <a href="https://www.linkedin.com/in/kimetz-andres-elorza/" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn de Kimetz Andrés Elorza (abre en otra pestaña)" title="LinkedIn">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3a2 2 0 1 1 0 4 2 2 0 0 1 0-4ZM3.5 9h3v12h-3ZM9 9h3v1.6c.7-1.1 1.8-1.9 3.6-1.9 3.1 0 4.4 1.9 4.4 5.3v7h-3v-6.5c0-1.9-.6-2.9-2.1-2.9-1.7 0-2.9 1.2-2.9 3.1V21H9Z"/></svg>
                    </a>
                    <a href="https://github.com/KmtZz" target="_blank" rel="noopener noreferrer" aria-label="GitHub de Kimetz Andrés Elorza (abre en otra pestaña)" title="GitHub">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 .8a11.2 11.2 0 0 0-3.54 21.83c.56.1.77-.24.77-.54v-2.1c-3.13.68-3.79-1.33-3.79-1.33-.51-1.3-1.25-1.65-1.25-1.65-1.02-.7.08-.69.08-.69 1.13.08 1.72 1.16 1.72 1.16 1 .0 1.73 1.25 3.28.89.1-.72.39-1.22.71-1.5-2.5-.28-5.13-1.25-5.13-5.55 0-1.23.44-2.23 1.16-3.02-.12-.28-.5-1.43.11-2.98 0 0 .95-.3 3.08 1.15a10.8 10.8 0 0 1 5.6 0c2.13-1.45 3.08-1.15 3.08-1.15.61 1.55.23 2.7.11 2.98.72.79 1.16 1.79 1.16 3.02 0 4.31-2.63 5.27-5.14 5.55.4.35.76 1.03.76 2.08v3.14c0 .3.21.65.77.54A11.2 11.2 0 0 0 12 .8Z"/></svg>
                    </a>
                </div>
            </div>
        </footer>
    </body>
</html>
