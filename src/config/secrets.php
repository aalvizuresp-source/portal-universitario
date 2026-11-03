<?php
/**
 * secrets.php — gestión de secretos por variables de entorno.
 *
 * Provee la función lab_secreto(), que lee un secreto desde:
 *   1. Las variables de entorno reales del proceso  (getenv) — p. ej. definidas
 *      en PowerShell con  $env:APP_SECRET_KEY = "..."  antes de arrancar PHP.
 *   2. Un archivo  .env  en la raíz del proyecto (solo para desarrollo).
 *   3. Un valor por defecto (último recurso; nunca para producción).
 *
 * De este modo los secretos no forman parte del código fuente. El archivo .env
 * está excluido del control de versiones.
 *
 * Se define como función (no clase) porque config.php la usa en el momento de
 * cargar, antes que el resto de helpers.
 */

declare(strict_types=1);

/**
 * Carga perezosa del archivo .env (una sola vez) a un arreglo estático.
 * @return array<string,string>
 */
function lab_env_file(): array
{
    static $cargado = null;
    if ($cargado !== null) {
        return $cargado;
    }
    $cargado = [];
    $ruta = APP_ROOT . DIRECTORY_SEPARATOR . '.env';
    if (!is_file($ruta)) {
        return $cargado;
    }
    foreach (file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
        $linea = trim($linea);
        if ($linea === '' || $linea[0] === '#' || !str_contains($linea, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $linea, 2);
        $k = trim($k);
        $v = trim($v);
        if (strlen($v) >= 2 && ($v[0] === '"' || $v[0] === "'")) {
            $v = substr($v, 1, -1);
        }
        $cargado[$k] = $v;
    }
    return $cargado;
}

/**
 * Devuelve el valor de un secreto por su nombre.
 *
 * @param string      $clave   nombre de la variable (p. ej. APP_SECRET_KEY)
 * @param string|null $default valor por defecto si no se encuentra
 */
function lab_secreto(string $clave, ?string $default = null): ?string
{
    // 1. Variable de entorno real del proceso.
    $val = getenv($clave);
    if ($val !== false && $val !== '') {
        return $val;
    }
    // 2. Archivo .env (solo desarrollo).
    $env = lab_env_file();
    if (isset($env[$clave]) && $env[$clave] !== '') {
        return $env[$clave];
    }
    // 3. Valor por defecto.
    return $default;
}

/**
 * Indica de dónde se obtuvo un secreto, sin revelar su valor.
 */
function lab_secreto_fuente(string $clave): string
{
    $val = getenv($clave);
    if ($val !== false && $val !== '') {
        return 'variable de entorno del proceso';
    }
    $env = lab_env_file();
    if (isset($env[$clave]) && $env[$clave] !== '') {
        return 'archivo .env (fuera de Git)';
    }
    return 'valor por defecto de desarrollo (NO configurado)';
}

/** Enmascara un secreto para mostrarlo: sólo los 4 primeros caracteres. */
function lab_enmascarar(?string $valor): string
{
    if ($valor === null || $valor === '') {
        return '(vacío)';
    }
    return mb_substr($valor, 0, 4) . str_repeat('*', 12) . ' (' . mb_strlen($valor) . ' caracteres)';
}
