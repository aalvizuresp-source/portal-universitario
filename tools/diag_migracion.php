<?php
/** Diagnóstico: migración automática de un hash heredado (estu2) en el login. */
$R = dirname(__DIR__);
require "$R/src/config/config.php"; require "$R/src/config/Database.php";
require "$R/src/helpers/functions.php"; require "$R/src/helpers/security.php";
require "$R/src/models/UserModel.php";
$h = UserModel::porUsername('estu2')['password_hash'];
echo "ANTES del primer login:\n  hash   = $h\n";
echo '  es legado (SHA-256 64 hex)? ' . (lab_es_hash_legado($h) ? 'SI' : 'no') . "\n";
if (lab_verificar_hash_legado('Estudiante123', $h)) {
    UserModel::cambiarPassword((int)UserModel::porUsername('estu2')['id'], lab_hash_password('Estudiante123'));
    echo "\n-> login correcto: se re-hashea con el algoritmo moderno\n\n";
}
$h2 = UserModel::porUsername('estu2')['password_hash'];
echo "DESPUES del primer login:\n  hash   = " . substr($h2,0,40) . "...\n";
echo '  es Argon2id? ' . (str_starts_with($h2,'$argon2id$') ? 'SI' : 'no') . "\n";
