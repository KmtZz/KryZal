CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('administrador', 'entrenador', 'usuario') NOT NULL DEFAULT 'usuario',
    points INT NOT NULL DEFAULT 0,
    active TINYINT NOT NULL DEFAULT 1,
    force_password TINYINT NOT NULL DEFAULT 0,
    auth_version INT NOT NULL DEFAULT 0,
    specialty VARCHAR(100) NOT NULL DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS classes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    starts_at DATETIME NOT NULL,
    trainer_id INT NOT NULL,
    capacity INT NOT NULL,
    cancelled TINYINT NOT NULL DEFAULT 0,
    FOREIGN KEY (trainer_id) REFERENCES users (id),
    UNIQUE (trainer_id, starts_at)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    class_id INT NOT NULL,
    attendance ENUM('asistio', 'no_asistio') NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (user_id, class_id),
    FOREIGN KEY (user_id) REFERENCES users (id),
    FOREIGN KEY (class_id) REFERENCES classes (id)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS resets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bucket CHAR(64) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (bucket, created_at)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS local_mail (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    subject VARCHAR(200) NOT NULL,
    body TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS app_state (
    setting VARCHAR(50) PRIMARY KEY,
    value VARCHAR(64) NOT NULL
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS profile_photos (
    user_id INT PRIMARY KEY,
    mime VARCHAR(30) NOT NULL,
    image MEDIUMBLOB NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS class_reviews (
    class_id INT PRIMARY KEY,
    reviewed_by INT NOT NULL,
    reviewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes (id),
    FOREIGN KEY (reviewed_by) REFERENCES users (id)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS redemptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    reward_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    cost INT NOT NULL,
    code CHAR(24) NOT NULL UNIQUE,
    request_key CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    used_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id),
    FOREIGN KEY (used_by) REFERENCES users (id),
    INDEX (user_id, created_at)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS point_adjustments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    admin_id INT NOT NULL,
    balance_delta BIGINT NOT NULL,
    score_delta BIGINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id),
    FOREIGN KEY (admin_id) REFERENCES users (id),
    INDEX (user_id)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS contact_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL,
    body TEXT NOT NULL,
    delivery ENUM('local', 'enviado', 'pendiente') NOT NULL DEFAULT 'local',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS contact_replies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    contact_id INT NOT NULL,
    admin_id INT NOT NULL,
    body TEXT NOT NULL,
    request_key CHAR(64) NOT NULL UNIQUE,
    delivery ENUM('local', 'enviando', 'enviado', 'fallido') NOT NULL DEFAULT 'local',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at DATETIME NULL,
    FOREIGN KEY (contact_id) REFERENCES contact_messages (id),
    FOREIGN KEY (admin_id) REFERENCES users (id),
    INDEX (contact_id, created_at)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    requester_id INT NOT NULL,
    recipient_id INT NULL,
    class_id INT NULL,
    subject VARCHAR(150) NOT NULL,
    status ENUM('abierta', 'cerrada') NOT NULL DEFAULT 'abierta',
    request_key CHAR(64) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (requester_id) REFERENCES users (id),
    FOREIGN KEY (recipient_id) REFERENCES users (id),
    FOREIGN KEY (class_id) REFERENCES classes (id),
    INDEX (recipient_id, status, updated_at),
    INDEX (requester_id, updated_at)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS ticket_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_id INT NOT NULL,
    author_id INT NOT NULL,
    body TEXT NOT NULL,
    request_key CHAR(64) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets (id),
    FOREIGN KEY (author_id) REFERENCES users (id),
    INDEX (ticket_id, id)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS ticket_reads (
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    last_message_id INT NOT NULL DEFAULT 0,
    PRIMARY KEY (ticket_id, user_id),
    FOREIGN KEY (ticket_id) REFERENCES tickets (id),
    FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS contact_reads (
    contact_id INT NOT NULL,
    admin_id INT NOT NULL,
    PRIMARY KEY (contact_id, admin_id),
    FOREIGN KEY (contact_id) REFERENCES contact_messages (id),
    FOREIGN KEY (admin_id) REFERENCES users (id)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS class_events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    class_id INT NOT NULL,
    actor_id INT NULL,
    event VARCHAR(30) NOT NULL,
    details TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes (id),
    FOREIGN KEY (actor_id) REFERENCES users (id),
    INDEX (class_id, created_at, id)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS booking_pins (
    booking_id INT PRIMARY KEY,
    FOREIGN KEY (booking_id) REFERENCES bookings (id) ON DELETE CASCADE
) ENGINE = InnoDB;