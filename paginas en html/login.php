<?php
session_set_cookie_params(0, '/');
session_start();
include 'conexion.php';

$error = "";

// Si ya tiene sesión activa, mandarlo directo a su panel
if (isset($_SESSION['usuario_id'])) {
    if ($_SESSION['rol'] === 'profesor') {
        header("Location: profesor/profesor.php");
        exit;
    } else {
        header("Location: alumno/alumno.php");
        exit;
    }
}

// Procesar el inicio de sesión al enviar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identificador = $conexion->real_escape_string(trim($_POST['identificador']));
    $password      = $_POST['password'];
    $rol_elegido   = ($_POST['rol'] === 'teacher') ? 'profesor' : 'alumno';

    // Buscar al usuario por código o correo y verificar que coincida con la pestaña elegida
    $sql = "SELECT * FROM usuarios 
            WHERE (codigo = '$identificador' OR correo = '$identificador') 
            AND rol = '$rol_elegido' 
            LIMIT 1";
    $resultado = $conexion->query($sql);

    if ($resultado && $resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();

        // Validar contraseña (soporta tanto hash seguro como texto plano de prueba)
        if (password_verify($password, $usuario['password']) || $password === $usuario['password']) {
            $_SESSION['usuario_id'] = $usuario['id_huella'];
            $_SESSION['id_huella']  = $usuario['id_huella'];
            $_SESSION['nombre']     = $usuario['nombre'];
            $_SESSION['codigo']     = $usuario['codigo'];
            $_SESSION['rol']        = $usuario['rol'];

            session_write_close();

            if ($usuario['rol'] === 'profesor') {
                header("Location: profesor/profesor.php");
            } else {
                header("Location: alumno/alumno.php");
            }
            exit;
        } else {
            $error = "Contraseña incorrecta. Verifica tus datos.";
        }
    } else {
        $error = "No se encontró un " . $rol_elegido . " con ese código o correo.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ffffff">
    <title>SiBCAE | Iniciar Sesión</title>
    <link rel="stylesheet" href="login.css">
</head>
<body class="login-body">
    <main class="login-shell">
        <section class="login-card" aria-labelledby="login-title">
            <!-- Encabezado con el mismo logo de tu sidebar -->
            <div class="login-brand">
                <img src="SiBCAE_LogoW&B.png" alt="" class="brand-logo" width="34px" height="46px">
                <span>SiBCAE</span>
            </div>

            <div class="login-header">
                <p class="eyebrow">CONTROL DE ASISTENCIA BIOMÉTRICA</p>
                <h1 id="login-title">Bienvenido de nuevo</h1>
                <p class="subtitle" id="role-subtitle">Ingresa con tu matrícula para consultar tus asistencias.</p>
            </div>

            <!-- Mensaje de error si fallan las credenciales -->
            <?php if (!empty($error)): ?>
                <div style="background:#fee2e2; color:#b91c1c; padding:10px 14px; border-radius:8px; font-size:0.85rem; margin-bottom:14px; text-align:center; font-weight:500;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Selector de modo (Alumno / Profesor) -->
            <div class="role-switch" role="tablist" aria-label="Seleccionar tipo de usuario">
                <button type="button" class="role-tab active" role="tab" aria-selected="true" data-role="student">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 19.5a7 7 0 0 1 14 0"/></svg>
                    <span>Soy Alumno</span>
                </button>
                <button type="button" class="role-tab" role="tab" aria-selected="false" data-role="teacher">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7l9-4 9 4-9 4-9-4z"/><path d="M6 10v5c0 2 3 3.5 6 3.5s6-1.5 6-3.5v-5"/></svg>
                    <span>Soy Profesor</span>
                </button>
            </div>

            <!-- Formulario conectado a PHP -->
            <form id="login-form" class="login-form" method="POST" action="login.php">
                <!-- Campo oculto que envía a PHP si eligió 'student' o 'teacher' -->
                <input type="hidden" name="rol" id="role-input" value="student">

                <label class="login-field">
                    <span id="id-label">Código de Estudiante / Matrícula</span>
                    <input type="text" name="identificador" id="user-id" placeholder="Ej. 001012350" required autocomplete="username">
                </label>

                <label class="login-field">
                    <div class="field-row">
                        <span>Contraseña</span>
                        <a href="#recuperar" class="forgot-link">¿La olvidaste?</a>
                    </div>
                    <input type="password" name="password" id="user-pass" placeholder="••••••••" required autocomplete="current-password">
                </label>

                <label class="remember-check">
                    <input type="checkbox" id="remember">
                    <span>Mantener sesión iniciada</span>
                </label>

                <button type="submit" class="login-submit" id="submit-btn">
                    Ingresar al Portal de Alumnos
                </button>
            </form>

            <p class="login-footer">
                ¿Problemas con tu registro de huella? Contacta a Control Escolar.
            </p>
        </section>
    </main>

    <script>
        const roleTabs = document.querySelectorAll('.role-tab');
        const roleSubtitle = document.getElementById('role-subtitle');
        const idLabel = document.getElementById('id-label');
        const userIdInput = document.getElementById('user-id');
        const submitBtn = document.getElementById('submit-btn');
        const roleInput = document.getElementById('role-input');

        let currentRole = 'student';

        const roleConfig = {
            student: {
                subtitle: 'Ingresa con tu matrícula para consultar tus asistencias.',
                label: 'Código de Estudiante / Matrícula',
                placeholder: 'Ej. 001012350',
                btnText: 'Ingresar al Portal de Alumnos'
            },
            teacher: {
                subtitle: 'Accede con tus credenciales docentes para gestionar tus grupos.',
                label: 'Correo Institucional o ID Docente',
                placeholder: 'Ej. luis.martinez@sibcae.edu.mx',
                btnText: 'Ingresar al Panel de Profesores'
            }
        };

        roleTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                roleTabs.forEach(t => {
                    t.classList.remove('active');
                    t.setAttribute('aria-selected', 'false');
                });

                tab.classList.add('active');
                tab.setAttribute('aria-selected', 'true');

                currentRole = tab.dataset.role;
                roleInput.value = currentRole;
                const config = roleConfig[currentRole];

                roleSubtitle.textContent = config.subtitle;
                idLabel.textContent = config.label;
                userIdInput.placeholder = config.placeholder;
                submitBtn.textContent = config.btnText;
                userIdInput.focus();
            });
        });
    </script>
</body>
</html>