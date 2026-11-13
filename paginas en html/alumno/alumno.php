<?php
session_start();
include '../conexion.php';

// Proteger ruta: solo alumnos
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'alumno') {
    header("Location: ../login.php");
    exit;
}

$usuario_id = intval($_SESSION['usuario_id']);

// Obtener datos actualizados del alumno (incluyendo su grupo)
$res_user = $conexion->query("SELECT * FROM usuarios WHERE id = $usuario_id LIMIT 1");
$datos_alumno = $res_user->fetch_assoc();
$grupo_alumno = $datos_alumno['grupo'] ?? '1°A';
$id_huella    = $datos_alumno['id_huella'] ?? '0';

// Consultar historial de asistencias del alumno en la base de datos
$sql = "SELECT * FROM asistencias WHERE usuario_id = $usuario_id ORDER BY fecha DESC, hora DESC";
$res_asistencias = $conexion->query($sql);

$registros = [];
$cont_asistencias = 0;
$cont_retardos    = 0;
$cont_faltas      = 0;

if ($res_asistencias) {
    while ($fila = $res_asistencias->fetch_assoc()) {
        $estado = $fila['estado'];
        if ($estado === 'Asistencia' || $estado === 'Presente' || $estado === 'Justificado') {
            $cont_asistencias++;
        } elseif ($estado === 'Retardo') {
            $cont_retardos++;
        } else {
            $cont_faltas++;
        }
        $registros[] = $fila;
    }
}

$total_clases = $cont_asistencias + $cont_retardos + $cont_faltas;
$porcentaje = ($total_clases > 0) 
    ? number_format((($cont_asistencias + ($cont_retardos * 0.5)) / $total_clases) * 100, 1) 
    : "100.0";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ffffff">
    <title>SiBCAE | Portal del Alumno</title>
    <link rel="stylesheet" href="alumno.css">
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar" aria-label="Navegación principal">
            <a class="brand" href="alumno.php" aria-label="SiBCAE, inicio">
                <img src="../SiBCAE_LogoW&B.png" alt="" class="brand-logo">
                <span>SiBCAE</span>
            </a>

            <p class="nav-caption">PORTAL ALUMNO</p>
            <nav class="side-nav">
                <a class="nav-link active" href="alumno.php" aria-current="page">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 4.5h8a2 2 0 0 1 2 2v13H6v-13a2 2 0 0 1 2-2Z"/><path d="M9 4.5v-1h6v1M9 10h6M9 14h6M9 18h3"/></svg>
                    <span>Mis asistencias</span>
                </a>
                <a class="nav-link" href="justificantes.php">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3.5h8l4 4V20a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4.5a1 1 0 0 1 1-1Z"/><path d="M14 3.5v4h4M9 13h6M9 17h4"/></svg>
                    <span>Justificantes</span>
                </a>
            </nav>

            <div class="sidebar-bottom">
                <div class="help-card">
                    <span class="help-icon" aria-hidden="true">?</span>
                    <div><strong>¿Error en tu registro?</strong><span>Acláralo con tu docente</span></div>
                    <span class="help-arrow" aria-hidden="true">›</span>
                </div>
                <div class="profile">
                    <div class="profile-copy">
                        <strong><?php echo htmlspecialchars($_SESSION['nombre']); ?></strong>
                        <span>Matrícula: <?php echo htmlspecialchars($_SESSION['codigo']); ?> • <?php echo htmlspecialchars($grupo_alumno); ?></span>
                    </div>
                    <a href="../logout.php" class="logout-btn" title="Cerrar sesión" aria-label="Cerrar sesión">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                    </a>
                </div>
            </div>
        </aside>

        <main class="main-area" id="inicio">
            <header class="topbar">
                <div class="breadcrumb"><span>SiBCAE</span><span class="crumb-divider">/</span><strong>Historial de Asistencia</strong></div>
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

            <section class="content" id="mi-asistencia">
                <div class="page-heading">
                    <div>
                        <p class="eyebrow">CICLO ESCOLAR 2026 • GRUPO <?php echo htmlspecialchars($grupo_alumno); ?></p>
                        <h1>Mi Historial de Asistencia</h1>
                        <p class="subtitle">Consulta tus asistencias, retardos y faltas registradas por fecha o materia.</p>
                    </div>
                    <span class="live-badge"><span></span> Huella activa: #<?php echo htmlspecialchars($id_huella); ?></span>
                </div>

                <!-- Resumen rápido de 4 indicadores reales -->
                <div class="summary-row summary-row-4" aria-label="Resumen personal de asistencia" style="margin-top: 0; margin-bottom: 18px;">
                    <div class="summary-card">
                        <span class="summary-icon present-icon" aria-hidden="true">✓</span>
                        <div><span>Asistencias</span><strong><?php echo str_pad($cont_asistencias, 2, '0', STR_PAD_LEFT); ?></strong></div>
                    </div>
                    <div class="summary-card">
                        <span class="summary-icon late-icon" aria-hidden="true">◷</span>
                        <div><span>Retardos</span><strong><?php echo str_pad($cont_retardos, 2, '0', STR_PAD_LEFT); ?></strong></div>
                    </div>
                    <div class="summary-card">
                        <span class="summary-icon absent-icon" aria-hidden="true">!</span>
                        <div><span>Faltas</span><strong><?php echo str_pad($cont_faltas, 2, '0', STR_PAD_LEFT); ?></strong></div>
                    </div>
                    <div class="summary-card">
                        <span class="summary-icon students-icon" aria-hidden="true">%</span>
                        <div><span>Porcentaje global</span><strong><?php echo $porcentaje; ?>%</strong></div>
                    </div>
                </div>

                <section class="attendance-card" aria-labelledby="attendance-title">
                    <div class="card-heading">
                        <div>
                            <h2 id="attendance-title">Detalle de registros</h2>
                            <p>Filtra por una fecha específica, materia o tipo de incidencia.</p>
                        </div>
                        <button class="export-button" type="button" id="btn-clear-filters">
                            Limpiar filtros
                        </button>
                    </div>

                    <div class="filters" aria-label="Filtros de consulta">
                        <label class="filter-field">
                            <span>Fecha específica</span>
                            <input type="date" id="filter-date" class="date-input" aria-label="Seleccionar fecha">
                        </label>

                        <label class="filter-field subject-field">
                            <span>Materia</span>
                            <select id="filter-subject" aria-label="Filtrar por materia">
                                <option value="all">Todas las materias</option>
                                <option value="Programación">Programación</option>
                                <option value="Matemáticas">Matemáticas</option>
                                <option value="Español">Español</option>
                            </select>
                        </label>

                        <label class="filter-field">
                            <span>Estado</span>
                            <select id="filter-status" aria-label="Filtrar por estado">
                                <option value="all">Todos los estados</option>
                                <option value="present">Asistencias</option>
                                <option value="late">Retardos</option>
                                <option value="absent">Faltas</option>
                            </select>
                        </label>
                    </div>

                    <div class="table-wrap">
                        <table id="student-table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Materia</th>
                                    <th>Docente</th>
                                    <th>Hora de huella</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($registros) === 0): ?>
                                    <tr><td colspan="5" style="text-align:center; padding:22px;">Aún no tienes registros de asistencia en el sistema.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($registros as $reg): 
                                        $est = $reg['estado'];
                                        if ($est === 'Asistencia' || $est === 'Presente' || $est === 'Justificado') {
                                            $data_status = 'present';
                                            $clase_css   = 'present';
                                        } elseif ($est === 'Retardo') {
                                            $data_status = 'late';
                                            $clase_css   = 'late';
                                        } else {
                                            $data_status = 'absent';
                                            $clase_css   = 'absent';
                                        }
                                        $materia_txt = !empty($reg['materia']) ? $reg['materia'] : 'Programación';
                                        $hora_txt    = !empty($reg['hora']) ? date("h:i A", strtotime($reg['hora'])) : '—';
                                        $fecha_txt   = date("d M Y", strtotime($reg['fecha']));
                                    ?>
                                    <tr data-date="<?php echo htmlspecialchars($reg['fecha']); ?>" data-subject="<?php echo htmlspecialchars($materia_txt); ?>" data-status="<?php echo $data_status; ?>">
                                        <td class="code"><?php echo $fecha_txt; ?></td>
                                        <td class="student-name"><?php echo htmlspecialchars($materia_txt); ?></td>
                                        <td>Docente Asignado</td>
                                        <td class="code"><?php echo $hora_txt; ?></td>
                                        <td><span class="status <?php echo $clase_css; ?>"><span></span><?php echo htmlspecialchars($est); ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="card-footer">
                        <span id="records-count">Mostrando <?php echo count($registros); ?> registro(s)</span>
                    </div>
                </section>
            </section>
        </main>
    </div>

    <script>
        const dateInput = document.getElementById('filter-date');
        const subjectSelect = document.getElementById('filter-subject');
        const statusSelect = document.getElementById('filter-status');
        const clearBtn = document.getElementById('btn-clear-filters');
        const rows = document.querySelectorAll('#student-table tbody tr[data-date]');
        const countLabel = document.getElementById('records-count');

        function filterTable() {
            const selectedDate = dateInput.value;
            const selectedSubject = subjectSelect.value;
            const selectedStatus = statusSelect.value;
            let visibleCount = 0;

            rows.forEach(row => {
                const matchDate = !selectedDate || row.dataset.date === selectedDate;
                const matchSubject = selectedSubject === 'all' || row.dataset.subject === selectedSubject;
                const matchStatus = selectedStatus === 'all' || row.dataset.status === selectedStatus;

                if (matchDate && matchSubject && matchStatus) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            countLabel.textContent = visibleCount === 0 
                ? 'No hay registros para la fecha o filtros seleccionados' 
                : `Mostrando ${visibleCount} registro(s)`;
        }

        dateInput.addEventListener('change', filterTable);
        subjectSelect.addEventListener('change', filterTable);
        statusSelect.addEventListener('change', filterTable);

        clearBtn.addEventListener('click', () => {
            dateInput.value = '';
            subjectSelect.value = 'all';
            statusSelect.value = 'all';
            filterTable();
        });
    </script>
</body>
</html>