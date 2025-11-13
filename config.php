<?php
// config.php
// AJUSTA ESTOS DATOS A TU POSTGRES
const DB_HOST = 'localhost';
const DB_PORT = '5432';
const DB_NAME = 'sistema_gestion_tareas';
const DB_USER = 'postgres';
const DB_PASS = 'postgres';

function getConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('Error de conexión a la base de datos: ' . $e->getMessage());
        }
    }

    return $pdo;
}
