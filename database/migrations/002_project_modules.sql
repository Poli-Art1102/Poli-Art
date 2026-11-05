USE poliart;

ALTER TABLE obra ADD COLUMN IF NOT EXISTS permite_colaboracion TINYINT(1) NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS mensaje (
    id_mensaje  INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    id_emisor   INT(10) UNSIGNED NOT NULL,
    id_receptor INT(10) UNSIGNED NOT NULL,
    contenido   TEXT NOT NULL,
    leido_en    DATETIME DEFAULT NULL,
    creado_en   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_mensaje),
    INDEX idx_mensaje_conversacion (id_emisor, id_receptor, creado_en),
    INDEX idx_mensaje_receptor (id_receptor, leido_en),
    CONSTRAINT fk_mensaje_emisor FOREIGN KEY (id_emisor) REFERENCES usuario (id_usuario) ON DELETE CASCADE,
    CONSTRAINT fk_mensaje_receptor FOREIGN KEY (id_receptor) REFERENCES usuario (id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS borrador_obra (
    id_borrador  INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    id_artista   INT(10) UNSIGNED NOT NULL,
    id_categoria SMALLINT(5) UNSIGNED DEFAULT NULL,
    titulo       VARCHAR(160) NOT NULL,
    descripcion  TEXT DEFAULT NULL,
    ruta_archivo VARCHAR(255) DEFAULT NULL,
    progreso     TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
    creado_en    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_borrador),
    CONSTRAINT fk_borrador_artista FOREIGN KEY (id_artista) REFERENCES artista (id_artista) ON DELETE CASCADE,
    CONSTRAINT fk_borrador_categoria FOREIGN KEY (id_categoria) REFERENCES categoria_obra (id_categoria) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS obra_visita (
    id_visita INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    id_obra   INT(10) UNSIGNED NOT NULL,
    id_usuario INT(10) UNSIGNED DEFAULT NULL,
    visitado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_visita),
    INDEX idx_obra_visita_fecha (id_obra, visitado_en),
    CONSTRAINT fk_obra_visita_obra FOREIGN KEY (id_obra) REFERENCES obra (id_obra) ON DELETE CASCADE,
    CONSTRAINT fk_obra_visita_usuario FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS administrador_log (
    id_log      INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    id_admin    INT(10) UNSIGNED NOT NULL,
    accion      VARCHAR(100) NOT NULL,
    entidad     VARCHAR(100) DEFAULT NULL,
    id_entidad  INT(10) UNSIGNED DEFAULT NULL,
    descripcion TEXT DEFAULT NULL,
    creado_en   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_log),
    INDEX idx_administrador_log_fecha (creado_en),
    CONSTRAINT fk_administrador_log_admin FOREIGN KEY (id_admin) REFERENCES usuario (id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS anuncio_global (
    id_anuncio INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    id_admin INT(10) UNSIGNED NOT NULL,
    titulo VARCHAR(160) NOT NULL,
    mensaje TEXT NOT NULL,
    audiencia ENUM('todos','artistas','clientes') NOT NULL DEFAULT 'todos',
    tipo ENUM('informativo','importante','mantenimiento') NOT NULL DEFAULT 'informativo',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_anuncio),
    INDEX idx_anuncio_global_fecha (activo, creado_en),
    CONSTRAINT fk_anuncio_global_admin FOREIGN KEY (id_admin) REFERENCES usuario (id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE anuncio_global ADD COLUMN IF NOT EXISTS contenido TEXT DEFAULT NULL;
ALTER TABLE anuncio_global ADD COLUMN IF NOT EXISTS mensaje TEXT DEFAULT NULL;
ALTER TABLE anuncio_global ADD COLUMN IF NOT EXISTS audiencia ENUM('todos','artistas','clientes') NOT NULL DEFAULT 'todos';
ALTER TABLE anuncio_global ADD COLUMN IF NOT EXISTS tipo ENUM('informativo','importante','mantenimiento') NOT NULL DEFAULT 'informativo';
ALTER TABLE anuncio_global ADD COLUMN IF NOT EXISTS expira_en DATETIME DEFAULT NULL;
UPDATE anuncio_global SET mensaje = COALESCE(mensaje, contenido, '') WHERE mensaje IS NULL;
ALTER TABLE anuncio_global MODIFY mensaje TEXT NOT NULL;
ALTER TABLE anuncio_global MODIFY contenido TEXT DEFAULT NULL;
