<?php
session_start();
header('Content-Type: application/json');
include '../conexion.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'alumno') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Sesión no válida. Inicia sesión nuevamente.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Método no permitido.']);
    exit;
}

$usuario_id    = intval($_SESSION['usuario_id']);
$incidencia    = $conexion->real_escape_string($_POST['incidencia_id'] ?? '');
$motivo        = $conexion->real_escape_string($_POST['motivo'] ?? '');
$observaciones = $conexion->real_escape_string($_POST['observaciones'] ?? '');

if (empty($incidencia) || empty($motivo) || !isset($_FILES['evidencia'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Faltan campos obligatorios o el archivo comprobatorio.']);
    exit;
}

$archivo = $_FILES['evidencia'];
if ($archivo['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Error al subir el archivo al servidor.']);
    exit;
}

// Validar tamaño (máx 5 MB) y extensión
if ($archivo['size'] > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'El archivo supera el límite de 5 MB.']);
    exit;
}

$ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
$permitidos = ['pdf', 'jpg', 'jpeg', 'png'];
if (!in_array($ext, $permitidos)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Formato no permitido. Usa PDF, JPG o PNG.']);
    exit;
}

// Crear carpeta alumno/evidencias si no existe
$carpeta_destino = __DIR__ . '/evidencias/';
if (!is_dir($carpeta_destino)) {
    mkdir($carpeta_destino, 0777, true);
}

$nombre_limpio  = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($archivo['name']));
$nombre_guardado = time() . '_' . $nombre_limpio;
$ruta_final      = $carpeta_destino . $nombre_guardado;

if (move_uploaded_file($archivo['tmp_name'], $ruta_final)) {
    $sql = "INSERT INTO justificantes (usuario_id, incidencia, motivo, observaciones, archivo) 
            VALUES ($usuario_id, '$incidencia', '$motivo', '$observaciones', '$nombre_guardado')";

    if ($conexion->query($sql)) {
        $folio = str_pad($conexion->insert_id, 4, '0', STR_PAD_LEFT);
        echo json_encode([
            'ok' => true,
            'folio' => $folio,
            'message' => 'Justificante guardado correctamente.'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => 'Error en base de datos: ' . $conexion->error]);
    }
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'No se pudo guardar el archivo en el servidor.']);
}
?>