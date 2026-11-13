<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Método no permitido.']);
    exit;
}

// 1. Recibir y sanitizar datos del alumno
$codigoEstudiante   = trim($_POST['codigo_estudiante'] ?? '');
$nombreCompleto     = trim($_POST['nombre_completo'] ?? '');
$correoInstitucional= filter_var(trim($_POST['correo_institucional'] ?? ''), FILTER_VALIDATE_EMAIL);
$incidencia         = trim($_POST['incidencia_id'] ?? '');
$motivo             = trim($_POST['motivo'] ?? '');
$observaciones      = trim($_POST['observaciones'] ?? 'Sin observaciones adicionales.');

if (!$codigoEstudiante || !$nombreCompleto || !$correoInstitucional || !$incidencia) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Faltan datos obligatorios del estudiante o la falta seleccionada.']);
    exit;
}

// 2. Validar archivo adjunto (opcional pero recomendado)
$hasAttachment = isset($_FILES['evidencia']) && $_FILES['evidencia']['error'] === UPLOAD_ERR_OK;
$allowedMimes  = ['application/pdf', 'image/jpeg', 'image/png'];

if ($hasAttachment) {
    $fileTmpPath = $_FILES['evidencia']['tmp_name'];
    $fileName    = basename($_FILES['evidencia']['name']);
    $fileSize    = $_FILES['evidencia']['size'];
    $fileMime    = mime_content_type($fileTmpPath);

    if (!in_array($fileMime, $allowedMimes, true)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'Formato no válido. Solo se permite PDF, JPG o PNG.']);
        exit;
    }
    if ($fileSize > 5 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'El archivo supera el límite de 5 MB.']);
        exit;
    }
}

// 3. Configurar correo hacia administración SiBCAE
$destinatario = 'sibcaeudg@gmail.com';
$folio        = strtoupper(substr(md5(uniqid((string)rand(), true)), 0, 6));
$asunto       = "SiBCAE Justificante #$folio | $codigoEstudiante - $nombreCompleto";

$cuerpoTexto = "NUEVA SOLICITUD DE JUSTIFICANTE - SiBCAE\n"
             . "==================================================\n"
             . "Folio:                #$folio\n"
             . "Código de Estudiante: $codigoEstudiante\n"
             . "Nombre Completo:      $nombreCompleto\n"
             . "Correo Institucional: $correoInstitucional\n"
             . "Incidencia / Falta:   $incidencia\n"
             . "Motivo:               $motivo\n"
             . "Observaciones:        $observaciones\n"
             . "Fecha de envío:       " . date('Y-m-d H:i:s') . "\n"
             . "==================================================\n";

// 4. Construir MIME multipart para incluir el archivo adjunto
$boundary = md5(time());
$headers  = "From: Sistema SiBCAE <no-reply@sibcae.edu.mx>\r\n";
$headers .= "Reply-To: $nombreCompleto <$correoInstitucional>\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

$mensaje  = "--$boundary\r\n";
$mensaje .= "Content-Type: text/plain; charset=UTF-8\r\n";
$mensaje .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
$mensaje .= $cuerpoTexto . "\r\n";

if ($hasAttachment) {
    $fileContent = chunk_split(base64_encode(file_get_contents($fileTmpPath)));
    $mensaje .= "--$boundary\r\n";
    $mensaje .= "Content-Type: $fileMime; name=\"$fileName\"\r\n";
    $mensaje .= "Content-Disposition: attachment; filename=\"$fileName\"\r\n";
    $mensaje .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $mensaje .= $fileContent . "\r\n";
}

$mensaje .= "--$boundary--";

// 5. Enviar correo
if (mail($destinatario, $asunto, $mensaje, $headers)) {
    echo json_encode([
        'ok'      => true,
        'folio'   => $folio,
        'message' => 'Justificante enviado exitosamente.'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'ok'      => false,
        'message' => 'El servidor no pudo enviar el correo. Verifica la configuración SMTP.'
    ]);
}