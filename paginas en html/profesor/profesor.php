<?php
session_start();
include '../conexion.php';

// Proteger ruta: solo profesores
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'profesor') {
    header("Location: ../login.php");
    exit;
}

// Actualizar estado de asistencia manualmente si el profesor lo cambia en la tabla
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado'])) {
    $usuario_id   = intval($_POST['usuario_id']);
    $nuevo_estado = $conexion->real_escape_string($_POST['nuevo_estado']);
    $hoy          = date('Y-m-d');

    $existe = $conexion->query("SELECT id FROM asistencias WHERE usuario_id = $usuario_id AND fecha = '$hoy'");
    if ($existe && $existe->num_rows > 0) {
        $conexion->query("UPDATE asistencias SET estado = '$nuevo_estado' WHERE usuario_id = $usuario_id AND fecha = '$hoy'");
    } else {
        $hora_actual = date('H:i:s');
        $conexion->query("INSERT INTO asistencias (usuario_id, fecha, hora, estado) VALUES ($usuario_id, '$hoy', '$hora_actual', '$nuevo_estado')");
    }
    header("Location: profesor.php?grupo=" . urlencode($_GET['grupo'] ?? 'todos'));
    exit;
}

// Filtro de grupo
$grupo_filtro = isset($_GET['grupo']) ? $conexion->real_escape_string($_GET['grupo']) : 'todos';
$hoy = date('Y-m-d');

$condicion_grupo = ($grupo_filtro !== 'todos') ? "AND u.grupo = '$grupo_filtro'" : "";

// Consultar alumnos y su asistencia del día de hoy
$sql = "SELECT u.id, u.id_huella, u.codigo, u.nombre, u.grupo, a.estado, a.hora
        FROM usuarios u
        LEFT JOIN asistencias a ON u.id = a.usuario_id AND a.fecha = '$hoy'
        WHERE u.rol = 'alumno' $condicion_grupo
        ORDER BY u.grupo ASC, u.nombre ASC";
$resultado = $conexion->query($sql);

$alumnos = [];
$total_lista = 0;
$total_presentes = 0;
$total_faltas = 0;

if ($resultado) {
    while ($fila = $resultado->fetch_assoc()) {
        $total_lista++;
        $estado_actual = $fila['estado'] ?? 'Falta';
        if ($estado_actual === 'Asistencia' || $estado_actual === 'Presente' || $estado_actual === 'Retardo') {
            $total_presentes++;
        } else {
            $total_faltas++;
        }
        $fila['estado_final'] = $estado_actual;
        $alumnos[] = $fila;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ffffff">
    <title>SiBCAE | Panel de Asistencia</title>
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
                <a class="nav-link active" href="profesor.php" aria-current="page">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 4.5h8a2 2 0 0 1 2 2v13H6v-13a2 2 0 0 1 2-2Z"/><path d="M9 4.5v-1h6v1M9 10h6M9 14h6M9 18h3"/></svg>
                    <span>Lista de asistencia</span>
                </a>
                <a class="nav-link" href="gestion-alumnos.php">
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
                    <a href="../logout.php" class="logout-btn" title="Cerrar sesión" aria-label="Cerrar sesión">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                    </a>
                </div>   
            </div>
        </aside>

        <main class="main-area" id="inicio">
            <header class="topbar">
                <div class="breadcrumb"><span>SiBCAE</span><span class="crumb-divider">/</span><strong>Panel de Asistencia (<?php echo date('d/m/Y'); ?>)</strong></div>
                <div class="topbar-actions">
                    <button class="icon-button notification-button" type="button" aria-label="Notificaciones">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-2.5 7-2.5 9h17C20.5 16 18 16 18 9ZM10 21h4"/></svg><i></i>
                    </button>
                    <a href="../logout.php" class="topbar-logout" title="Cerrar sesión">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                        <span>Cerrar sesión</span>
                    </a>
                </div>
            </header>

            <section class="content" id="asistencia">
                <div class="page-heading">
                    <div>
                        <p class="eyebrow">CONTROL ESCOLAR</p>
                        <h1>Asistencia en Tiempo Real</h1>
                        <p class="subtitle">Consulta y organiza la asistencia de tu grupo.</p>
                    </div>
                    <span class="live-badge"><span></span> Panel en vivo</span>
                </div>

                <section class="attendance-card" aria-labelledby="attendance-title">
                    <div class="card-heading">
                        <div>
                            <h2 id="attendance-title">Registro de asistencia</h2>
                            <p>Selecciona un grupo para filtrar o edita el estado directamente.</p>
                        </div>
                        <button class="export-button" type="button" onclick="exportarCSV()">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m-4-4 4 4 4-4M5 17v3h14v-3"/></svg>
                            Exportar CSV
                        </button>
                    </div>

                    <div class="filters" aria-label="Filtros de asistencia">
                        <form method="GET" action="profesor.php" style="display:flex; gap:12px; flex-wrap:wrap;">
                            <label class="filter-field"><span>Grupo</span>
                                <select name="grupo" onchange="this.form.submit()" aria-label="Seleccionar grupo">
                                    <option value="todos" <?php if($grupo_filtro==='todos') echo 'selected'; ?>>Todos los grupos</option>
                                    <option value="1°A" <?php if($grupo_filtro==='1°A') echo 'selected'; ?>>1°A (Matutino)</option>
                                    <option value="1°B" <?php if($grupo_filtro==='1°B') echo 'selected'; ?>>1°B (Matutino)</option>
                                    <option value="2°A" <?php if($grupo_filtro==='2°A') echo 'selected'; ?>>2°A (Matutino)</option>
                                </select>
                            </label>
                        </form>
                        <label class="search-field"><span class="visually-hidden">Buscar alumno</span>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.3"/><path d="m15.5 15.5 4 4"/></svg>
                            <input type="search" id="buscador-asistencia" placeholder="Buscar alumno..." aria-label="Buscar alumno" onkeyup="filtrarTabla()">
                        </label>
                    </div>

                    <div class="table-wrap">
                        <table id="tabla-asistencia">
                            <thead>
                                <tr>
                                    <th>ID Huella</th>
                                    <th>Código de Estudiante</th>
                                    <th>Nombre completo</th>
                                    <th>Grupo</th>
                                    <th>Hora</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($alumnos) === 0): ?>
                                    <tr><td colspan="6" style="text-align:center; padding:20px;">No hay alumnos registrados en este grupo.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($alumnos as $al): 
                                        $est = $al['estado_final'];
                                        $clase_badge = ($est === 'Asistencia' || $est === 'Presente') ? 'present' : (($est === 'Retardo') ? 'late' : 'absent');
                                    ?>
                                    <tr>
                                        <td class="code">#<?php echo htmlspecialchars($al['id_huella']); ?></td>
                                        <td class="code"><?php echo htmlspecialchars($al['codigo']); ?></td>
                                        <td class="student-name"><?php echo htmlspecialchars($al['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($al['grupo'] ?? '1°A'); ?></td>
                                        <td class="code"><?php echo $al['hora'] ? substr($al['hora'], 0, 5) : '--:--'; ?></td>
                                        <td>
                                            <form method="POST" action="profesor.php?grupo=<?php echo urlencode($grupo_filtro); ?>" style="display:inline;">
                                                <input type="hidden" name="cambiar_estado" value="1">
                                                <input type="hidden" name="usuario_id" value="<?php echo $al['id']; ?>">
                                                <select name="nuevo_estado" onchange="this.form.submit()" class="status <?php echo $clase_badge; ?>" style="border:none; cursor:pointer; font-weight:600; padding:4px 8px; border-radius:6px;">
                                                    <option value="Asistencia" <?php if($est==='Asistencia'||$est==='Presente') echo 'selected'; ?>>Presente</option>
                                                    <option value="Retardo" <?php if($est==='Retardo') echo 'selected'; ?>>Retardo</option>
                                                    <option value="Falta" <?php if($est==='Falta') echo 'selected'; ?>>Falta</option>
                                                    <option value="Justificado" <?php if($est==='Justificado') echo 'selected'; ?>>Justificado</option>
                                                </select>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        <span>Actualizado con base de datos SiBCAE</span>
                    </div>
                </section>

                <div class="summary-row" aria-label="Resumen de la lista">
                    <div class="summary-card"><span class="summary-icon students-icon" aria-hidden="true">↗</span><div><span>Alumnos en lista</span><strong><?php echo str_pad($total_lista, 2, '0', STR_PAD_LEFT); ?></strong></div></div>
                    <div class="summary-card"><span class="summary-icon present-icon" aria-hidden="true">✓</span><div><span>Presentes</span><strong><?php echo str_pad($total_presentes, 2, '0', STR_PAD_LEFT); ?></strong></div></div>
                    <div class="summary-card"><span class="summary-icon absent-icon" aria-hidden="true">!</span><div><span>Faltas</span><strong><?php echo str_pad($total_faltas, 2, '0', STR_PAD_LEFT); ?></strong></div></div>
                </div>
            </section>
        </main>
    </div>

    <script>
        function filtrarTabla() {
            const texto = document.getElementById('buscador-asistencia').value.toLowerCase();
            document.querySelectorAll('#tabla-asistencia tbody tr').forEach(fila => {
                fila.style.display = fila.textContent.toLowerCase().includes(texto) ? '' : 'none';
            });
        }

        function exportarCSV() {
            let csv = [];
            document.querySelectorAll('#tabla-asistencia tr').forEach(row => {
                let cols = Array.from(row.querySelectorAll('th, td')).map(col => {
                    const sel = col.querySelector('select');
                    return '"' + (sel ? sel.value : col.innerText.trim()) + '"';
                });
                csv.push(cols.join(','));
            });
            const blob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'asistencia_sibcae_<?php echo date("Y-m-d"); ?>.csv';
            link.click();
        }
    </script>
</body>
</html>