<?php
session_start();
include '../conexion.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'alumno') {
    header("Location: ../login.php");
    exit;
}

$usuario_id = intval($_SESSION['usuario_id']);

// Datos del alumno
$res_user = $conexion->query("SELECT * FROM usuarios WHERE id = $usuario_id LIMIT 1");
$alumno   = $res_user->fetch_assoc();
$grupo    = $alumno['grupo'] ?? '1°A';
$correo   = $alumno['correo'] ?? '';

// Consultar faltas y retardos reales del alumno para el <select>
$res_incidencias = $conexion->query(
    "SELECT * FROM asistencias 
     WHERE usuario_id = $usuario_id AND (estado = 'Falta' OR estado = 'Retardo') 
     ORDER BY fecha DESC"
);

// Consultar historial de justificantes enviados por el alumno
$res_historial = $conexion->query(
    "SELECT * FROM justificantes WHERE usuario_id = $usuario_id ORDER BY fecha_envio DESC"
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ffffff">
    <title>SiBCAE | Justificantes</title>
    <link rel="stylesheet" href="justificantes.css">
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
                <a class="nav-link" href="alumno.php">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 4.5h8a2 2 0 0 1 2 2v13H6v-13a2 2 0 0 1 2-2Z"/><path d="M9 4.5v-1h6v1M9 10h6M9 14h6M9 18h3"/></svg>
                    <span>Mis asistencias</span>
                </a>
                <a class="nav-link active" href="justificantes.php" aria-current="page">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3.5h8l4 4V20a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4.5a1 1 0 0 1 1-1Z"/><path d="M14 3.5v4h4M9 13h6M9 17h4"/></svg>
                    <span>Justificantes</span>
                </a>
            </nav>

            <div class="sidebar-bottom">
                <div class="help-card">
                    <span class="help-icon" aria-hidden="true">?</span>
                    <div><strong>¿Plazo límite?</strong><span>Tienes 72 hrs hábiles</span></div>
                    <span class="help-arrow" aria-hidden="true">›</span>
                </div>
                <div class="profile">
                    <div class="profile-copy">
                        <strong><?php echo htmlspecialchars($_SESSION['nombre']); ?></strong>
                        <span>Matrícula: <?php echo htmlspecialchars($_SESSION['codigo']); ?> • <?php echo htmlspecialchars($grupo); ?></span>
                    </div>
                    <a href="../logout.php" class="logout-btn" title="Cerrar sesión" aria-label="Cerrar sesión">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                    </a>
                </div>
            </div>
        </aside>

        <main class="main-area" id="inicio">
            <header class="topbar">
                <div class="breadcrumb">
                    <span>SiBCAE</span>
                    <span class="crumb-divider">/</span>
                    <strong>Solicitud de Justificantes</strong>
                </div>
                <div class="topbar-actions">
                    <a href="../logout.php" class="topbar-logout" title="Cerrar sesión">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                        <span>Cerrar sesión</span>
                    </a>
                </div>
            </header>

            <section class="content">
                <div class="page-heading">
                    <div>
                        <p class="eyebrow">TRÁMITES ESCOLARES • GRUPO <?php echo htmlspecialchars($grupo); ?></p>
                        <h1>Justificantes de Inasistencia</h1>
                        <p class="subtitle">Selecciona la falta registrada y adjunta tu documento comprobatorio para revisión.</p>
                    </div>
                    <span class="live-badge"><span></span> Envío a sibcaeudg@gmail.com</span>
                </div>

                <!-- Tarjeta principal: Formulario de solicitud -->
                <section class="attendance-card" aria-labelledby="form-justificante-title">
                    <div class="card-heading">
                        <div>
                            <h2 id="form-justificante-title">Nueva solicitud de justificante</h2>
                            <p>Los datos escolares se enviarán automáticamente a la administración de SiBCAE.</p>
                        </div>
                    </div>

                    <form id="justificante-form" enctype="multipart/form-data">
                        <div class="filters justify-identity-bar">
                            <label class="filter-field login-input-group">
                                <span>Código de Estudiante</span>
                                <input type="text" name="codigo_estudiante" id="codigo-estudiante" value="<?php echo htmlspecialchars($_SESSION['codigo']); ?>" readonly required>
                            </label>

                            <label class="filter-field login-input-group">
                                <span>Nombre completo</span>
                                <input type="text" name="nombre_completo" id="nombre-completo" value="<?php echo htmlspecialchars($_SESSION['nombre']); ?>" readonly required>
                            </label>

                            <label class="filter-field login-input-group">
                                <span>Correo institucional</span>
                                <input type="email" name="correo_institucional" id="correo-institucional" value="<?php echo htmlspecialchars($correo); ?>" required>
                            </label>
                        </div>

                        <div class="justify-form-body">
                            <div class="justify-grid-2">
                                <label class="filter-field">
                                    <span>Falta o retardo a justificar</span>
                                    <select name="incidencia_id" id="select-falta" required>
                                        <option value="" disabled selected>Selecciona una incidencia registrada...</option>
                                        <?php if ($res_incidencias && $res_incidencias->num_rows > 0): ?>
                                            <?php while ($inc = $res_incidencias->fetch_assoc()): 
                                                $f_txt = date("d M Y", strtotime($inc['fecha']));
                                                $m_txt = !empty($inc['materia']) ? $inc['materia'] : 'Clase General';
                                                $val_opt = $inc['fecha'] . " | " . $m_txt . " | " . $inc['estado'];
                                            ?>
                                                <option value="<?php echo htmlspecialchars($val_opt); ?>">
                                                    <?php echo "$f_txt — $m_txt ({$inc['estado']})"; ?>
                                                </option>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <option value="<?php echo date('Y-m-d'); ?> | Justificación Preventiva | Falta">
                                                <?php echo date('d M Y'); ?> — Justificación de inasistencia general
                                            </option>
                                        <?php endif; ?>
                                    </select>
                                </label>

                                <label class="filter-field">
                                    <span>Motivo de la inasistencia</span>
                                    <select name="motivo" id="select-motivo" required>
                                        <option value="Consulta médica / Salud">Consulta médica / Salud (IMSS / UDG)</option>
                                        <option value="Trámite académico oficial">Trámite académico o institucional</option>
                                        <option value="Comisión deportiva o cultural">Comisión deportiva / cultural</option>
                                        <option value="Causa de fuerza mayor">Causa de fuerza mayor familiar</option>
                                    </select>
                                </label>
                            </div>

                            <label class="filter-field login-input-group">
                                <span>Observaciones adicionales (opcional)</span>
                                <textarea name="observaciones" id="observaciones" rows="3" placeholder="Describe brevemente el motivo o detalla si el comprobante cubre más de una materia..."></textarea>
                            </label>

                            <div class="filter-field">
                                <span>Documento comprobatorio (Receta médica, constancia en PDF, JPG o PNG — Máx. 5 MB)</span>
                                <label class="file-dropzone" for="archivo-evidencia" id="dropzone-label">
                                    <span id="file-name-display">Haz clic para seleccionar archivo o arrástralo aquí</span>
                                    <input type="file" name="evidencia" id="archivo-evidencia" accept=".pdf,.jpg,.jpeg,.png" required>
                                </label>
                            </div>

                            <div id="feedback-banner" class="feedback-banner" hidden></div>
                        </div>

                        <div class="card-footer">
                            <span>Destinatario oficial: <strong>sibcaeudg@gmail.com</strong></span>
                            <button type="submit" class="btn-primary-sibcae btn-auto" id="btn-submit-justificante">
                                Enviar Justificante
                            </button>
                        </div>
                    </form>
                </section>

                <!-- Segunda tarjeta: Historial de solicitudes enviadas desde MySQL -->
                <section class="attendance-card" style="margin-top: 20px;" aria-labelledby="historial-justificantes-title">
                    <div class="card-heading">
                        <div>
                            <h2 id="historial-justificantes-title">Mis solicitudes enviadas</h2>
                            <p>Seguimiento de justificantes remitidos a control escolar.</p>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table id="requests-table">
                            <thead>
                                <tr>
                                    <th>Fecha Envío</th>
                                    <th>Incidencia Seleccionada</th>
                                    <th>Motivo</th>
                                    <th>Archivo</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody id="requests-tbody">
                                <?php if ($res_historial && $res_historial->num_rows > 0): ?>
                                    <?php while ($req = $res_historial->fetch_assoc()): 
                                        $badge_cls = ($req['estado'] === 'Aprobado') ? 'present' : (($req['estado'] === 'Rechazado') ? 'absent' : 'late');
                                    ?>
                                    <tr>
                                        <td class="code"><?php echo date("d M Y", strtotime($req['fecha_envio'])); ?></td>
                                        <td class="student-name"><?php echo htmlspecialchars($req['incidencia']); ?></td>
                                        <td><?php echo htmlspecialchars($req['motivo']); ?></td>
                                        <td class="code">
                                            <a href="evidencias/<?php echo urlencode($req['archivo']); ?>" target="_blank" style="color:inherit; text-decoration:underline;">
                                                <?php echo htmlspecialchars($req['archivo']); ?>
                                            </a>
                                        </td>
                                        <td><span class="status <?php echo $badge_cls; ?>"><span></span><?php echo htmlspecialchars($req['estado']); ?></span></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr id="empty-row"><td colspan="5" style="text-align:center; padding:20px;">Aún no has enviado ninguna solicitud de justificante.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </section>
        </main>
    </div>

    <script>
        const form = document.getElementById('justificante-form');
        const fileInput = document.getElementById('archivo-evidencia');
        const fileDisplay = document.getElementById('file-name-display');
        const feedbackBanner = document.getElementById('feedback-banner');
        const submitBtn = document.getElementById('btn-submit-justificante');
        const requestsTbody = document.getElementById('requests-tbody');

        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
                fileDisplay.textContent = `${file.name} (${sizeMB} MB)`;
            } else {
                fileDisplay.textContent = 'Haz clic para seleccionar archivo o arrástralo aquí';
            }
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(form);
            const faltaTexto = formData.get('incidencia_id');
            const motivoTexto = formData.get('motivo');
            const archivoNombre = fileInput.files[0]?.name || 'documento.pdf';

            submitBtn.disabled = true;
            submitBtn.textContent = 'Enviando a SiBCAE...';
            feedbackBanner.hidden = true;

            try {
                const response = await fetch('enviar_justificante.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (!response.ok || !result.ok) {
                    throw new Error(result.message || 'No se pudo procesar el envío en el servidor.');
                }

                showFeedback('success', `Solicitud enviada correctamente a sibcaeudg@gmail.com. Folio: #${result.folio}`);
                appendRequestRow(faltaTexto, motivoTexto, archivoNombre);
                form.reset();
                fileDisplay.textContent = 'Haz clic para seleccionar archivo o arrástralo aquí';

            } catch (error) {
                showFeedback('error', error.message);
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Enviar Justificante';
            }
        });

        function showFeedback(type, message) {
            feedbackBanner.hidden = false;
            feedbackBanner.className = `feedback-banner ${type}`;
            feedbackBanner.textContent = message;
        }

        function appendRequestRow(falta, motivo, archivo) {
            const emptyRow = document.getElementById('empty-row');
            if (emptyRow) emptyRow.remove();

            const hoy = new Date().toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' });
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="code">${hoy}</td>
                <td class="student-name">${falta.split('|').slice(0, 2).join(' — ')}</td>
                <td>${motivo}</td>
                <td class="code">${archivo}</td>
                <td><span class="status late"><span></span>En revisión</span></td>
            `;
            requestsTbody.prepend(tr);
        }
    </script>
</body>
</html>