CREATE DATABASE IF NOT EXISTS asistencia_biometrica;
USE asistencia_biometrica;

CREATE TABLE estudiantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    matricula VARCHAR(20) UNIQUE NOT NULL,
    nombre_completo VARCHAR(100) NOT NULL,
    id_biometrico VARCHAR(255) UNIQUE NOT NULL -- ID asignado por el sensor
);

CREATE TABLE asistencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT NOT NULL,
    fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('Presente', 'Retardo') DEFAULT 'Presente',
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id)
);

-- Datos de prueba
INSERT INTO estudiantes (id, nombre_completo, id_biometrico) VALUES 
(1, 'Ana Sofía Martínez', 'BIO_987654'),
(2, 'Carlos Eduardo López', 'BIO_123456');

INSERT INTO asistencias (estudiante_id, estado) VALUES (1, 'Presente'), (2, 'Retardo');