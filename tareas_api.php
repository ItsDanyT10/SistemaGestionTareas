<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['ok' => false, 'msg' => 'No autenticado']);
    exit;
}

require_once 'config.php';

$accion = $_POST['accion'] ?? '';
$usuarioId = (int) $_SESSION['usuario_id'];

try {
    $pdo = getConnection();

    if ($accion === 'crear') {
        $titulo = trim($_POST['titulo'] ?? '');
        $estado = $_POST['estado'] ?? 'pendiente';

        if ($titulo === '') {
            echo json_encode(['ok' => false, 'msg' => 'Título vacío']);
            exit;
        }

        if (!in_array($estado, ['pendiente', 'progreso', 'completada'], true)) {
            $estado = 'pendiente';
        }

        $stmt = $pdo->prepare("
            INSERT INTO tareas (titulo, estado, usuario_id)
            VALUES (:t, :e, :u)
            RETURNING id, creada_en
        ");
        $stmt->execute([
            ':t' => $titulo,
            ':e' => $estado,
            ':u' => $usuarioId
        ]);
        $row = $stmt->fetch();

        echo json_encode([
            'ok'   => true,
            'id'   => (int)$row['id'],
            'fecha'=> $row['creada_en']
        ]);
        exit;
    }

    if ($accion === 'cambiar_estado') {
        $id     = (int) ($_POST['id'] ?? 0);
        $estado = $_POST['estado'] ?? 'pendiente';

        if (!in_array($estado, ['pendiente', 'progreso', 'completada'], true)) {
            echo json_encode(['ok' => false, 'msg' => 'Estado no válido']);
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE tareas
               SET estado = :e
             WHERE id = :id AND usuario_id = :u
        ");
        $stmt->execute([
            ':e'  => $estado,
            ':id' => $id,
            ':u'  => $usuarioId
        ]);

        if ($stmt->rowCount() === 0) {
            echo json_encode(['ok' => false, 'msg' => 'Tarea no encontrada']);
        } else {
            echo json_encode(['ok' => true]);
        }
        exit;
    }

    if ($accion === 'eliminar') {
        $id = (int) ($_POST['id'] ?? 0);

        $stmt = $pdo->prepare("
            DELETE FROM tareas
             WHERE id = :id AND usuario_id = :u
        ");
        $stmt->execute([
            ':id' => $id,
            ':u'  => $usuarioId
        ]);

        if ($stmt->rowCount() === 0) {
            echo json_encode(['ok' => false, 'msg' => 'Tarea no encontrada']);
        } else {
            echo json_encode(['ok' => true]);
        }
        exit;
    }

    echo json_encode(['ok' => false, 'msg' => 'Acción no válida']);
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
