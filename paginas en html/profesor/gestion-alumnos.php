<?php
session_start();
include '../conexion.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'profesor') {
    header("Location: ../login.php");
    exit;
}

$mensaje_alerta = "";
$tipo_alerta = "";

// 1. CREAR NUEVO ALUMNO Y ACTIVAR SENSOR AS608
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['accion'] === 'crear') {
    $id_huella = intval($_POST['id_huella']);
    $codigo    = $conexion->real_escape_string(trim($_POST['codigo']));
    $nombre    = $conexion->real_escape_string(trim($_POST['nombre']));
    $grupo     = $conexion->real_escape_string($_POST['grupo']);
    $correo    = $codigo . "@alumnos.sibcae.edu.mx";
    $password  = password_hash($codigo, PASSWORD_DEFAULT); // Contraseña inicial = su código

    $sql = "INSERT INTO usuarios (id_huella, nombre, codigo, correo, grupo, rol, password) 
            VALUES ($id_huella, '$nombre', '$codigo', '$correo', '$grupo', 'alumno', '$password')";

    if ($conexion->query($sql)) {
        // Activar modo enrolar en la ESP32 si se pidió capturar huella
        if ($_POST['estado_huella'] === 'Pendiente') {
            $conexion->query("UPDATE control_sensor SET modo = 'enrolar', id_a_registrar = $id_huella WHERE id = 1");
            $mensaje_alerta = "Alumno registrado. ¡Sensor AS608 ACTIVADO! Coloca el dedo del alumno para grabar el ID #$id_huella.";
        } else {
            $mensaje_alerta = "Alumno registrado correctamente con ID #$id_huella.";
        }
        $tipo_alerta = "ok";
    } else {
        $mensaje_alerta = "Error al registrar: " . $conexion->error;
        $tipo_alerta = "error";
    }
}

// 2. EDITAR ALUMNO EXISTENTE (Y RE-ACTIVAR SENSOR SI SE SOLICITA)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['accion'] === 'editar') {
    $id_db     = intval($_POST['id_db']);
    $id_huella = intval($_POST['id_huella']);
    $codigo    = $conexion->real_escape_string(trim($_POST['codigo']));
    $nombre    = $conexion->real_escape_string(trim($_POST['nombre']));
    $grupo     = $conexion->real_escape_string($_POST['grupo']);

    $sql = "UPDATE usuarios SET id_huella=$id_huella, codigo='$codigo', nombre='$nombre', grupo='$grupo' WHERE id=$id_db";
    if ($conexion->query($sql)) {
        if ($_POST['estado_huella'] === 'Pendiente') {
            $conexion->query("UPDATE control_sensor SET modo = 'enrolar', id_a_registrar = $id_huella WHERE id = 1");
            $mensaje_alerta = "Datos actualizados y Sensor AS608 activado para re-grabar el ID #$id_huella.";
        } else {
            $mensaje_alerta = "Datos del alumno actualizados.";
        }
        $tipo_alerta = "ok";
    }
}

// 3. ELIMINAR ALUMNO
if (isset($_GET['eliminar'])) {
    $id_eliminar = intval($_GET['eliminar']);
    $conexion->query("DELETE FROM usuarios WHERE id = $id_eliminar AND rol = 'alumno'");
    header("Location: gestion-alumnos.php");
    exit;
}

// Consultar todos los alumnos
$res_alumnos = $conexion->query("SELECT * FROM usuarios WHERE rol = 'alumno' ORDER BY id_huella ASC");
$lista_alumnos = [];
$siguiente_id = 1;

if ($res_alumnos) {
    while ($row = $res_alumnos->fetch_assoc()) {
        $lista_alumnos[] = $row;
        if ($row['id_huella'] >= $siguiente_id) {
            $siguiente_id = $row['id_huella'] + 1;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ffffff">
    <title>SiBCAE | Gestión de Alumnos</title>
    <link rel="stylesheet" href="profesor.css">
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar" aria-label="Navegación principal">
            <a class="brand" href="profesor.php" aria-label="SiBCAE, inicio">
                <img src="../SiBCAE_LogoW&B.png" alt="" class="brand-logo">
                <span>SiBCAE</span>
            </a>

            <p class="nav-caption">MENÚ PRINCIPAL</p>
            <nav class="side-nav">
                <a class="nav-link" href="profesor.php">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 4.5h8a2 2 0 0 1 2 2v13H6v-13a2 2 0 0 1 2-2Z"/><path d="M9 4.5v-1h6v1M9 10h6M9 14h6M9 18h3"/></svg>
                    <span>Lista de asistencia</span>
                </a>
                <a class="nav-link active" href="gestion-alumnos.php" aria-current="page">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3.2"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0M16 5.3a3.1 3.1 0 0 1 0 5.9M17 14a5 5 0 0 1 3.5 4.8"/></svg>
                    <span>Gestión de alumnos</span>
                </a>
            </nav>

            <div class="sidebar-bottom">
                <div class="help-card">
                    <span class="help-icon" aria-hidden="true">?</span>
                    <div><strong>¿Necesitas ayuda?</strong><span>Estamos para apoyarte</span></div>
                    <span class="help-arrow" aria-hidden="true">›</span>
                </div>
                <div class="profile">
                    <div class="profile-copy">
                        <strong><?php echo htmlspecialchars($_SESSION['nombre']); ?></strong>
                        <span>Docente</span>
                    </div>
                    <a href="../logout.php" class="logout-btn" title="Cerrar sesión">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                    </a>
                </div>
            </div>
        </aside>

        <main class="main-area" id="inicio">
            <header class="topbar">
                <div class="breadcrumb"><span>SiBCAE</span><span class="crumb-divider">/</span><strong>Gestión de Alumnos</strong></div>
                <div class="topbar-actions">
                    <a href="../logout.php" class="topbar-logout" title="Cerrar sesión">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                        <span>Cerrar sesión</span>
                    </a>
                </div>
            </header>

            <section class="content">
                <?php if ($mensaje_alerta): ?>
                    <div style="padding:14px 18px; border-radius:10px; margin-bottom:20px; font-weight:600; background:<?php echo $tipo_alerta==='ok' ? '#dcfce7' : '#fee2e2'; ?>; color:<?php echo $tipo_alerta==='ok' ? '#15803d' : '#b91c1c'; ?>;">
                        <?php echo htmlspecialchars($mensaje_alerta); ?>
                    </div>
                <?php endif; ?>

                <div class="page-heading">
                    <div>
                        <p class="eyebrow">PADRÓN BIOMÉTRICO</p>
                        <h1>Gestión de Alumnos</h1>
                        <p class="subtitle">Registra nuevos estudiantes, vincula su ID de huella o actualiza sus datos.</p>
                    </div>
                    <button type="button" class="btn-primary-sibcae btn-auto" id="btn-open-add">
                        + Nuevo Alumno
                    </button>
                </div>

                <section class="attendance-card" aria-labelledby="crud-title">
                    <div class="card-heading">
                        <div>
                            <h2 id="crud-title">Directorio de estudiantes</h2>
                            <p>Al registrar un alumno con huella pendiente, el sensor AS608 se activará automáticamente.</p>
                        </div>
                    </div>

                    <div class="filters" aria-label="Filtros de directorio">
                        <label class="filter-field">
                            <span>Grupo</span>
                            <select id="filter-group" aria-label="Filtrar por grupo" onchange="filtrarDirectorio()">
                                <option value="all">Todos los grupos</option>
                                <option value="1°A">1°A (Matutino)</option>
                                <option value="1°B">1°B (Matutino)</option>
                                <option value="2°A">2°A (Matutino)</option>
                            </select>
                        </label>

                        <label class="search-field">
                            <span class="visually-hidden">Buscar alumno</span>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.3"/><path d="m15.5 15.5 4 4"/></svg>
                            <input type="search" id="search-student" placeholder="Buscar por nombre, matrícula o huella..." onkeyup="filtrarDirectorio()">
                        </label>
                    </div>

                    <div class="table-wrap">
                        <table id="students-crud-table">
                            <thead>
                                <tr>
                                    <th>ID Huella</th>
                                    <th>Código de Estudiante</th>
                                    <th>Nombre completo</th>
                                    <th>Grupo</th>
                                    <th>Estado Huella</th>
                                    <th class="actions-col">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="students-tbody">
                                <?php foreach ($lista_alumnos as $st): ?>
                                <tr data-grupo="<?php echo htmlspecialchars($st['grupo'] ?? '1°A'); ?>">
                                    <td class="code">#<?php echo htmlspecialchars($st['id_huella']); ?></td>
                                    <td class="code"><?php echo htmlspecialchars($st['codigo']); ?></td>
                                    <td class="student-name"><?php echo htmlspecialchars($st['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($st['grupo'] ?? '1°A'); ?></td>
                                    <td><span class="status present"><span></span>Vinculada</span></td>
                                    <td class="actions-cell">
                                        <button type="button" class="table-action-btn edit-btn" 
                                            onclick="openEditModal(<?php echo $st['id']; ?>, '<?php echo $st['id_huella']; ?>', '<?php echo htmlspecialchars($st['codigo']); ?>', '<?php echo htmlspecialchars($st['nombre']); ?>', '<?php echo htmlspecialchars($st['grupo'] ?? '1°A'); ?>')">
                                            Editar
                                        </button>
                                        <a href="gestion-alumnos.php?eliminar=<?php echo $st['id']; ?>" 
                                           class="table-action-btn delete-btn" 
                                           style="text-decoration:none; display:inline-block;"
                                           onclick="return confirm('¿Seguro que deseas eliminar a <?php echo htmlspecialchars($st['nombre']); ?>?')">
                                            Eliminar
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="card-footer">
                        <span>Mostrando <?php echo count($lista_alumnos); ?> alumno(s) registrados en MySQL</span>
                    </div>
                </section>
            </section>
        </main>
    </div>

    <!-- Modal para Agregar / Editar Alumno y Activar Sensor -->
    <dialog id="student-modal" class="sibcae-modal">
        <form id="student-form" method="POST" action="gestion-alumnos.php" class="modal-box">
            <input type="hidden" name="accion" id="form-accion" value="crear">
            <input type="hidden" name="id_db" id="edit-id-db" value="0">

            <div class="card-heading">
                <div>
                    <h2 id="modal-title">Registrar Nuevo Alumno</h2>
                    <p id="modal-subtitle">Su contraseña inicial será su mismo código de estudiante.</p>
                </div>
                <button type="button" class="icon-button" id="btn-close-modal" aria-label="Cerrar ventana">✕</button>
            </div>

            <div class="modal-body">
                <div class="modal-grid-2">
                    <label class="filter-field login-input-group">
                        <span>ID Huella Biométrica (1-127)</span>
                        <input type="number" name="id_huella" id="input-huella" value="<?php echo $siguiente_id; ?>" min="1" max="127" required>
                    </label>

                    <label class="filter-field login-input-group">
                        <span>Código de Estudiante</span>
                        <input type="text" name="codigo" id="input-codigo" placeholder="Ej. 219000000" required>
                    </label>
                </div>

                <label class="filter-field login-input-group">
                    <span>Nombre completo</span>
                    <input type="text" name="nombre" id="input-nombre" placeholder="Ej. Ana Sofía Morales" required>
                </label>

                <div class="modal-grid-2">
                    <label class="filter-field">
                        <span>Grupo asignado</span>
                        <select name="grupo" id="input-grupo" required>
                            <option value="1°A">1°A (Matutino)</option>
                            <option value="1°B">1°B (Matutino)</option>
                            <option value="2°A">2°A (Matutino)</option>
                        </select>
                    </label>

                    <label class="filter-field">
                        <span>Sensor Biométrico AS608</span>
                        <select name="estado_huella" id="input-estado">
                            <option value="Pendiente">Activar sensor ahora para capturar huella</option>
                            <option value="Activa">Ya está grabada en el sensor</option>
                        </select>
                    </label>
                </div>
            </div>

            <div class="card-footer modal-footer">
                <button type="button" class="export-button" id="btn-cancel-modal">Cancelar</button>
                <button type="submit" class="btn-primary-sibcae btn-auto" id="btn-save-student">Guardar y Activar Sensor</button>
            </div>
        </form>
    </dialog>

    <script>
        const modal = document.getElementById('student-modal');
        const form = document.getElementById('student-form');

        document.getElementById('btn-open-add').addEventListener('click', () => {
            document.getElementById('form-accion').value = 'crear';
            document.getElementById('edit-id-db').value = '0';
            document.getElementById('modal-title').textContent = 'Registrar Nuevo Alumno';
            document.getElementById('btn-save-student').textContent = 'Guardar y Activar Sensor';
            form.reset();
            document.getElementById('input-huella').value = '<?php echo $siguiente_id; ?>';
            modal.showModal();
        });

        function openEditModal(idDb, huella, codigo, nombre, grupo) {
            document.getElementById('form-accion').value = 'editar';
            document.getElementById('edit-id-db').value = idDb;
            document.getElementById('modal-title').textContent = 'Editar Datos del Alumno';
            document.getElementById('btn-save-student').textContent = 'Actualizar Alumno';
            document.getElementById('input-huella').value = huella;
            document.getElementById('input-codigo').value = codigo;
            document.getElementById('input-nombre').value = nombre;
            document.getElementById('input-grupo').value = grupo;
            document.getElementById('input-estado').value = 'Activa';
            modal.showModal();
        }

        document.getElementById('btn-close-modal').addEventListener('click', () => modal.close());
        document.getElementById('btn-cancel-modal').addEventListener('click', () => modal.close());

        function filtrarDirectorio() {
            const grupo = document.getElementById('filter-group').value;
            const texto = document.getElementById('search-student').value.toLowerCase();
            document.querySelectorAll('#students-tbody tr').forEach(fila => {
                const matchGrupo = (grupo === 'all' || fila.dataset.grupo === grupo);
                const matchTexto = fila.textContent.toLowerCase().includes(texto);
                fila.style.display = (matchGrupo && matchTexto) ? '' : 'none';
            });
        }
    </script>
</body>
</html>