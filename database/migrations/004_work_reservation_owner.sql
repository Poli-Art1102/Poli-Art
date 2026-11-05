USE poliart;

ALTER TABLE obra
    ADD COLUMN IF NOT EXISTS reservada_por INT(10) UNSIGNED DEFAULT NULL,
    ADD CONSTRAINT fk_obra_reservada_por
        FOREIGN KEY (reservada_por) REFERENCES usuario (id_usuario) ON DELETE SET NULL;
