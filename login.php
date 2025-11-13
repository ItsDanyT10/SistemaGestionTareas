<?php
session_start();
require_once 'config.php';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['usuario'] ?? '');
    $password = trim($_POST['contrasena'] ?? '');

    if ($username === '' || $password === '') {
        $mensaje = 'Debes ingresar usuario y contraseña.';
    } else {
        try {
            $pdo = getConnection();

            $stmt = $pdo->prepare('SELECT id, username, password_hash FROM usuarios WHERE username = :u');
            $stmt->execute([':u' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Login correcto
                $_SESSION['usuario_id']  = $user['id'];
                $_SESSION['usuario_nom'] = $user['username'];

                header('Location: kanban.php');
                exit;
            } else {
                $mensaje = 'Usuario o contraseña incorrectos.';
            }
        } catch (PDOException $e) {
            $mensaje = 'Error al iniciar sesión: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inicio de Sesión - Sistema de Gestión de Tareas</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <h1>Iniciar Sesión</h1>

    <?php if ($mensaje !== ''): ?>
        <p style="max-width:450px;margin:0 auto 1rem;color:#e63946;font-weight:bold;">
            <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form action="login.php" method="post">
        <label for="usuario">Usuario:</label><br>
        <input type="text" id="usuario" name="usuario" required><br><br>

        <label for="contrasena">Contraseña:</label><br>
        <input type="password" id="contrasena" name="contrasena" required><br><br>

        <button type="submit">Ingresar</button>
    </form>

    <p>¿No tienes una cuenta? <a href="crear.php">Crea una aquí</a></p>
</body>
</html>
