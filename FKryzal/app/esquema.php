<?php
declare(strict_types=1);

function ensure_contacts(): void
{
    query(
        "CREATE TABLE IF NOT EXISTS contact_messages (id INT PRIMARY KEY AUTO_INCREMENT,name VARCHAR(100) NOT NULL,email VARCHAR(190) NOT NULL,body TEXT NOT NULL,delivery ENUM('local','enviado','pendiente') NOT NULL DEFAULT 'local',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB",
    );
}

function ensure_photos(): void
{
    query(
        'CREATE TABLE IF NOT EXISTS profile_photos(user_id INT PRIMARY KEY,mime VARCHAR(30) NOT NULL,image MEDIUMBLOB NOT NULL,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB',
    );
}

function ensure_features(): void
{
    query(
        'CREATE TABLE IF NOT EXISTS booking_pins(booking_id INT PRIMARY KEY,FOREIGN KEY(booking_id) REFERENCES bookings(id) ON DELETE CASCADE) ENGINE=InnoDB',
    );
    query(
        'CREATE TABLE IF NOT EXISTS class_events(id INT PRIMARY KEY AUTO_INCREMENT,class_id INT NOT NULL,actor_id INT NULL,event VARCHAR(30) NOT NULL,details TEXT NOT NULL,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(class_id) REFERENCES classes(id),FOREIGN KEY(actor_id) REFERENCES users(id),INDEX(class_id,created_at,id)) ENGINE=InnoDB',
    );
    query(
        'CREATE TABLE IF NOT EXISTS point_adjustments(id INT PRIMARY KEY AUTO_INCREMENT,user_id INT NOT NULL,admin_id INT NOT NULL,balance_delta BIGINT NOT NULL,score_delta BIGINT NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(user_id) REFERENCES users(id),FOREIGN KEY(admin_id) REFERENCES users(id),INDEX(user_id)) ENGINE=InnoDB',
    );
    query(
        'CREATE TABLE IF NOT EXISTS class_reviews(class_id INT PRIMARY KEY,reviewed_by INT NOT NULL,reviewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(class_id) REFERENCES classes(id),FOREIGN KEY(reviewed_by) REFERENCES users(id)) ENGINE=InnoDB',
    );
    query(
        'CREATE TABLE IF NOT EXISTS redemptions(id INT PRIMARY KEY AUTO_INCREMENT,user_id INT NOT NULL,reward_id INT NOT NULL,title VARCHAR(150) NOT NULL,cost INT NOT NULL,code CHAR(24) NOT NULL UNIQUE,request_key CHAR(64) NOT NULL UNIQUE,expires_at DATETIME NOT NULL,used_at DATETIME NULL,used_by INT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(user_id) REFERENCES users(id),FOREIGN KEY(used_by) REFERENCES users(id),INDEX(user_id,created_at)) ENGINE=InnoDB',
    );
}

function ensure_contact_replies(): void
{
    ensure_contacts();
    query(
        "CREATE TABLE IF NOT EXISTS contact_replies(id INT PRIMARY KEY AUTO_INCREMENT,contact_id INT NOT NULL,admin_id INT NOT NULL,body TEXT NOT NULL,request_key CHAR(64) NOT NULL UNIQUE,delivery ENUM('local','enviando','enviado','fallido') NOT NULL DEFAULT 'local',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,sent_at DATETIME NULL,FOREIGN KEY(contact_id) REFERENCES contact_messages(id),FOREIGN KEY(admin_id) REFERENCES users(id),INDEX(contact_id,created_at)) ENGINE=InnoDB",
    );
}
