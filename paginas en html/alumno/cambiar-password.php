<?php
session_set_cookie_params(0, '/');
session_start();
include '../conexion.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'alumno') {
    header("Location: ../login.php");
    exit;
}

$usuario_id = intval($_SESSION['usuario_id']);
$mensaje = "";
$tipo = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actual    = $_POST['password_actual'] ?? '';
    $nueva     = $_POST['password_nueva'] ?? '';
    $confirmar = $_POST['password_confirmar'] ?? '';

    if (empty($actual) || empty($nueva) || empty($confirmar)) {
        $mensaje = "Todos los campos son obligatorios.";
        $tipo = "error";
    } elseif ($nueva !== $confirmar) {
        $mensaje = "La nueva contraseña y la confirmación no coinciden.";
        $tipo = "error";
    } elseif (strlen($nueva) < 4) {
        $mensaje = "La nueva contraseña debe tener al menos 4 caracteres.";
        $tipo = "error";
    } else {
        // Consultar la contraseña actual en la base de datos
        $res = $conexion->query("SELECT password FROM usuarios WHERE id_huella = $usuario_id LIMIT 1");
        if ($res && $row = $res->fetch_assoc()) {
            $hash_bd = $row['password'];

            // Validar si la actual coincide (soporta hash o texto plano inicial)
            if (password_verify($actual, $hash_bd) || $actual === $hash_bd) {
                $nuevo_hash = password_hash($nueva, PASSWORD_DEFAULT);
                if ($conexion->query("UPDATE usuarios SET password = '$nuevo_hash' WHERE id_huella = $usuario_id")) {
                    $mensaje = "¡Contraseña actualizada con éxito!";
                    $tipo = "ok";
                } else {
                    $mensaje = "Error al actualizar en la base de datos.";
                    $tipo = "error";
                }
            } else {
                $mensaje = "La contraseña actual es incorrecta.";
                $tipo = "error";
            }
        }
    }
}

// Datos del alumno para la vista
$res_user = $conexion->query("SELECT * FROM usuarios WHERE id_huella = $usuario_id LIMIT 1");
$alumno = $res_user ? $res_user->fetch_assoc() : [];
$grupo = $alumno['grupo'] ?? '1°A';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiBCAE | Cambiar Contraseña</title>
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
                <a class="nav-link" href="alumno.php">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 4.5h8a2 2 0 0 1 2 2v13H6v-13a2 2 0 0 1 2-2Z"/><path d="M9 4.5v-1h6v1M9 10h6M9 14h6M9 18h3"/></svg>
                    <span>Mis asistencias</span>
                </a>
                <a class="nav-link" href="justificantes.php">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3.5h8l4 4V20a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4.5a1 1 0 0 1 1-1Z"/><path d="M14 3.5v4h4M9 13h6M9 17h4"/></svg>
                    <span>Justificantes</span>
                </a>
                <a class="nav-link active" href="cambiar-password.php" aria-current="page">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <span>Seguridad</span>
                </a>
            </nav>

            <div class="sidebar-bottom">
                <div class="profile">
                    <div class="profile-copy">
                        <strong><?php echo htmlspecialchars($_SESSION['nombre']); ?></strong>
                        <span>Matrícula: <?php echo htmlspecialchars($_SESSION['codigo']); ?></span>
                    </div>
                    <a href="../logout.php" class="logout-btn" title="Cerrar sesión">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                    </a>
                </div>
            </div>
        </aside>

        <main class="main-area">
            <header class="topbar">
                <div class="breadcrumb"><span>SiBCAE</span><span class="crumb-divider">/</span><strong>Cambiar Contraseña</strong></div>
            </header>

            <section class="content">
                <div class="page-heading">
                    <div>
                        <p class="eyebrow">SEGURIDAD DE LA CUENTA</p>
                        <h1>Actualizar Contraseña</h1>
                        <p class="subtitle">Personaliza tu contraseña de acceso para proteger tu cuenta.</p>
                    </div>
                </div>

                <?php if ($mensaje): ?>
                    <div style="padding:14px 18px; border-radius:10px; margin-bottom:20px; font-weight:600; background:<?php echo $tipo==='ok' ? '#dcfce7' : '#fee2e2'; ?>; color:<?php echo $tipo==='ok' ? '#15803d' : '#b91c1c'; ?>;">
                        <?php echo htmlspecialchars($mensaje); ?>
                    </div>
                <?php endif; ?>

                <section class="attendance-card" style="max-width: 600px;">
                    <div class="card-heading">
                        <div>
                            <h2>Cambiar clave de acceso</h2>
                            <p>Si es tu primera vez, tu contraseña actual es tu código de estudiante.</p>
                        </div>
                    </div>

                    <form method="POST" action="cambiar-password.php" style="padding: 20px;">
                        <label class="filter-field login-input-group" style="margin-bottom: 16px; display:block;">
                            <span style="display:block; margin-bottom:6px; font-weight:600;">Contraseña actual (o tu código inicial)</span>
                            <input type="password" name="password_actual" placeholder="••••••••" required style="width:100%; padding:10px; border-radius:8px; border:1px solid #cbd5e1;">
                        </label>

                        <label class="filter-field login-input-group" style="margin-bottom: 16px; display:block;">
                            <span style="display:block; margin-bottom:6px; font-weight:600;">Nueva contraseña</span>
                            <input type="password" name="password_nueva" placeholder="••••••••" required style="width:100%; padding:10px; border-radius:8px; border:1px solid #cbd5e1;">
                        </label>

                        <label class="filter-field login-input-group" style="margin-bottom: 24px; display:block;">
                            <span style="display:block; margin-bottom:6px; font-weight:600;">Confirmar nueva contraseña</span>
                            <input type="password" name="password_confirmar" placeholder="••••••••" required style="width:100%; padding:10px; border-radius:8px; border:1px solid #cbd5e1;">
                        </label>

                        <button type="submit" class="btn-primary-sibcae btn-auto" style="padding: 10px 20px; border-radius:8px; cursor:pointer; font-weight:600;">
                            Guardar Nueva Contraseña
                        </button>
                    </form>
                </section>
            </section>
        </main>
    </div>
</body>
</html>