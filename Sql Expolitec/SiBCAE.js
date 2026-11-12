const express = require('express');
const mysql = require('mysql2/promise');
const cors = require('cors');
const path = require('path');
require('dotenv').config();

const app = express();
const PORT = process.env.PORT || 3000;

// Middlewares
app.use(cors());
app.use(express.json());
app.use(express.static(path.join(__dirname, 'public')));

// Pool de conexiones a MySQL
const pool = mysql.createPool({
    host: process.env.DB_HOST || 'localhost',
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASSWORD || '',
    database: process.env.DB_NAME || 'asistencia_biometrica',
    waitForConnections: true,
    connectionLimit: 10
});

// RUTA 1: Consultar todas las asistencias
app.get('/api/asistencias', async (req, res) => {
    try {
        const query = `
            SELECT a.id, e.matricula, e.nombre_completo, a.fecha_hora, a.estado 
            FROM asistencias a
            INNER JOIN estudiantes e ON a.estudiante_id = e.id
            ORDER BY a.fecha_hora DESC
        `;
        const [rows] = await pool.query(query);
        res.json(rows);
    } catch (error) {
        console.error('Error al consultar asistencias:', error);
        res.status(500).json({ error: 'Error interno del servidor' });
    }
});

// RUTA 2: Registrar asistencia desde la chingadera esa
app.post('/api/registrar-huella', async (req, res) => {
    const { id_biometrico } = req.body;

    if (!id_biometrico) {
        return res.status(400).json({ error: 'Falta el ID biométrico' });
    }

    try {
        // 1. Buscar al estudiante por su ID biométrico
        const [estudiantes] = await pool.query(
            'SELECT id, nombre_completo FROM estudiantes WHERE id_biometrico = ?',
            [id_biometrico]
        );

        if (estudiantes.length === 0) {
            return res.status(404).json({ error: 'Huella/Rostro no reconocido en el sistema' });
        }

        const estudiante = estudiantes[0];

        // 2. Insertar el registro de asistencia
        await pool.query(
            'INSERT INTO asistencias (estudiante_id, estado) VALUES (?, ?)',
            [estudiante.id, 'Presente']
        );

        res.status(201).json({ 
            mensaje: `Asistencia registrada para ${estudiante.nombre_completo}` 
        });
    } catch (error) {
        console.error('Error al registrar asistencia:', error);
        res.status(500).json({ error: 'Error al procesar el registro' });
    }
});

app.listen(PORT, () => {
    console.log(`Servidor corriendo en http://localhost:${PORT}`);
});