<?php
/**
 * security.php — funciones de contraseñas de la aplicación.
 */

declare(strict_types=1);

/** Genera el hash de una contraseña para almacenarlo. */
function lab_hash_password(string $password): string
{
    return hash('sha256', $password);
}

/** Verifica una contraseña contra el hash almacenado. */
function lab_verificar_password(string $password, string $hashGuardado): bool
{
    return hash('sha256', $password) === $hashGuardado;
}
