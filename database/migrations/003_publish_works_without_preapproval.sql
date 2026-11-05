USE poliart;

UPDATE obra
SET estado_publicacion = 'publicada',
    publicada_en = COALESCE(publicada_en, creado_en)
WHERE estado_publicacion = 'pendiente';

ALTER TABLE obra
    MODIFY estado_publicacion ENUM('pendiente','publicada','rechazada','oculta')
    NOT NULL DEFAULT 'publicada';
