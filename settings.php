<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Admin settings for local_evalia.
 *
 * @package    local_evalia
 * @copyright  2026 Schaller & Ponce <dev@schaller-ponce.com.ar>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_evalia', 'EVAL-IA');
    $ADMIN->add('localplugins', $settings);

    // ── Setup Wizard link ────────────────────────────────────────────────────
    $setupurl = new moodle_url('/local/evalia/setup.php');
    $settings->add(new admin_setting_heading(
        'local_evalia_wizard_heading',
        '🚀 Setup Wizard',
        html_writer::tag(
            'p',
            'Use the wizard to configure the AI engine step by step, with a real-time connection test. ' .
            html_writer::link($setupurl, '▶ Launch Setup Wizard', ['class' => 'btn btn-sm btn-primary ms-2']),
            ['style' => 'margin-top:6px;']
        )
    ));

    // ── Motor de IA ──────────────────────────────────────────────────────────
    $settings->add(new admin_setting_heading(
        'local_evalia_engine_heading',
        '🤖 Motor de IA (SAIPA Engine)',
        'URL y token del motor de IA. Si se dejan en blanco, EVAL-IA utiliza la configuración '
        . 'del plugin SAIPA (si está instalado). Configúrelos aquí para despliegues independientes '
        . 'donde SAIPA no está instalado.'
    ));

    $settings->add(new admin_setting_configtext(
        'local_evalia/engine_url',
        'URL del motor de IA',
        'URL base del engine, por ejemplo: <code>http://localhost:8052</code> o '
        . '<code>https://engine.saipa.online</code>. Dejar en blanco para heredar la configuración de SAIPA.',
        '',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'local_evalia/engine_token',
        'Token de autenticación del motor',
        'Bearer token configurado en el engine. Dejar en blanco para heredar el token de SAIPA.',
        ''
    ));

    // ── Rúbricas ─────────────────────────────────────────────────────────────
    $settings->add(new admin_setting_heading(
        'local_evalia_rubric_heading',
        '📋 Rúbricas',
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'local_evalia/rubric_default_items',
        'Cantidad de ítems por defecto',
        'Valor inicial del campo al abrir el formulario de generación (rango: 5–40).',
        18,
        PARAM_INT
    ));

    // ── Exámenes ──────────────────────────────────────────────────────────────
    $settings->add(new admin_setting_heading(
        'local_evalia_exam_heading',
        '📝 Exámenes',
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'local_evalia/exam_default_basic',
        'Preguntas básicas por defecto',
        'Cantidad inicial de preguntas básicas al crear un examen.',
        3,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_evalia/exam_default_medium',
        'Preguntas medias por defecto',
        'Cantidad inicial de preguntas medias al crear un examen.',
        4,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_evalia/exam_default_advanced',
        'Preguntas avanzadas por defecto',
        'Cantidad inicial de preguntas avanzadas al crear un examen.',
        2,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_evalia/exam_default_time_limit',
        'Tiempo límite por defecto (minutos)',
        'Valor inicial del campo tiempo límite. Usar 0 para sin límite.',
        60,
        PARAM_INT
    ));

    // ── Ponderación por dificultad ────────────────────────────────────────────
    $settings->add(new admin_setting_heading(
        'local_evalia_weights_heading',
        '⚖️ Ponderación por dificultad',
        'Puntos asignados a cada pregunta según su dificultad. ' .
        'La nota final se calcula como (pesos correctos / total pesos) × 10.'
    ));

    $settings->add(new admin_setting_configtext(
        'local_evalia/weight_basic',
        'Peso — preguntas básicas',
        '',
        '1',
        PARAM_FLOAT
    ));

    $settings->add(new admin_setting_configtext(
        'local_evalia/weight_medium',
        'Peso — preguntas medias',
        '',
        '2',
        PARAM_FLOAT
    ));

    $settings->add(new admin_setting_configtext(
        'local_evalia/weight_advanced',
        'Peso — preguntas avanzadas',
        '',
        '3',
        PARAM_FLOAT
    ));

    // ── Generación de preguntas ───────────────────────────────────────────────
    $settings->add(new admin_setting_heading(
        'local_evalia_questions_heading',
        '❓ Generación de preguntas',
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'local_evalia/questions_default_count',
        'Preguntas por ítem de rúbrica (por defecto)',
        'Cantidad inicial en el diálogo "Generar preguntas" por cada ítem. Rango recomendado: 3–10.',
        5,
        PARAM_INT
    ));

    // ── Indexado de PDFs ──────────────────────────────────────────────────────
    $settings->add(new admin_setting_heading(
        'local_evalia_pdf_heading',
        '📄 Indexado de PDFs',
        ''
    ));

    $settings->add(new admin_setting_configtextarea(
        'local_evalia/pdf_page_ranges',
        'Rangos de páginas por archivo PDF',
        'JSON que mapea nombres de archivo a capítulos. Permite indexar cada capítulo como fuente independiente.<br>' .
        'Formato: <code>{"archivo.pdf": [{"from": 1, "to": 50, "label": "Cap1-Tema"}, ...]}</code><br>' .
        'Dejar vacío para indexar el PDF completo sin dividir.',
        '',
        PARAM_RAW
    ));

    // ── Feedback ──────────────────────────────────────────────────────────────
    $settings->add(new admin_setting_heading(
        'local_evalia_feedback_heading',
        '💬 Feedback',
        ''
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_evalia/feedback_telegram',
        'Enviar feedback por Telegram',
        'Al calificar un examen, envía al alumno su resultado y análisis pedagógico vía Telegram. ' .
        'Requiere que el alumno tenga vinculada su cuenta de Telegram en SAIPA.',
        1
    ));
}
