USE poliart;

CREATE TABLE IF NOT EXISTS notificacion (
    id_notificacion INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    id_destinatario INT(10) UNSIGNED NOT NULL,
    id_actor INT(10) UNSIGNED DEFAULT NULL,
    id_obra INT(10) UNSIGNED DEFAULT NULL,
    tipo VARCHAR(40) NOT NULL,
    titulo VARCHAR(160) NOT NULL,
    mensaje TEXT NOT NULL,
    enlace VARCHAR(255) NOT NULL DEFAULT '/poliart/app/artista/notificaciones.php',
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    leido_en DATETIME DEFAULT NULL,
    PRIMARY KEY (id_notificacion),
    INDEX idx_notificacion_destinatario (id_destinatario, leido_en, creado_en),
    CONSTRAINT fk_notificacion_destinatario FOREIGN KEY (id_destinatario)
        REFERENCES usuario (id_usuario) ON DELETE CASCADE,
    CONSTRAINT fk_notificacion_actor FOREIGN KEY (id_actor)
        REFERENCES usuario (id_usuario) ON DELETE SET NULL,
    CONSTRAINT fk_notificacion_obra FOREIGN KEY (id_obra)
        REFERENCES obra (id_obra) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
