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

            $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE username = :u');
            $stmt->execute([':u' => $username]);

            if ($stmt->fetch()) {
                $mensaje = 'El nombre de usuario ya está en uso.';
            } else {
                $hash = password_hash($pass1, PASSWORD_DEFAULT);

                $ins = $pdo->prepare(
                    'INSERT INTO usuarios (username, password_hash) VALUES (:u, :p)'
                );
                $ins->execute([
                    ':u' => $username,
                    ':p' => $hash,
                ]);

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
    <title>Crear Cuenta - Sistema de Gestión de Tareas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root{
            --bg-gradient: linear-gradient(135deg,#5b8def 0%,#7f5af0 40%,#ff6aa6 100%);
            --card-bg: rgba(10,12,24,0.96);
            --border-soft: rgba(255,255,255,0.12);
            --text-main:#f5f7ff;
            --text-muted:#a5b4cf;
            --accent:#55efc4;
            --danger:#ff7675;
        }

        *{margin:0;padding:0;box-sizing:border-box;}

        body{
            min-height:100vh;
            background:
                radial-gradient(circle at 0% 0%, rgba(255,255,255,0.18) 0,transparent 55%),
                radial-gradient(circle at 100% 100%, rgba(0,0,0,0.5) 0,transparent 60%),
                var(--bg-gradient);
            display:flex;
            align-items:center;
            justify-content:center;
            padding:16px;
            font-family:"Poppins",system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
            color:var(--text-main);
        }

        .auth-shell{
            width:100%;
            max-width:420px;
            background:radial-gradient(circle at 0 0, rgba(255,255,255,0.06) 0,transparent 55%),
                       radial-gradient(circle at 100% 100%, rgba(0,0,0,0.5) 0,transparent 60%),
                       var(--card-bg);
            border-radius:24px;
            border:1px solid var(--border-soft);
            box-shadow:0 26px 60px rgba(0,0,0,0.7);
            padding:22px 22px 18px;
            position:relative;
            overflow:hidden;
        }

        .auth-shell::before{
            content:'';
            position:absolute;
            inset:0;
            background-image:
                radial-gradient(circle at 20% 0%, rgba(255,255,255,0.20) 0, transparent 58%),
                radial-gradient(circle at 100% 100%, rgba(0,0,0,0.65) 0, transparent 60%);
            mix-blend-mode:soft-light;
            opacity:0.65;
            pointer-events:none;
        }

        .auth-inner{position:relative;}

        .badge-app{
            font-size:11px;
            letter-spacing:.16em;
            text-transform:uppercase;
            color:var(--text-muted);
            margin-bottom:6px;
            display:flex;
            align-items:center;
            gap:6px;
        }

        .badge-dot{
            width:6px;height:6px;border-radius:50%;
            background:#ffeaa7;
            box-shadow:0 0 0 4px rgba(255,234,167,0.2);
        }

        h1{
            font-size:22px;
            margin-bottom:4px;
            display:flex;
            align-items:center;
            gap:8px;
        }

        h1 span.emoji{font-size:22px;}

        .subtitle{
            font-size:13px;
            color:var(--text-muted);
            margin-bottom:18px;
        }

        .tabs{
            display:flex;
            gap:6px;
            background:rgba(255,255,255,0.04);
            padding:3px;
            border-radius:999px;
            margin-bottom:18px;
        }

        .tab{
            flex:1;
            text-align:center;
            font-size:12px;
            padding:6px 0;
            border-radius:999px;
            text-decoration:none;
            color:var(--text-muted);
            transition:all .15s ease;
        }

        .tab.active{
            background:linear-gradient(135deg,#55efc4,#81ecec);
            color:#02130f;
            font-weight:600;
            box-shadow:0 6px 18px rgba(85,239,196,0.45);
        }

        form{
            display:flex;
            flex-direction:column;
            gap:12px;
            margin-bottom:10px;
        }

        label{
            font-size:12px;
            color:var(--text-muted);
            margin-bottom:2px;
        }

        .field{
            display:flex;
            flex-direction:column;
            gap:4px;
        }

        .input-wrap{
            position:relative;
        }

        input[type="text"],
        input[type="password"]{
            width:100%;
            border-radius:12px;
            border:1px solid rgba(255,255,255,0.16);
            padding:10px 12px 10px 36px;
            background:rgba(8,10,23,0.95);
            color:var(--text-main);
            font-size:13px;
        }

        input::placeholder{
            color:rgba(255,255,255,0.35);
        }

        input:focus{
            outline:none;
            border-color:rgba(85,239,196,0.9);
            box-shadow:0 0 0 1px rgba(85,239,196,0.9);
        }

        .input-icon{
            position:absolute;
            left:11px;top:50%;
            transform:translateY(-50%);
            font-size:13px;
            color:var(--text-muted);
        }

        .helper{
            font-size:11px;
            color:var(--text-muted);
        }

        .btn-primary{
            margin-top:4px;
            width:100%;
            border:none;
            border-radius:999px;
            padding:10px 12px;
            font-size:13px;
            font-weight:600;
            cursor:pointer;
            background:linear-gradient(135deg,#55efc4,#81ecec);
            color:#02130f;
            box-shadow:0 10px 28px rgba(85,239,196,0.55);
            transition:transform .14s ease, box-shadow .14s ease;
        }

        .btn-primary:hover{
            transform:translateY(-1px);
            box-shadow:0 14px 32px rgba(85,239,196,0.7);
        }

        .btn-primary:active{
            transform:translateY(0);
            box-shadow:0 7px 18px rgba(85,239,196,0.55);
        }

        .bottom-text{
            margin-top:10px;
            font-size:12px;
            text-align:center;
            color:var(--text-muted);
        }

        .bottom-text a{
            color:var(--accent);
            text-decoration:none;
            font-weight:500;
        }

        .bottom-text a:hover{
            text-decoration:underline;
        }

        .alert{
            margin-bottom:10px;
            font-size:12px;
            padding:8px 10px;
            border-radius:12px;
            background:rgba(255,118,117,0.12);
            border:1px solid rgba(255,118,117,0.65);
            color:#ffdede;
            display:flex;
            align-items:flex-start;
            gap:8px;
        }

        .alert-icon{font-size:14px;}

        @media (max-width:480px){
            .auth-shell{
                padding:18px 16px 14px;
                border-radius:18px;
            }
            h1{font-size:20px;}
        }
    </style>
</head>
<body>
<div class="auth-shell">
    <div class="auth-inner">
        <div class="badge-app">
            <span class="badge-dot"></span>
            SISTEMA DE GESTIÓN DE TAREAS
        </div>
        <h1><span class="emoji">🧾</span> Crear cuenta</h1>
        <p class="subtitle">Regístrate para empezar a organizar tus tareas con el tablero Kanban.</p>

        <div class="tabs">
            <a href="login.php" class="tab">Iniciar sesión</a>
            <a href="crear.php" class="tab active">Crear cuenta</a>
        </div>

        <?php if ($mensaje !== ''): ?>
            <div class="alert">
                <span class="alert-icon">⚠️</span>
                <span><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        <?php endif; ?>

        <form action="crear.php" method="post">
            <div class="field">
                <label for="nuevoUsuario">Nombre de usuario</label>
                <div class="input-wrap">
                    <span class="input-icon">👤</span>
                    <input type="text" id="nuevoUsuario" name="nuevoUsuario"
                           placeholder="elige.un.usuario"
                           required>
                </div>
                <span class="helper">Debe ser único. Lo usarás para iniciar sesión.</span>
            </div>

            <div class="field">
                <label for="nuevaContrasena">Contraseña</label>
                <div class="input-wrap">
                    <span class="input-icon">🔒</span>
                    <input type="password" id="nuevaContrasena" name="nuevaContrasena"
                           placeholder="••••••••"
                           required>
                </div>
            </div>

            <div class="field">
                <label for="confirmarContrasena">Confirmar contraseña</label>
                <div class="input-wrap">
                    <span class="input-icon">✅</span>
                    <input type="password" id="confirmarContrasena" name="confirmarContrasena"
                           placeholder="repítela otra vez"
                           required>
                </div>
                <span class="helper">Ambas contraseñas deben coincidir exactamente.</span>
            </div>

            <button type="submit" class="btn-primary">Crear cuenta y entrar</button>
        </form>
    </div>
</div>
</body>
</html>
