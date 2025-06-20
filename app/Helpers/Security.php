<?php
// app/Helpers/Security.php

/**
 * Nettoie une entrée pour usage SQL (à utiliser uniquement si requête préparée impossible)
 * @param mixed $input
 * @param PDO|null $pdo
 * @return mixed
 */
function sanitize_sql_input($input, $pdo = null) {
    if (is_array($input)) {
        return array_map(function($v) use ($pdo) { return sanitize_sql_input($v, $pdo); }, $input);
    }
    // Utilise la connexion db() si $pdo n'est pas fourni
    if (!$pdo && function_exists('db')) {
        $pdo = db();
    }
    if ($pdo instanceof PDO) {
        return $pdo->quote($input);
    }
    return addslashes($input);
}
