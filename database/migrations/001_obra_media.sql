CREATE TABLE IF NOT EXISTS obra_media (
    id_media INT AUTO_INCREMENT PRIMARY KEY,
    id_obra INT(10) UNSIGNED NOT NULL,
    ruta VARCHAR(255) NOT NULL,
    tipo_mime VARCHAR(100) NOT NULL,
    orden SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_obra_media_obra_orden (id_obra, orden),
    CONSTRAINT fk_obra_media_obra FOREIGN KEY (id_obra)
        REFERENCES obra(id_obra) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
