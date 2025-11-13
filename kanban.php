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
    // silencio visual, pero podrías loguear el error
}

$totalPendientes  = count($pendientes);
$totalProgreso    = count($progreso);
$totalCompletadas = count($completadas);
$totalTareas      = $totalPendientes + $totalProgreso + $totalCompletadas;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tablero Kanban - Sistema de Gestión de Tareas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root{
            --bg-gradient: linear-gradient(135deg,#5b8def 0%,#7f5af0 40%,#ff6aa6 100%);
            --col-pendiente: #ffeaa7;
            --col-progreso: #74b9ff;
            --col-completada: #55efc4;

            --col-pendiente-soft: rgba(255,234,167,0.18);
            --col-progreso-soft: rgba(116,185,255,0.18);
            --col-completada-soft: rgba(85,239,196,0.18);

            --card-bg: rgba(16,18,27,0.85);
            --card-border: rgba(255,255,255,0.04);
            --text-main: #f5f7ff;
            --text-muted: #a5b4cf;
            --chip-bg: rgba(255,255,255,0.08);
            --danger: #ff7675;
        }

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        body{
            min-height:100vh;
            font-family:"Poppins",system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
            color:var(--text-main);
            background:
                radial-gradient(circle at 0% 0%, rgba(255,255,255,0.15) 0, transparent 55%),
                radial-gradient(circle at 100% 100%, rgba(0,0,0,0.35) 0, transparent 60%),
                var(--bg-gradient);
            display:flex;
            justify-content:center;
            align-items:center;
            padding:16px;
        }

        .app-shell{
            width:100%;
            max-width:1200px;
            min-height:80vh;
            background:radial-gradient(circle at 0 0, rgba(255,255,255,0.05) 0, transparent 55%),
                       radial-gradient(circle at 100% 100%, rgba(0,0,0,0.35) 0, transparent 60%),
                       rgba(10,12,24,0.95);
            border-radius:24px;
            border:1px solid rgba(255,255,255,0.12);
            box-shadow:0 32px 80px rgba(0,0,0,0.55);
            display:flex;
            flex-direction:column;
            overflow:hidden;
        }

        header{
            padding:18px 24px 10px;
            border-bottom:1px solid rgba(255,255,255,0.08);
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:16px;
        }

        .header-left{
            display:flex;
            flex-direction:column;
            gap:4px;
        }

        .subtitle{
            font-size:12px;
            text-transform:uppercase;
            letter-spacing:0.18em;
            color:var(--text-muted);
        }

        h1{
            font-size:22px;
            font-weight:700;
            display:flex;
            align-items:center;
            gap:8px;
        }

        h1 span.emoji{
            font-size:22px;
        }

        .legend{
            display:flex;
            gap:8px;
            margin-top:6px;
            flex-wrap:wrap;
        }

        .legend-item{
            display:flex;
            align-items:center;
            gap:6px;
            padding:4px 10px;
            border-radius:999px;
            background:rgba(255,255,255,0.04);
            font-size:11px;
            color:var(--text-muted);
        }

        .legend-dot{
            width:10px;
            height:10px;
            border-radius:50%;
        }

        .legend-dot.pendiente{ background:var(--col-pendiente); }
        .legend-dot.progreso{ background:var(--col-progreso); }
        .legend-dot.completada{ background:var(--col-completada); }

        .user-info{
            display:flex;
            align-items:center;
            gap:10px;
        }

        .user-meta{
            display:flex;
            flex-direction:column;
            gap:2px;
            text-align:right;
        }

        .user-meta span:first-child{
            font-size:13px;
            color:var(--text-muted);
        }

        .user-meta span:last-child{
            font-size:12px;
            color:var(--text-muted);
        }

        .user-avatar{
            width:40px;
            height:40px;
            border-radius:50%;
            background:conic-gradient(from 180deg at 50% 50%,#ff6aa6,#ffbe76,#74b9ff,#a29bfe,#ff6aa6);
            padding:2px;
            display:flex;
            align-items:center;
            justify-content:center;
        }

        .user-avatar-inner{
            width:100%;
            height:100%;
            border-radius:50%;
            background:#0f1222;
            display:flex;
            align-items:center;
            justify-content:center;
            font-weight:600;
        }

        .logout-btn{
            background:rgba(255,255,255,0.06);
            border:1px solid rgba(255,255,255,0.12);
            padding:6px 12px;
            border-radius:999px;
            color:var(--text-muted);
            cursor:pointer;
            text-decoration:none;
            font-size:12px;
            display:flex;
            align-items:center;
            gap:6px;
            transition:all .18s ease;
        }

        .logout-btn:hover{
            background:rgba(255,90,90,0.12);
            border-color:rgba(255,107,107,0.85);
            color:#ffbfbf;
            transform:translateY(-1px);
        }

        .logout-btn span.icon{
            font-size:14px;
        }

        .stats-bar{
            padding:6px 24px 12px;
            display:flex;
            flex-wrap:wrap;
            gap:12px;
            border-bottom:1px solid rgba(255,255,255,0.06);
        }

        .stat-chip{
            padding:6px 10px;
            border-radius:999px;
            background:rgba(255,255,255,0.03);
            border:1px solid rgba(255,255,255,0.08);
            font-size:11px;
            color:var(--text-muted);
            display:flex;
            align-items:center;
            gap:6px;
        }

        .stat-pill{
            padding:2px 7px;
            border-radius:999px;
            font-weight:600;
            font-size:11px;
            color:#111;
        }

        .pill-pendiente{
            background:var(--col-pendiente);
        }

        .pill-progreso{
            background:var(--col-progreso);
            color:#0c1a33;
        }

        .pill-completada{
            background:var(--col-completada);
        }

        .pill-total{
            background:#ffeaa7;
        }

        .board-container{
            flex:1;
            display:flex;
            gap:16px;
            padding:18px 18px 20px;
            overflow-x:auto;
        }

        .column{
            background:var(--card-bg);
            border-radius:18px;
            padding:14px 12px 10px;
            min-width:260px;
            max-width:340px;
            display:flex;
            flex-direction:column;
            border:1px solid var(--card-border);
            backdrop-filter:blur(18px);
            position:relative;
            overflow:hidden;
        }

        .column::before{
            content:'';
            position:absolute;
            inset:0;
            opacity:0.7;
            pointer-events:none;
            background:
                radial-gradient(circle at 0% 0%, rgba(255,255,255,0.10) 0, transparent 55%),
                radial-gradient(circle at 100% 100%, rgba(0,0,0,0.45) 0, transparent 60%);
            mix-blend-mode:soft-light;
        }

        .column-inner{
            position:relative;
            display:flex;
            flex-direction:column;
            gap:10px;
            height:100%;
        }

        .column-header{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:4px;
        }

        .column-title-wrap{
            display:flex;
            align-items:center;
            gap:8px;
        }

        .column-icon{
            width:26px;
            height:26px;
            border-radius:12px;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:16px;
        }

        .column-title{
            font-size:14px;
            font-weight:600;
            letter-spacing:0.03em;
            text-transform:uppercase;
        }

        .column-hint{
            font-size:11px;
            color:var(--text-muted);
        }

        .task-count{
            font-size:11px;
            padding:4px 8px;
            border-radius:999px;
            background:rgba(255,255,255,0.05);
            border:1px solid rgba(255,255,255,0.08);
        }

        .task-list{
            list-style:none;
            overflow-y:auto;
            flex-grow:1;
            padding:2px 2px 4px;
            display:flex;
            flex-direction:column;
            gap:8px;
        }

        .task{
            background:rgba(14,18,34,0.92);
            border-radius:12px;
            padding:10px 10px 8px;
            box-shadow:0 6px 18px rgba(0,0,0,0.45);
            cursor:pointer;
            transition:transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
            position:relative;
        }

        .task::before{
            content:'';
            position:absolute;
            inset:-1px;
            border-radius:inherit;
            border:1px solid rgba(255,255,255,0.03);
            pointer-events:none;
        }

        .task:hover{
            transform:translateY(-2px);
            box-shadow:0 10px 26px rgba(0,0,0,0.6);
            background:rgba(18,23,46,0.98);
        }

        .task-title{
            font-size:13px;
            font-weight:500;
            margin-bottom:6px;
            color:#f9fbff;
        }

        .task-footer{
            display:flex;
            justify-content:space-between;
            align-items:center;
            font-size:11px;
            color:var(--text-muted);
            gap:6px;
        }

        .task-tags{
            display:flex;
            align-items:center;
            gap:6px;
        }

        .tag{
            padding:2px 6px;
            border-radius:999px;
            background:var(--chip-bg);
            border:1px solid rgba(255,255,255,0.10);
            display:flex;
            align-items:center;
            gap:4px;
        }

        .tag-dot{
            width:7px;
            height:7px;
            border-radius:50%;
        }

        .task-actions{
            display:flex;
            gap:4px;
            opacity:0;
            transition:opacity .16s ease;
        }

        .task:hover .task-actions{
            opacity:1;
        }

        .task-action-btn{
            background:transparent;
            border:none;
            color:var(--text-muted);
            cursor:pointer;
            padding:4px;
            border-radius:999px;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:13px;
        }

        .task-action-btn:hover{
            background:rgba(255,255,255,0.08);
            color:#fff;
        }

        .add-task-btn{
            background:rgba(255,255,255,0.02);
            border:1px dashed rgba(255,255,255,0.16);
            color:var(--text-muted);
            padding:7px 9px;
            border-radius:999px;
            cursor:pointer;
            text-align:left;
            width:100%;
            display:flex;
            align-items:center;
            gap:6px;
            font-size:12px;
            transition:all .15s ease;
        }

        .add-task-btn span.icon{
            font-size:14px;
        }

        .add-task-btn:hover{
            background:rgba(255,255,255,0.06);
            color:#ffffff;
            border-style:solid;
        }

        .add-task-form{
            display:none;
            flex-direction:column;
            gap:8px;
            margin-top:6px;
            background:rgba(6,10,25,0.9);
            padding:8px;
            border-radius:12px;
            border:1px solid rgba(255,255,255,0.08);
        }

        .task-input{
            border:none;
            border-radius:10px;
            padding:7px 9px;
            font-size:12px;
            resize:none;
            min-height:60px;
            background:rgba(20,25,46,0.96);
            color:#f5f6ff;
        }

        .task-input::placeholder{
            color:rgba(255,255,255,0.42);
        }

        .task-input:focus{
            outline:none;
            box-shadow:0 0 0 1px rgba(116,185,255,0.8);
        }

        .form-actions{
            display:flex;
            gap:8px;
            justify-content:flex-end;
        }

        .add-btn{
            background:linear-gradient(135deg,#74b9ff,#a29bfe);
            color:#0a1020;
            border:none;
            border-radius:999px;
            padding:6px 12px;
            font-size:11px;
            font-weight:600;
            cursor:pointer;
            box-shadow:0 5px 14px rgba(72,149,239,0.45);
        }

        .cancel-btn{
            background:transparent;
            border:none;
            color:var(--text-muted);
            padding:6px 10px;
            border-radius:999px;
            font-size:11px;
            cursor:pointer;
        }

        .drag-over{
            box-shadow:0 0 0 1px rgba(116,185,255,0.7);
        }

        .dragging{
            opacity:0.5;
        }

        /* Scrolls */
        .task-list::-webkit-scrollbar{
            width:6px;
        }
        .task-list::-webkit-scrollbar-track{
            background:transparent;
        }
        .task-list::-webkit-scrollbar-thumb{
            background:rgba(255,255,255,0.12);
            border-radius:999px;
        }

        @media (max-width:900px){
            .app-shell{
                min-height:95vh;
            }
            header{
                flex-direction:column;
                align-items:flex-start;
            }
            .user-info{
                align-self:flex-end;
            }
        }

        @media (max-width:700px){
            body{
                padding:8px;
            }
            .app-shell{
                border-radius:18px;
            }
            header{
                padding:14px 16px 8px;
            }
            .stats-bar{
                padding:6px 16px 10px;
            }
            .board-container{
                padding:12px 10px 14px;
            }
            .column{
                min-width:86vw;
            }
        }

        @media (max-width:480px){
            h1{
                font-size:19px;
            }
            .legend{
                display:none;
            }
            .user-meta span:last-child{
                display:none;
            }
        }
    </style>
</head>
<body>
<div class="app-shell">
    <header>
        <div class="header-left">
            <span class="subtitle">Tablero personal</span>
            <h1><span class="emoji">📌</span> Kanban de tareas</h1>
            <div class="legend">
                <div class="legend-item">
                    <span class="legend-dot pendiente"></span> Pendiente
                </div>
                <div class="legend-item">
                    <span class="legend-dot progreso"></span> En progreso
                </div>
                <div class="legend-item">
                    <span class="legend-dot completada"></span> Completada
                </div>
            </div>
        </div>

        <div class="user-info">
            <div class="user-meta">
                <span>Hola, <?= htmlspecialchars($usuario, ENT_QUOTES, 'UTF-8') ?></span>
                <span><?= $totalTareas ?> tareas en total</span>
            </div>
            <div class="user-avatar">
                <div class="user-avatar-inner"><?= htmlspecialchars($inicial, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <a href="logout.php" class="logout-btn">
                <span class="icon">↩</span>
                <span>Salir</span>
            </a>
        </div>
    </header>

    <div class="stats-bar">
        <div class="stat-chip">
            Pendientes:
            <span class="stat-pill pill-pendiente" id="chip-pendientes"><?= $totalPendientes ?></span>
        </div>
        <div class="stat-chip">
            En progreso:
            <span class="stat-pill pill-progreso" id="chip-progreso"><?= $totalProgreso ?></span>
        </div>
        <div class="stat-chip">
            Completadas:
            <span class="stat-pill pill-completada" id="chip-completadas"><?= $totalCompletadas ?></span>
        </div>
        <div class="stat-chip">
            Total:
            <span class="stat-pill pill-total" id="chip-total"><?= $totalTareas ?></span>
        </div>
    </div>

    <div class="board-container">
        <!-- Columna Pendientes -->
        <div class="column" id="pending-column" data-estado="pendiente">
            <div class="column-inner">
                <div class="column-header">
                    <div class="column-title-wrap">
                        <div class="column-icon" style="background:var(--col-pendiente-soft);">
                            🧠
                        </div>
                            <div>
                                <div class="column-title">Pendientes</div>
                                <div class="column-hint">Ideas y tareas por iniciar</div>
                            </div>
                    </div>
                    <span class="task-count" id="pending-count">0</span>
                </div>
                <ul class="task-list" id="pending-tasks">
                    <?php foreach ($pendientes as $t): ?>
                        <li class="task" draggable="true" data-id="<?= (int)$t['id'] ?>">
                            <div class="task-title">
                                <?= htmlspecialchars($t['titulo'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <div class="task-footer">
                                <div class="task-tags">
                                    <div class="tag">
                                        <span class="tag-dot" style="background:var(--col-pendiente);"></span>
                                        <span>Por hacer</span>
                                    </div>
                                </div>
                                <div class="task-actions">
                                    <button class="task-action-btn" onclick="deleteTask(this,event)" title="Eliminar">🗑️</button>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <button class="add-task-btn" onclick="toggleAddTaskForm(this)">
                    <span class="icon">＋</span> Añadir tarea pendiente
                </button>
                <div class="add-task-form">
                    <textarea class="task-input" placeholder="Ej: Preparar informe de avances..."></textarea>
                    <div class="form-actions">
                        <button class="cancel-btn" onclick="toggleAddTaskForm(this)">Cancelar</button>
                        <button class="add-btn" onclick="addTask(this)">Añadir</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna En Progreso -->
        <div class="column" id="progress-column" data-estado="progreso">
            <div class="column-inner">
                <div class="column-header">
                    <div class="column-title-wrap">
                        <div class="column-icon" style="background:var(--col-progreso-soft);">
                            🚧
                        </div>
                        <div>
                            <div class="column-title">En progreso</div>
                            <div class="column-hint">Lo que estás trabajando ahora</div>
                        </div>
                    </div>
                    <span class="task-count" id="progress-count">0</span>
                </div>
                <ul class="task-list" id="progress-tasks">
                    <?php foreach ($progreso as $t): ?>
                        <li class="task" draggable="true" data-id="<?= (int)$t['id'] ?>">
                            <div class="task-title">
                                <?= htmlspecialchars($t['titulo'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <div class="task-footer">
                                <div class="task-tags">
                                    <div class="tag">
                                        <span class="tag-dot" style="background:var(--col-progreso);"></span>
                                        <span>En curso</span>
                                    </div>
                                </div>
                                <div class="task-actions">
                                    <button class="task-action-btn" onclick="deleteTask(this,event)" title="Eliminar">🗑️</button>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <button class="add-task-btn" onclick="toggleAddTaskForm(this)">
                    <span class="icon">＋</span> Añadir tarea en progreso
                </button>
                <div class="add-task-form">
                    <textarea class="task-input" placeholder="¿Qué estás haciendo ahora mismo?"></textarea>
                    <div class="form-actions">
                        <button class="cancel-btn" onclick="toggleAddTaskForm(this)">Cancelar</button>
                        <button class="add-btn" onclick="addTask(this)">Añadir</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna Completadas -->
        <div class="column" id="completed-column" data-estado="completada">
            <div class="column-inner">
                <div class="column-header">
                    <div class="column-title-wrap">
                        <div class="column-icon" style="background:var(--col-completada-soft);">
                            ✅
                        </div>
                        <div>
                            <div class="column-title">Completadas</div>
                            <div class="column-hint">Todo lo que ya lograste</div>
                        </div>
                    </div>
                    <span class="task-count" id="completed-count">0</span>
                </div>
                <ul class="task-list" id="completed-tasks">
                    <?php foreach ($completadas as $t): ?>
                        <li class="task" draggable="true" data-id="<?= (int)$t['id'] ?>">
                            <div class="task-title">
                                <?= htmlspecialchars($t['titulo'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <div class="task-footer">
                                <div class="task-tags">
                                    <div class="tag">
                                        <span class="tag-dot" style="background:var(--col-completada);"></span>
                                        <span>Hecho</span>
                                    </div>
                                </div>
                                <div class="task-actions">
                                    <button class="task-action-btn" onclick="deleteTask(this,event)" title="Eliminar">🗑️</button>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <button class="add-task-btn" onclick="toggleAddTaskForm(this)">
                    <span class="icon">＋</span> Añadir tarea completada
                </button>
                <div class="add-task-form">
                    <textarea class="task-input" placeholder="Registra logros para ver tu progreso 😎"></textarea>
                    <div class="form-actions">
                        <button class="cancel-btn" onclick="toggleAddTaskForm(this)">Cancelar</button>
                        <button class="add-btn" onclick="addTask(this)">Añadir</button>
                    </div>
                </div>
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

        draggedTask.parentNode.removeChild(draggedTask);
        taskList.prepend(draggedTask);
        updateTaskCounters();

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
        }).catch(console.error);
    }

    function toggleAddTaskForm(buttonOrCancel) {
        let form, button;

        if (buttonOrCancel.classList.contains('add-task-btn')) {
            button = buttonOrCancel;
            form = buttonOrCancel.nextElementSibling;
        } else {
            form = buttonOrCancel.closest('.add-task-form');
            button = form.previousElementSibling;
        }

        const isVisible = form.style.display === 'flex';

        document.querySelectorAll('.add-task-form').forEach(f => f.style.display = 'none');
        document.querySelectorAll('.add-task-btn').forEach(b => b.style.display = 'flex');

        if (!isVisible) {
            form.style.display = 'flex';
            button.style.display = 'none';
            form.querySelector('.task-input').focus();
        }
    }

    function addTask(button) {
        const form   = button.closest('.add-task-form');
        const input  = form.querySelector('.task-input');
        const texto  = input.value.trim();
        if (texto === '') return;

        const column = form.closest('.column');
        const lista  = column.querySelector('.task-list');
        const estado = column.dataset.estado;

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
                const colorMap = {
                    pendiente: 'var(--col-pendiente)',
                    progreso: 'var(--col-progreso)',
                    completada: 'var(--col-completada)'
                };
                const labelMap = {
                    pendiente: 'Por hacer',
                    progreso: 'En curso',
                    completada: 'Hecho'
                };

                li.innerHTML = `
                    <div class="task-title">${texto}</div>
                    <div class="task-footer">
                        <div class="task-tags">
                            <div class="tag">
                                <span class="tag-dot" style="background:${colorMap[estado]};"></span>
                                <span>${labelMap[estado]}</span>
                            </div>
                        </div>
                        <div class="task-actions">
                            <button class="task-action-btn" onclick="deleteTask(this,event)" title="Eliminar">🗑️</button>
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
            .catch(console.error);
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
            .catch(console.error);
    }

    function updateTaskCounters() {
        const pending = document.getElementById('pending-tasks').children.length;
        const prog    = document.getElementById('progress-tasks').children.length;
        const comp    = document.getElementById('completed-tasks').children.length;

        document.getElementById('pending-count').textContent   = pending;
        document.getElementById('progress-count').textContent  = prog;
        document.getElementById('completed-count').textContent = comp;

        document.getElementById('chip-pendientes').textContent = pending;
        document.getElementById('chip-progreso').textContent   = prog;
        document.getElementById('chip-completadas').textContent= comp;
        document.getElementById('chip-total').textContent      = pending + prog + comp;
    }
</script>
</body>
</html>
