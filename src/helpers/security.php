<?php
/**
 * security.php — funciones de seguridad de la aplicación.
 *
 *   - Contraseñas: password_hash()/password_verify() (Argon2id o bcrypt).
 *   - Tokens CSRF por sesión.
 *   - Validación y normalización de entradas (clase Validador).
 *   - Cifrado autenticado con Sodium (crypto_secretbox) para datos sensibles.
 *
 * Se usan exclusivamente las APIs criptográficas nativas de PHP.
 */

declare(strict_types=1);

/* ========================================================================== *
 *  CONTRASEÑAS
 * ========================================================================== */

/**
 * Devuelve el algoritmo de hashing a usar: Argon2id si la instalación lo
 * soporta; en caso contrario, bcrypt (PASSWORD_DEFAULT).
 */
function lab_algoritmo_password(): string
{
    if (defined('PASSWORD_ARGON2ID')) {
        return PASSWORD_ARGON2ID;
    }
    return PASSWORD_DEFAULT; // bcrypt
}

/** Genera un hash seguro de la contraseña (con sal automática y coste). */
function lab_hash_password(string $password): string
{
    return password_hash($password, lab_algoritmo_password());
}

/** Verifica una contraseña contra un hash de password_hash(). */
function lab_verificar_password(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

/**
 * Indica si un hash almacenado pertenece al formato heredado del sistema
 * anterior (SHA-256 hexadecimal de 64 caracteres). Estos hashes sólo se aceptan
 * para migrarlos a password_hash() en el siguiente inicio de sesión correcto.
 */
function lab_es_hash_legado(string $hash): bool
{
    return (bool)preg_match('/^[0-9a-f]{64}$/', $hash);
}

/** Verifica una contraseña contra un hash heredado (comparación en tiempo constante). */
function lab_verificar_hash_legado(string $password, string $hash): bool
{
    return hash_equals($hash, hash('sha256', $password));
}

/* ========================================================================== *
 *  CSRF
 * ========================================================================== */

/** Devuelve el token CSRF de la sesión, creándolo si no existe. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Campo oculto para insertar en los formularios. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Verifica el token CSRF en una petición POST. Si falla, corta la ejecución.
 * Usa hash_equals para comparación en tiempo constante.
 */
function csrf_verificar(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }
    $enviado = $_POST['csrf_token'] ?? '';
    $esperado = $_SESSION['csrf_token'] ?? '';
    if ($esperado === '' || !is_string($enviado) || !hash_equals($esperado, $enviado)) {
        registrar_evento('CSRF_FAIL', 'Token CSRF inválido o ausente', 'DENEGADO');
        http_response_code(419);
        render_vista('error', [
            'titulo'  => 'Solicitud no válida',
            'mensaje' => 'La sesión del formulario expiró o no es válida. Vuelva a intentarlo.',
        ]);
        exit;
    }
}

/* ========================================================================== *
 *  VALIDACIÓN DE ENTRADAS
 * ========================================================================== */

final class Validador
{
    /** @var string[] */
    public array $errores = [];

    public function email(string $valor, string $campo = 'correo'): ?string
    {
        $valor = trim(mb_strtolower($valor));
        if ($valor === '' || !filter_var($valor, FILTER_VALIDATE_EMAIL)) {
            $this->errores[] = "El $campo no tiene un formato válido.";
            return null;
        }
        if (mb_strlen($valor) > 120) {
            $this->errores[] = "El $campo es demasiado largo.";
            return null;
        }
        return $valor;
    }

    public function carne(string $valor): ?string
    {
        $valor = trim($valor);
        // Formato: 4 dígitos, guion, 4 dígitos (p. ej. 2021-0001)
        if (!preg_match('/^\d{4}-\d{4}$/', $valor)) {
            $this->errores[] = "El carné debe tener el formato NNNN-NNNN (p. ej. 2021-0001).";
            return null;
        }
        return $valor;
    }

    public function nombre(string $valor, string $campo = 'nombre'): ?string
    {
        $valor = trim($valor);
        if ($valor === '' || mb_strlen($valor) > 80) {
            $this->errores[] = "El $campo es obligatorio y de máximo 80 caracteres.";
            return null;
        }
        // Letras (incl. acentos/ñ), espacios, apóstrofo y guion.
        if (!preg_match("/^[\p{L}\p{M} .'\-]+$/u", $valor)) {
            $this->errores[] = "El $campo contiene caracteres no permitidos.";
            return null;
        }
        return $valor;
    }

    public function nota(string $valor): ?float
    {
        if (!is_numeric($valor)) {
            $this->errores[] = "La nota debe ser un número.";
            return null;
        }
        $n = (float)$valor;
        if ($n < 0 || $n > 100) {
            $this->errores[] = "La nota debe estar entre 0 y 100.";
            return null;
        }
        return $n;
    }

    /**
     * Fecha de nacimiento (opcional): formato AAAA-MM-DD, fecha real del
     * calendario, no futura y con una edad entre 14 y 100 años.
     */
    public function fechaNacimiento(string $valor): ?string
    {
        $valor = trim($valor);
        if ($valor === '') {
            return null;
        }
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $valor, $m)
            || !checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
            $this->errores[] = 'La fecha de nacimiento no es una fecha válida (AAAA-MM-DD).';
            return null;
        }
        $fecha = new DateTimeImmutable($valor);
        $hoy   = new DateTimeImmutable('today');
        $edad  = $fecha->diff($hoy)->y;
        if ($fecha > $hoy || $edad < 14 || $edad > 100) {
            $this->errores[] = 'La fecha de nacimiento está fuera del rango permitido.';
            return null;
        }
        return $valor;
    }

    /**
     * Teléfono (opcional): sólo dígitos, con "+" inicial, espacios o guiones
     * como separadores; entre 8 y 15 dígitos. Se normaliza quitando espacios.
     */
    public function telefono(string $valor, string $campo = 'teléfono'): ?string
    {
        $valor = trim($valor);
        if ($valor === '') {
            return null;
        }
        $digitos = preg_replace('/[\s-]/', '', $valor);
        if (!preg_match('/^\+?\d{8,15}$/', $digitos)) {
            $this->errores[] = "El $campo sólo admite dígitos (8 a 15), con '+', espacios o guiones.";
            return null;
        }
        return preg_replace('/\s+/', ' ', $valor);
    }

    /** Identificador numérico positivo (para ?id=). */
    public function id(string|int $valor): ?int
    {
        if (!preg_match('/^\d+$/', (string)$valor)) {
            $this->errores[] = "Identificador no válido.";
            return null;
        }
        return (int)$valor;
    }

    /** Parámetro de búsqueda: limita longitud y normaliza, sin alterar su uso seguro. */
    public function busqueda(string $valor): string
    {
        return mb_substr(trim($valor), 0, 60);
    }

    /** Texto libre (comentarios): recorta longitud máxima. */
    public function texto(string $valor, int $max = 1000, string $campo = 'texto'): ?string
    {
        $valor = trim($valor);
        if ($valor === '') {
            $this->errores[] = "El $campo no puede estar vacío.";
            return null;
        }
        if (mb_strlen($valor) > $max) {
            $this->errores[] = "El $campo supera los $max caracteres.";
            return null;
        }
        return $valor;
    }

    public function ok(): bool
    {
        return empty($this->errores);
    }

    public function primerError(): string
    {
        return $this->errores[0] ?? 'Datos no válidos.';
    }
}

/* ========================================================================== *
 *  CIFRADO AUTENTICADO CON SODIUM
 * ========================================================================== */

/**
 * Carga (o genera la primera vez) la clave de cifrado desde un archivo FUERA
 * del código fuente y FUERA de public/, en storage/keys/.
 *
 * La clave nunca se guarda en el código ni en la base de datos. Se toma de la
 * variable de entorno APP_ENCRYPTION_KEY y, si no existe, del archivo
 * storage/keys/secretbox.key (excluido del control de versiones y con permisos
 * restringidos).
 */
function lab_clave_cifrado(): string
{
    // Prioridad 1: secreto externo (variable de entorno del proceso o .env),
    // leído con el mismo mecanismo unificado que el resto de secretos.
    $env = lab_secreto('APP_ENCRYPTION_KEY');
    if ($env !== null && $env !== '') {
        $bin = base64_decode($env, true);
        if ($bin !== false && strlen($bin) === SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            return $bin;
        }
    }

    // Prioridad 2: archivo de clave en storage/keys/ (fuera de public/).
    if (!is_dir(KEYS_PATH)) {
        mkdir(KEYS_PATH, 0700, true);
    }
    $archivo = KEYS_PATH . DIRECTORY_SEPARATOR . 'secretbox.key';
    if (is_file($archivo)) {
        $bin = base64_decode(trim((string)file_get_contents($archivo)), true);
        if ($bin !== false && strlen($bin) === SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            return $bin;
        }
    }
    // Generar una clave nueva de forma segura y guardarla.
    $clave = sodium_crypto_secretbox_keygen();
    file_put_contents($archivo, base64_encode($clave));
    @chmod($archivo, 0600);
    return $clave;
}

/**
 * Cifra un texto con crypto_secretbox (XSalsa20-Poly1305: confidencialidad +
 * autenticidad). Devuelve base64(nonce || ciphertext).
 */
function lab_cifrar(?string $texto): ?string
{
    if ($texto === null || $texto === '') {
        return $texto;
    }
    $clave = lab_clave_cifrado();
    $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $cifrado = sodium_crypto_secretbox($texto, $nonce, $clave);
    return base64_encode($nonce . $cifrado);
}

/**
 * Descifra un valor producido por lab_cifrar(). Si el dato no está cifrado
 * (p. ej. filas antiguas en claro) o la autenticación falla, devuelve el valor
 * tal cual o null, según corresponda, sin romper la aplicación.
 */
function lab_descifrar(?string $almacenado): ?string
{
    if ($almacenado === null || $almacenado === '') {
        return $almacenado;
    }
    $raw = base64_decode($almacenado, true);
    if ($raw === false || strlen($raw) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES) {
        // No parece un valor cifrado por nosotros: devolver como está.
        return $almacenado;
    }
    $nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $cifrado = substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $clave = lab_clave_cifrado();
    $plano = sodium_crypto_secretbox_open($cifrado, $nonce, $clave);
    if ($plano === false) {
        return null; // integridad fallida o clave incorrecta
    }
    return $plano;
}
