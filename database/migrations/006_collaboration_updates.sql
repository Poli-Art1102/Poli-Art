USE poliart;

CREATE TABLE IF NOT EXISTS avance_colaboracion (
    id_avance INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    id_proyecto INT(10) UNSIGNED NOT NULL,
    id_artista INT(10) UNSIGNED NOT NULL,
    descripcion VARCHAR(3000) DEFAULT NULL,
    progreso TINYINT(3) UNSIGNED DEFAULT NULL,
    ruta_imagen VARCHAR(255) DEFAULT NULL,
    nombre_imagen VARCHAR(255) DEFAULT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_avance),
    INDEX idx_avance_proyecto_fecha (id_proyecto, creado_en),
    CONSTRAINT fk_avance_proyecto FOREIGN KEY (id_proyecto)
        REFERENCES proyecto_colaborativo (id_proyecto) ON DELETE CASCADE,
    CONSTRAINT fk_avance_artista FOREIGN KEY (id_artista)
        REFERENCES artista (id_artista) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
