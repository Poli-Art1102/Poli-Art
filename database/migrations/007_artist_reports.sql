USE poliart;

CREATE TABLE IF NOT EXISTS reporte_artista (
    id_reporte_artista INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    id_reportante INT(10) UNSIGNED NOT NULL,
    id_artista INT(10) UNSIGNED NOT NULL,
    motivo VARCHAR(120) NOT NULL,
    detalle TEXT DEFAULT NULL,
    estado ENUM('pendiente','ignorado','resuelto') NOT NULL DEFAULT 'pendiente',
    id_revisor INT(10) UNSIGNED DEFAULT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revisado_en DATETIME DEFAULT NULL,
    observaciones_revision TEXT DEFAULT NULL,
    PRIMARY KEY (id_reporte_artista),
    INDEX idx_reporte_artista_estado (estado, creado_en),
    FOREIGN KEY (id_reportante) REFERENCES usuario (id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_artista) REFERENCES artista (id_artista) ON DELETE CASCADE,
    FOREIGN KEY (id_revisor) REFERENCES usuario (id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
