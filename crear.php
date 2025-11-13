<?php
session_start();
require_once 'config.php';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username   = trim($_POST['nuevoUsuario'] ?? '');
    $pass1      = trim($_POST['nuevaContrasena'] ?? '');
    $pass2      = trim($_POST['confirmarContrasena'] ?? '');

    if ($username === '' || $pass1 === '' || $pass2 === '') {
        $mensaje = 'Todos los campos son obligatorios.';
    } elseif ($pass1 !== $pass2) {
        $mensaje = 'Las contraseñas no coinciden.';
    } else {
        try {
            $pdo = getConnection();

            // ¿Ya existe ese usuario?
            $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE username = :u');
            $stmt->execute([':u' => $username]);

            if ($stmt->fetch()) {
                $mensaje = 'El nombre de usuario ya está en uso.';
            } else {
                // Guardar con hash seguro
                $hash = password_hash($pass1, PASSWORD_DEFAULT);

                $ins = $pdo->prepare(
                    'INSERT INTO usuarios (username, password_hash) VALUES (:u, :p)'
                );
                $ins->execute([
                    ':u' => $username,
                    ':p' => $hash,
                ]);

                // Opcional: iniciar sesión automáticamente
                $_SESSION['usuario_id']  = $pdo->lastInsertId();
                $_SESSION['usuario_nom'] = $username;

                header('Location: kanban.php');
                exit;
            }
        } catch (PDOException $e) {
            $mensaje = 'Error al crear la cuenta: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Usuario - Sistema de Gestión de Tareas</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <h1>Crear Cuenta</h1>

    <?php if ($mensaje !== ''): ?>
        <p style="max-width:450px;margin:0 auto 1rem;color:#e63946;font-weight:bold;">
            <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form action="crear.php" method="post">
        <label for="nuevoUsuario">Nombre de usuario:</label><br>
        <input type="text" id="nuevoUsuario" name="nuevoUsuario" required><br><br>

        <label for="nuevaContrasena">Contraseña:</label><br>
        <input type="password" id="nuevaContrasena" name="nuevaContrasena" required><br><br>

        <label for="confirmarContrasena">Confirmar contraseña:</label><br>
        <input type="password" id="confirmarContrasena" name="confirmarContrasena" required><br><br>

        <button type="submit">Crear Cuenta</button>
    </form>

    <p>¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a></p>
</body>
</html>
