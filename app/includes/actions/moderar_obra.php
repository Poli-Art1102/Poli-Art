<?php
require_once __DIR__ . '/../auth.php';
requireLogin(['administrador']);

http_response_code(410);
exit('La aprobación previa de obras fue deshabilitada. Solo se moderan obras reportadas.');
