-- ============================================================
-- POLIART - Schema completo MariaDB / MySQL
-- Base de datos: poliart
-- Versión: 1.0  |  PA2026
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP DATABASE IF EXISTS poliart;
CREATE DATABASE poliart
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE poliart;

-- -----------------------------------------------------------
-- 1. zona
-- -----------------------------------------------------------
CREATE TABLE zona (
    id_zona      SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre       VARCHAR(130)         NOT NULL,
    tipo         ENUM('comuna','corregimiento') NOT NULL DEFAULT 'comuna',
    PRIMARY KEY (id_zona)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 2. categoria_obra
-- -----------------------------------------------------------
CREATE TABLE categoria_obra (
    id_categoria        SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    id_categoria_padre  SMALLINT(5) UNSIGNED DEFAULT NULL,
    nombre              VARCHAR(100) NOT NULL,
    slug                VARCHAR(100) NOT NULL UNIQUE,
    descripcion         VARCHAR(255) DEFAULT NULL,
    activa              TINYINT(1)   NOT NULL DEFAULT 1,
    creado_en           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_categoria),
    FOREIGN KEY (id_categoria_padre) REFERENCES categoria_obra (id_categoria) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 3. configuracion_sistema
-- -----------------------------------------------------------
CREATE TABLE configuracion_sistema (
    id_configuracion  TINYINT(3) UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre_plataforma VARCHAR(100)        NOT NULL DEFAULT 'Poli-Art',
    porcentaje_comision DECIMAL(5,2)      NOT NULL DEFAULT 10.00,
    correo_soporte    VARCHAR(254)        DEFAULT NULL,
    actualizado_por   INT(10) UNSIGNED    DEFAULT NULL,
    actualizado_en    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_configuracion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 4. usuario
-- -----------------------------------------------------------
CREATE TABLE usuario (
    id_usuario      INT(10) UNSIGNED     NOT NULL AUTO_INCREMENT,
    nombre          VARCHAR(100)         NOT NULL,
    apellido        VARCHAR(100)         NOT NULL,
    nombre_usuario  VARCHAR(50)          NOT NULL UNIQUE,
    correo          VARCHAR(254)         NOT NULL UNIQUE,
    contrasena_hash VARCHAR(255)         NOT NULL,
    rol             ENUM('cliente','artista','administrador') NOT NULL DEFAULT 'cliente',
    id_zona         SMALLINT(5) UNSIGNED DEFAULT NULL,
    direccion       VARCHAR(200)         DEFAULT NULL,
    telefono        VARCHAR(30)          DEFAULT NULL,
    avatar_path     VARCHAR(255)         DEFAULT NULL,
    portada_path    VARCHAR(255)         DEFAULT NULL,
    estado          ENUM('activo','suspendido','inactivo') NOT NULL DEFAULT 'activo',
    creado_en       DATETIME             NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  DATETIME             NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ultimo_acceso   DATETIME             DEFAULT NULL,
    PRIMARY KEY (id_usuario),
    FOREIGN KEY (id_zona) REFERENCES zona (id_zona) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 5. artista  (extiende usuario cuando rol = 'artista')
-- -----------------------------------------------------------
CREATE TABLE artista (
    id_artista      INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    id_usuario      INT(10) UNSIGNED NOT NULL UNIQUE,
    nombre_artistico VARCHAR(100)   DEFAULT NULL,
    biografia       TEXT            DEFAULT NULL,
    especialidad    VARCHAR(120)    DEFAULT NULL,
    creado_en       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_artista),
    FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 6. seguimiento
-- -----------------------------------------------------------
CREATE TABLE seguimiento (
    id_seguidor   INT(10) UNSIGNED NOT NULL,
    id_seguido    INT(10) UNSIGNED NOT NULL,
    creado_en     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_seguidor, id_seguido),
    FOREIGN KEY (id_seguidor) REFERENCES usuario (id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_seguido)  REFERENCES usuario (id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 7. obra
-- -----------------------------------------------------------
CREATE TABLE obra (
    id_obra              INT(10) UNSIGNED     NOT NULL AUTO_INCREMENT,
    id_artista           INT(10) UNSIGNED     NOT NULL,
    id_categoria         SMALLINT(5) UNSIGNED NOT NULL,
    id_zona              SMALLINT(5) UNSIGNED DEFAULT NULL,
    titulo               VARCHAR(160)         NOT NULL,
    descripcion          TEXT                 DEFAULT NULL,
    ruta_imagen          VARCHAR(255)         DEFAULT NULL,
    precio               DECIMAL(12,2)        NOT NULL DEFAULT 0.00,
    moneda               ENUM('COP','USD')    NOT NULL DEFAULT 'COP',
    permite_colaboracion TINYINT(1)            NOT NULL DEFAULT 0,
    estado_publicacion   ENUM('pendiente','publicada','rechazada','oculta') NOT NULL DEFAULT 'publicada',
    estado_venta         ENUM('disponible','reservada','vendida','no_disponible') NOT NULL DEFAULT 'disponible',
    reservada_por        INT(10) UNSIGNED DEFAULT NULL,
    creado_en            DATETIME             NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en       DATETIME             NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    publicada_en         DATETIME             DEFAULT NULL,
    PRIMARY KEY (id_obra),
    FOREIGN KEY (id_artista)   REFERENCES artista (id_artista) ON DELETE CASCADE,
    FOREIGN KEY (id_categoria) REFERENCES categoria_obra (id_categoria),
    FOREIGN KEY (id_zona)      REFERENCES zona (id_zona) ON DELETE SET NULL,
    FOREIGN KEY (reservada_por) REFERENCES usuario (id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 8. obra_colaborador
-- -----------------------------------------------------------
CREATE TABLE obra_colaborador (
    id_obra            INT(10) UNSIGNED NOT NULL,
    id_artista         INT(10) UNSIGNED NOT NULL,
    rol_colaboracion   VARCHAR(100)     DEFAULT NULL,
    creado_en          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_obra, id_artista),
    FOREIGN KEY (id_obra)    REFERENCES obra    (id_obra)    ON DELETE CASCADE,
    FOREIGN KEY (id_artista) REFERENCES artista (id_artista) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 9. me_gusta
-- -----------------------------------------------------------
CREATE TABLE me_gusta (
    id_usuario  INT(10) UNSIGNED NOT NULL,
    id_obra     INT(10) UNSIGNED NOT NULL,
    creado_en   DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_usuario, id_obra),
    FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_obra)    REFERENCES obra    (id_obra)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 10. comentario
-- -----------------------------------------------------------
CREATE TABLE comentario (
    id_comentario  INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    id_obra        INT(10) UNSIGNED NOT NULL,
    id_usuario     INT(10) UNSIGNED NOT NULL,
    contenido      TEXT             NOT NULL,
    estado         ENUM('visible','oculto','eliminado') NOT NULL DEFAULT 'visible',
    creado_en      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_comentario),
    FOREIGN KEY (id_obra)    REFERENCES obra     (id_obra)    ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuario  (id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 11. reporte_contenido
-- -----------------------------------------------------------
CREATE TABLE reporte_contenido (
    id_reporte            INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    id_reportante         INT(10) UNSIGNED NOT NULL,
    id_obra               INT(10) UNSIGNED NOT NULL,
    id_comentario         INT(10) UNSIGNED DEFAULT NULL,
    motivo                VARCHAR(120)     NOT NULL,
    detalle               TEXT             DEFAULT NULL,
    estado                ENUM('pendiente','ignorado','resuelto') NOT NULL DEFAULT 'pendiente',
    id_revisor            INT(10) UNSIGNED DEFAULT NULL,
    creado_en             DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revisado_en           DATETIME         DEFAULT NULL,
    observaciones_revision TEXT            DEFAULT NULL,
    PRIMARY KEY (id_reporte),
    FOREIGN KEY (id_reportante)  REFERENCES usuario (id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_obra)        REFERENCES obra    (id_obra)    ON DELETE CASCADE,
    FOREIGN KEY (id_comentario)  REFERENCES comentario (id_comentario) ON DELETE SET NULL,
    FOREIGN KEY (id_revisor)     REFERENCES usuario (id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 11.1 reporte_artista
-- -----------------------------------------------------------
CREATE TABLE reporte_artista (
    id_reporte_artista   INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    id_reportante        INT(10) UNSIGNED NOT NULL,
    id_artista           INT(10) UNSIGNED NOT NULL,
    motivo               VARCHAR(120) NOT NULL,
    detalle              TEXT DEFAULT NULL,
    estado               ENUM('pendiente','ignorado','resuelto') NOT NULL DEFAULT 'pendiente',
    id_revisor           INT(10) UNSIGNED DEFAULT NULL,
    creado_en            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revisado_en          DATETIME DEFAULT NULL,
    observaciones_revision TEXT DEFAULT NULL,
    PRIMARY KEY (id_reporte_artista),
    INDEX idx_reporte_artista_estado (estado, creado_en),
    FOREIGN KEY (id_reportante) REFERENCES usuario (id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_artista) REFERENCES artista (id_artista) ON DELETE CASCADE,
    FOREIGN KEY (id_revisor) REFERENCES usuario (id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 12. carrito
-- -----------------------------------------------------------
CREATE TABLE carrito (
    id_carrito   INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    id_usuario   INT(10) UNSIGNED NOT NULL,
    estado       ENUM('activo','convertido','abandonado') NOT NULL DEFAULT 'activo',
    creado_en    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_carrito),
    FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 13. carrito_detalle
-- -----------------------------------------------------------
CREATE TABLE carrito_detalle (
    id_carrito_detalle INT(10) UNSIGNED  NOT NULL AUTO_INCREMENT,
    id_carrito         INT(10) UNSIGNED  NOT NULL,
    id_obra            INT(10) UNSIGNED  NOT NULL,
    cantidad           SMALLINT(5) UNSIGNED NOT NULL DEFAULT 1,
    precio_unitario    DECIMAL(12,2)     NOT NULL,
    moneda             ENUM('COP','USD') NOT NULL DEFAULT 'COP',
    agregado_en        DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_carrito_detalle),
    FOREIGN KEY (id_carrito) REFERENCES carrito (id_carrito) ON DELETE CASCADE,
    FOREIGN KEY (id_obra)    REFERENCES obra    (id_obra)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 14. pedido
-- -----------------------------------------------------------
CREATE TABLE pedido (
    id_pedido          INT(10) UNSIGNED  NOT NULL AUTO_INCREMENT,
    id_usuario_cliente INT(10) UNSIGNED  NOT NULL,
    estado_pedido      ENUM('pendiente','confirmado','cancelado','completado') NOT NULL DEFAULT 'pendiente',
    estado_pago        ENUM('pendiente','confirmado','fallido','reembolsado','no_aplica') NOT NULL DEFAULT 'pendiente',
    moneda             ENUM('COP','USD') NOT NULL DEFAULT 'COP',
    subtotal           DECIMAL(12,2)     NOT NULL DEFAULT 0.00,
    total_comision     DECIMAL(12,2)     NOT NULL DEFAULT 0.00,
    total              DECIMAL(12,2)     NOT NULL DEFAULT 0.00,
    creado_en          DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en     DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_pedido),
    FOREIGN KEY (id_usuario_cliente) REFERENCES usuario (id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 15. detalle_pedido
-- -----------------------------------------------------------
CREATE TABLE detalle_pedido (
    id_detalle_pedido   INT(10) UNSIGNED  NOT NULL AUTO_INCREMENT,
    id_pedido           INT(10) UNSIGNED  NOT NULL,
    id_obra             INT(10) UNSIGNED  NOT NULL,
    id_artista          INT(10) UNSIGNED  NOT NULL,
    cantidad            SMALLINT(5) UNSIGNED NOT NULL DEFAULT 1,
    precio_unitario     DECIMAL(12,2)     NOT NULL,
    moneda              ENUM('COP','USD') NOT NULL DEFAULT 'COP',
    porcentaje_comision DECIMAL(5,2)      NOT NULL DEFAULT 10.00,
    valor_comision      DECIMAL(12,2)     NOT NULL DEFAULT 0.00,
    subtotal_linea      DECIMAL(12,2)     NOT NULL DEFAULT 0.00,
    PRIMARY KEY (id_detalle_pedido),
    FOREIGN KEY (id_pedido)  REFERENCES pedido  (id_pedido)  ON DELETE CASCADE,
    FOREIGN KEY (id_obra)    REFERENCES obra     (id_obra)    ON DELETE CASCADE,
    FOREIGN KEY (id_artista) REFERENCES artista  (id_artista) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 16. proyecto_colaborativo
-- -----------------------------------------------------------
CREATE TABLE proyecto_colaborativo (
    id_proyecto       INT(10) UNSIGNED     NOT NULL AUTO_INCREMENT,
    id_artista_creador INT(10) UNSIGNED    NOT NULL,
    id_zona           SMALLINT(5) UNSIGNED DEFAULT NULL,
    titulo            VARCHAR(160)         NOT NULL,
    descripcion       TEXT                 DEFAULT NULL,
    progreso          TINYINT(3) UNSIGNED  NOT NULL DEFAULT 0,
    estado            ENUM('abierto','activo','completado','cancelado') NOT NULL DEFAULT 'abierto',
    creado_en         DATETIME             NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en    DATETIME             NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_proyecto),
    FOREIGN KEY (id_artista_creador) REFERENCES artista (id_artista) ON DELETE CASCADE,
    FOREIGN KEY (id_zona)            REFERENCES zona    (id_zona)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 17. participante_colaboracion
-- -----------------------------------------------------------
CREATE TABLE participante_colaboracion (
    id_proyecto          INT(10) UNSIGNED NOT NULL,
    id_artista           INT(10) UNSIGNED NOT NULL,
    rol_participacion    VARCHAR(100)     DEFAULT NULL,
    estado_invitacion    ENUM('pendiente','aceptada','rechazada') NOT NULL DEFAULT 'pendiente',
    activa               TINYINT(1)       NOT NULL DEFAULT 1,
    invitado_en          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    respondido_en        DATETIME         DEFAULT NULL,
    PRIMARY KEY (id_proyecto, id_artista),
    FOREIGN KEY (id_proyecto) REFERENCES proyecto_colaborativo (id_proyecto) ON DELETE CASCADE,
    FOREIGN KEY (id_artista)  REFERENCES artista               (id_artista)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 18. mensaje
-- -----------------------------------------------------------
CREATE TABLE mensaje (
    id_mensaje  INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    id_emisor   INT(10) UNSIGNED NOT NULL,
    id_receptor INT(10) UNSIGNED NOT NULL,
    contenido   TEXT             NOT NULL,
    leido_en    DATETIME         DEFAULT NULL,
    creado_en   DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_mensaje),
    INDEX idx_mensaje_conversacion (id_emisor, id_receptor, creado_en),
    INDEX idx_mensaje_receptor (id_receptor, leido_en),
    FOREIGN KEY (id_emisor)   REFERENCES usuario (id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_receptor) REFERENCES usuario (id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 19. borrador_obra
-- -----------------------------------------------------------
CREATE TABLE borrador_obra (
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
    FOREIGN KEY (id_artista) REFERENCES artista (id_artista) ON DELETE CASCADE,
    FOREIGN KEY (id_categoria) REFERENCES categoria_obra (id_categoria) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 20. obra_visita
-- -----------------------------------------------------------
CREATE TABLE obra_visita (
    id_visita INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    id_obra   INT(10) UNSIGNED NOT NULL,
    id_usuario INT(10) UNSIGNED DEFAULT NULL,
    visitado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_visita),
    INDEX idx_obra_visita_fecha (id_obra, visitado_en),
    FOREIGN KEY (id_obra) REFERENCES obra (id_obra) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 21. administrador_log
-- -----------------------------------------------------------
CREATE TABLE administrador_log (
    id_log      INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    id_admin    INT(10) UNSIGNED NOT NULL,
    accion      VARCHAR(100) NOT NULL,
    entidad     VARCHAR(100) DEFAULT NULL,
    id_entidad  INT(10) UNSIGNED DEFAULT NULL,
    descripcion TEXT DEFAULT NULL,
    creado_en   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_log),
    INDEX idx_administrador_log_fecha (creado_en),
    FOREIGN KEY (id_admin) REFERENCES usuario (id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 22. anuncio_global
-- -----------------------------------------------------------
CREATE TABLE anuncio_global (
    id_anuncio INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    id_admin INT(10) UNSIGNED NOT NULL,
    titulo VARCHAR(160) NOT NULL,
    mensaje TEXT NOT NULL,
    audiencia ENUM('todos','artistas','clientes') NOT NULL DEFAULT 'todos',
    tipo ENUM('informativo','importante','mantenimiento') NOT NULL DEFAULT 'informativo',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expira_en DATETIME DEFAULT NULL,
    PRIMARY KEY (id_anuncio),
    INDEX idx_anuncio_global_fecha (activo, creado_en),
    FOREIGN KEY (id_admin) REFERENCES usuario (id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;

-- ============================================================
-- DATOS INICIALES
-- ============================================================

-- Zonas / Comunas de Medellín
INSERT INTO zona (nombre, tipo) VALUES
('Popular',         'comuna'),
('Santa Cruz',      'comuna'),
('Manrique',        'comuna'),
('Aranjuez',        'comuna'),
('Castilla',        'comuna'),
('Doce de Octubre', 'comuna'),
('Robledo',         'comuna'),
('Villa Hermosa',   'comuna'),
('Buenos Aires',    'comuna'),
('La Candelaria',   'comuna'),
('Laureles-Estadio','comuna'),
('La América',      'comuna'),
('San Javier',      'comuna'),
('El Poblado',      'comuna'),
('Guayabal',        'comuna'),
('Belén',           'comuna'),
('San Sebastián de Palmitas', 'corregimiento'),
('San Cristóbal',   'corregimiento'),
('Altavista',       'corregimiento'),
('San Antonio de Prado', 'corregimiento'),
('Santa Elena',     'corregimiento');

-- Categorías de obra
INSERT INTO categoria_obra (id_categoria_padre, nombre, slug, descripcion) VALUES
(NULL, 'Ilustración & Pintura',  'ilustracion-pintura',  'Arte visual 2D: óleo, acrílico, acuarela, digital'),
(NULL, 'Escultura & 3D',         'escultura-3d',         'Obras tridimensionales, modelos e instalaciones'),
(NULL, 'Música & Audio',         'musica-audio',         'Pistas, álbumes, singles y videoclips'),
(NULL, 'Artes Escénicas',        'artes-escenicas',      'Teatro, danza, performance y circo'),
(NULL, 'Literatura & Escritura', 'literatura-escritura', 'Libros, poesía, cuentos y ensayos'),
(1,    'Arte Digital',           'arte-digital',         'Ilustración y pintura 100% digital'),
(1,    'Acuarela',               'acuarela',             NULL),
(1,    'Óleo sobre lienzo',      'oleo-lienzo',          NULL),
(2,    'Cerámica',               'ceramica',             NULL),
(3,    'Rock & Metal',           'rock-metal',           NULL),
(3,    'Hip Hop & Rap',          'hip-hop-rap',          NULL),
(3,    'Electrónica',            'electronica',          NULL),
(4,    'Teatro',                 'teatro',               NULL),
(4,    'Danza Contemporánea',    'danza-contemporanea',  NULL),
(5,    'Novela',                 'novela',               NULL),
(5,    'Poesía',                 'poesia',               NULL);

-- Configuración del sistema por defecto
INSERT INTO configuracion_sistema (nombre_plataforma, porcentaje_comision, correo_soporte) VALUES
('Poli-Art', 10.00, 'soporte@poliart.co');

-- Crea el primer administrador desde CLI con setup_admin.php y credenciales
-- proporcionadas por variables de entorno; nunca se siembran contraseñas conocidas.
