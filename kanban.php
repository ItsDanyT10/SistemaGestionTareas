<?php
session_start();
require_once 'config.php';

// Si no está logueado → al login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$usuarioId = (int) $_SESSION['usuario_id'];
$usuario   = $_SESSION['usuario_nom'] ?? 'U';
$inicial   = mb_strtoupper(mb_substr($usuario, 0, 1, 'UTF-8'));

// Cargar tareas desde la BD
$pendientes  = [];
$progreso    = [];
$completadas = [];

try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("
        SELECT id, titulo, estado, creada_en
        FROM tareas
        WHERE usuario_id = :uid
        ORDER BY id DESC
    ");
    $stmt->execute([':uid' => $usuarioId]);

    while ($row = $stmt->fetch()) {
        switch ($row['estado']) {
            case 'progreso':
                $progreso[] = $row;
                break;
            case 'completada':
                $completadas[] = $row;
                break;
            default:
                $pendientes[] = $row;
        }
    }
} catch (PDOException $e) {
    // Si hay error, mostramos arrays vacíos pero no rompemos la página
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tablero Kanban - Sistema de Gestión de Tareas</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f4f5f7;
            color: #172b4d;
            padding: 20px;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e1e4e8;
        }

        h1 {
            color: #172b4d;
            font-size: 24px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: #0052cc;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .logout-btn {
            background-color: transparent;
            border: 1px solid #dfe1e6;
            padding: 6px 12px;
            border-radius: 3px;
            color: #42526e;
            cursor: pointer;
            transition: background-color 0.2s;
            text-decoration: none;
            font-size: 14px;
        }

        .logout-btn:hover {
            background-color: #ebecf0;
        }

        .board-container {
            display: flex;
            gap: 16px;
            overflow-x: auto;
            padding-bottom: 20px;
        }

        .column {
            background-color: #ebecf0;
            border-radius: 8px;
            width: 280px;
            min-width: 280px;
            padding: 12px;
            display: flex;
            flex-direction: column;
            max-height: calc(100vh - 150px);
        }

        .column-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding: 0 8px;
        }

        .column-title {
            font-size: 16px;
            font-weight: 600;
            color: #172b4d;
        }

        .task-count {
            background-color: #dfe1e6;
            border-radius: 10px;
            padding: 2px 8px;
            font-size: 12px;
            color: #5e6c84;
        }

        .task-list {
            list-style: none;
            overflow-y: auto;
            flex-grow: 1;
            padding: 0 4px;
        }

        .task {
            background-color: white;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 8px;
            box-shadow: 0 1px 1px rgba(9, 30, 66, 0.25);
            cursor: pointer;
            transition: background-color 0.1s;
            position: relative;
        }

        .task:hover {
            background-color: #f8f9fa;
        }

        .task-title {
            font-weight: 500;
            margin-bottom: 8px;
            word-break: break-word;
        }

        .task-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #5e6c84;
        }

        .task-actions {
            display: flex;
            gap: 4px;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .task:hover .task-actions {
            opacity: 1;
        }

        .task-action-btn {
            background: none;
            border: none;
            color: #5e6c84;
            cursor: pointer;
            padding: 4px;
            border-radius: 3px;
        }

        .task-action-btn:hover {
            background-color: #ebecf0;
        }

        .add-task-btn {
            background-color: transparent;
            border: none;
            color: #5e6c84;
            padding: 8px;
            border-radius: 3px;
            cursor: pointer;
            text-align: left;
            width: 100%;
            display: flex;
            align-items: center;
            gap: 4px;
            transition: background-color 0.2s;
        }

        .add-task-btn:hover {
            background-color: #dfe1e6;
            color: #172b4d;
        }

        .add-task-form {
            display: none;
            flex-direction: column;
            gap: 8px;
            margin-top: 8px;
        }

        .task-input {
            border: 2px solid #0079bf;
            border-radius: 3px;
            padding: 8px 12px;
            font-size: 14px;
            resize: none;
            min-height: 54px;
        }

        .task-input:focus {
            outline: none;
        }

        .form-actions {
            display: flex;
            gap: 8px;
        }

        .add-btn {
            background-color: #0079bf;
            color: white;
            border: none;
            border-radius: 3px;
            padding: 6px 12px;
            cursor: pointer;
            font-size: 14px;
        }

        .cancel-btn {
            background-color: transparent;
            border: none;
            color: #5e6c84;
            cursor: pointer;
            padding: 6px 12px;
            border-radius: 3px;
            font-size: 14px;
        }

        .cancel-btn:hover {
            background-color: #dfe1e6;
        }

        .drag-over {
            background-color: #e1e4e8;
        }

        .dragging {
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .board-container {
                flex-direction: column;
                align-items: center;
            }
            
            .column {
                width: 100%;
                max-width: 400px;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>Tablero Kanban</h1>
        <div class="user-info">
            <div class="user-avatar"><?= htmlspecialchars($inicial, ENT_QUOTES, 'UTF-8') ?></div>
            <span><?= htmlspecialchars($usuario, ENT_QUOTES, 'UTF-8') ?></span>
            <a href="logout.php" class="logout-btn">Cerrar Sesión</a>
        </div>
    </header>

    <div class="board-container">
        <!-- Columna Pendientes -->
        <div class="column" id="pending-column" data-estado="pendiente">
            <div class="column-header">
                <h2 class="column-title">Pendientes</h2>
                <span class="task-count" id="pending-count">0</span>
            </div>
            <ul class="task-list" id="pending-tasks">
                <?php foreach ($pendientes as $t): ?>
                    <li class="task" draggable="true" data-id="<?= (int)$t['id'] ?>">
                        <div class="task-title"><?= htmlspecialchars($t['titulo'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="task-footer">
                            <span><?= htmlspecialchars(substr($t['creada_en'], 0, 16), ENT_QUOTES, 'UTF-8') ?></span>
                            <div class="task-actions">
                                <button class="task-action-btn" onclick="deleteTask(this, event)">🗑️</button>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
            <button class="add-task-btn" onclick="toggleAddTaskForm(this)">+ Añadir una tarea</button>
            <div class="add-task-form">
                <textarea class="task-input" placeholder="Introduce un título para esta tarea..."></textarea>
                <div class="form-actions">
                    <button class="add-btn" onclick="addTask(this)">Añadir</button>
                    <button class="cancel-btn" onclick="toggleAddTaskForm(this)">Cancelar</button>
                </div>
            </div>
        </div>

        <!-- Columna En Progreso -->
        <div class="column" id="progress-column" data-estado="progreso">
            <div class="column-header">
                <h2 class="column-title">En Progreso</h2>
                <span class="task-count" id="progress-count">0</span>
            </div>
            <ul class="task-list" id="progress-tasks">
                <?php foreach ($progreso as $t): ?>
                    <li class="task" draggable="true" data-id="<?= (int)$t['id'] ?>">
                        <div class="task-title"><?= htmlspecialchars($t['titulo'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="task-footer">
                            <span><?= htmlspecialchars(substr($t['creada_en'], 0, 16), ENT_QUOTES, 'UTF-8') ?></span>
                            <div class="task-actions">
                                <button class="task-action-btn" onclick="deleteTask(this, event)">🗑️</button>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
            <button class="add-task-btn" onclick="toggleAddTaskForm(this)">+ Añadir una tarea</button>
            <div class="add-task-form">
                <textarea class="task-input" placeholder="Introduce un título para esta tarea..."></textarea>
                <div class="form-actions">
                    <button class="add-btn" onclick="addTask(this)">Añadir</button>
                    <button class="cancel-btn" onclick="toggleAddTaskForm(this)">Cancelar</button>
                </div>
            </div>
        </div>

        <!-- Columna Completadas -->
        <div class="column" id="completed-column" data-estado="completada">
            <div class="column-header">
                <h2 class="column-title">Completadas</h2>
                <span class="task-count" id="completed-count">0</span>
            </div>
            <ul class="task-list" id="completed-tasks">
                <?php foreach ($completadas as $t): ?>
                    <li class="task" draggable="true" data-id="<?= (int)$t['id'] ?>">
                        <div class="task-title"><?= htmlspecialchars($t['titulo'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="task-footer">
                            <span><?= htmlspecialchars(substr($t['creada_en'], 0, 16), ENT_QUOTES, 'UTF-8') ?></span>
                            <div class="task-actions">
                                <button class="task-action-btn" onclick="deleteTask(this, event)">🗑️</button>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
            <button class="add-task-btn" onclick="toggleAddTaskForm(this)">+ Añadir una tarea</button>
            <div class="add-task-form">
                <textarea class="task-input" placeholder="Introduce un título para esta tarea..."></textarea>
                <div class="form-actions">
                    <button class="add-btn" onclick="addTask(this)">Añadir</button>
                    <button class="cancel-btn" onclick="toggleAddTaskForm(this)">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let draggedTask = null;

        document.addEventListener('DOMContentLoaded', function() {
            const tasks = document.querySelectorAll('.task');
            const columns = document.querySelectorAll('.column');

            tasks.forEach(task => {
                task.addEventListener('dragstart', handleDragStart);
                task.addEventListener('dragend', handleDragEnd);
            });

            columns.forEach(column => {
                column.addEventListener('dragover', handleDragOver);
                column.addEventListener('dragenter', handleDragEnter);
                column.addEventListener('dragleave', handleDragLeave);
                column.addEventListener('drop', handleDrop);
            });

            updateTaskCounters();
        });

        function handleDragStart(e) {
            draggedTask = this;
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        }

        function handleDragEnd() {
            this.classList.remove('dragging');
            document.querySelectorAll('.column').forEach(column => {
                column.classList.remove('drag-over');
            });
        }

        function handleDragOver(e) {
            e.preventDefault();
        }

        function handleDragEnter() {
            this.classList.add('drag-over');
        }

        function handleDragLeave() {
            this.classList.remove('drag-over');
        }

        function handleDrop(e) {
            e.preventDefault();
            this.classList.remove('drag-over');

            if (!draggedTask) return;

            const taskList = this.querySelector('.task-list');
            const estado   = this.dataset.estado;
            const id       = draggedTask.dataset.id;

            // Mover en el DOM
            draggedTask.parentNode.removeChild(draggedTask);
            taskList.appendChild(draggedTask);
            updateTaskCounters();

            // Actualizar en BD
            fetch('tareas_api.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    accion: 'cambiar_estado',
                    id: id,
                    estado: estado
                })
            }).then(r => r.json()).then(data => {
                if (!data.ok) {
                    alert('No se pudo actualizar la tarea: ' + (data.msg || 'Error'));
                }
            }).catch(err => {
                console.error(err);
            });
        }

        function toggleAddTaskForm(buttonOrCancel) {
            let form, button;

            if (buttonOrCancel.classList.contains('add-task-btn')) {
                button = buttonOrCancel;
                form = buttonOrCancel.nextElementSibling;
            } else {
                form = buttonOrCancel.parentNode.parentNode;
                button = form.previousElementSibling;
            }

            const isVisible = form.style.display === 'flex';

            // Ocultar todos
            document.querySelectorAll('.add-task-form').forEach(f => f.style.display = 'none');
            document.querySelectorAll('.add-task-btn').forEach(b => b.style.display = 'block');

            if (!isVisible) {
                form.style.display = 'flex';
                button.style.display = 'none';
                form.querySelector('.task-input').focus();
            }
        }

        function addTask(button) {
            const form   = button.parentNode.parentNode;
            const input  = form.querySelector('.task-input');
            const texto  = input.value.trim();
            if (texto === '') return;

            const column = form.parentNode;
            const lista  = column.querySelector('.task-list');
            const estado = column.dataset.estado;

            // Guardar en BD
            fetch('tareas_api.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    accion: 'crear',
                    titulo: texto,
                    estado: estado
                })
            })
            .then(r => r.json())
            .then(data => {
                if (!data.ok) {
                    alert('No se pudo crear la tarea: ' + (data.msg || 'Error'));
                    return;
                }

                const li = document.createElement('li');
                li.className = 'task';
                li.draggable = true;
                li.dataset.id = data.id;
                li.innerHTML = `
                    <div class="task-title">${texto}</div>
                    <div class="task-footer">
                        <span>Nueva</span>
                        <div class="task-actions">
                            <button class="task-action-btn" onclick="deleteTask(this, event)">🗑️</button>
                        </div>
                    </div>
                `;

                li.addEventListener('dragstart', handleDragStart);
                li.addEventListener('dragend', handleDragEnd);

                lista.prepend(li);
                input.value = '';
                updateTaskCounters();
                toggleAddTaskForm(form.previousElementSibling);
            })
            .catch(err => {
                console.error(err);
            });
        }

        function deleteTask(btn, ev) {
            ev.stopPropagation();
            const task = btn.closest('.task');
            const id   = task.dataset.id;

            if (!confirm('¿Eliminar esta tarea?')) return;

            fetch('tareas_api.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    accion: 'eliminar',
                    id: id
                })
            })
            .then(r => r.json())
            .then(data => {
                if (!data.ok) {
                    alert('No se pudo borrar la tarea: ' + (data.msg || 'Error'));
                    return;
                }
                task.parentNode.removeChild(task);
                updateTaskCounters();
            })
            .catch(err => console.error(err));
        }

        function updateTaskCounters() {
            document.getElementById('pending-count').textContent =
                document.getElementById('pending-tasks').children.length;

            document.getElementById('progress-count').textContent =
                document.getElementById('progress-tasks').children.length;

            document.getElementById('completed-count').textContent =
                document.getElementById('completed-tasks').children.length;
        }
    </script>
</body>
</html>
