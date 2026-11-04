<?php
/** Diagnóstico: muestra de dónde se obtiene el secreto (sin revelar su valor). */
$R = dirname(__DIR__);
require "$R/src/config/config.php";
require "$R/src/helpers/functions.php";
echo 'APP_ENV      : ' . (getenv('APP_ENV') ?: 'development') . PHP_EOL;
echo 'Fuente       : ' . lab_secreto_fuente('APP_SECRET_KEY') . PHP_EOL;
echo 'Valor (masc.): ' . lab_enmascarar(APP_SECRET_KEY) . PHP_EOL;
