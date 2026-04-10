<?php
// This file is part of Moodle - http://moodle.org/
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
 * Cadenas de idioma español para local_evalia.
 *
 * @package    local_evalia
 * @copyright  2026 Schaller & Ponce <dev@schaller-ponce.com.ar>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Metadatos del plugin
$string['pluginname'] = 'EVAL-IA';
$string['plugindescription'] = 'Evaluación inteligente con IA para cursos Moodle. Genera rúbricas, bancos de preguntas y exámenes únicos por alumno usando RAG sobre el material del curso.';

// Capacidades
$string['evalia:manage'] = 'Gestionar evaluaciones EVAL-IA (docente)';
$string['evalia:take']   = 'Rendir exámenes EVAL-IA (alumno)';

// Títulos de página
$string['teacher_page_title']   = 'EVAL-IA — Panel Docente';
$string['teacher_page_heading'] = 'EVAL-IA: Evaluación Inteligente';

// Pestañas
$string['tab_rubric']    = 'Rúbrica';
$string['tab_questions'] = 'Banco de Preguntas';
$string['tab_exams']     = 'Exámenes';
$string['tab_portfolio'] = 'Legajos';

// Sección rúbrica
$string['rubric_generate']         = 'Generar Rúbrica con IA';
$string['rubric_generating']       = 'Generando rúbrica desde el material del curso...';
$string['rubric_save']             = 'Guardar Rúbrica';
$string['rubric_activate']         = 'Activar';
$string['rubric_status_draft']     = 'Borrador';
$string['rubric_status_active']    = 'Activa';
$string['rubric_status_archived']  = 'Archivada';
$string['rubric_no_content']       = 'No hay contenido del curso indexado aún. Primero indexe este curso en SAIPA.';
$string['rubric_saved']            = 'Rúbrica guardada correctamente.';
$string['rubric_activated']        = 'Rúbrica activada. Ya puede generar preguntas.';
$string['rubric_item_topic']       = 'Tema';
$string['rubric_item_description'] = 'Descripción';
$string['rubric_item_weight']      = 'Peso de dificultad';
$string['weight_low']              = 'Bajo';
$string['weight_medium']           = 'Medio';
$string['weight_high']             = 'Alto';

// Sección banco de preguntas
$string['questions_generate']          = 'Generar Preguntas';
$string['questions_generating']        = 'Generando preguntas...';
$string['questions_approve']           = 'Aprobar';
$string['questions_reject']            = 'Rechazar';
$string['questions_edit']              = 'Editar';
$string['questions_generate_more']     = 'Generar Más';
$string['questions_filter_topic']      = 'Filtrar por tema';
$string['questions_filter_difficulty'] = 'Filtrar por dificultad';
$string['questions_filter_status']     = 'Filtrar por estado';
$string['questions_status_draft']      = 'Pendiente de revisión';
$string['questions_status_approved']   = 'Aprobada';
$string['questions_status_rejected']   = 'Rechazada';
$string['difficulty_basic']            = 'Básica';
$string['difficulty_medium']           = 'Media';
$string['difficulty_advanced']         = 'Avanzada';
$string['qtype_multichoice']           = 'Opción múltiple';
$string['qtype_truefalse']             = 'Verdadero/Falso';
$string['qtype_numerical']             = 'Numérica';
$string['qtype_shortanswer']           = 'Respuesta corta';
$string['qtype_essay']                 = 'Ensayo';
$string['questions_none']              = 'El banco está vacío. Active una rúbrica primero y luego genere preguntas por ítem.';

// Sección exámenes
$string['exam_create']           = 'Crear Examen';
$string['exam_name']             = 'Nombre del examen';
$string['exam_instructions']     = 'Instrucciones para los alumnos';
$string['exam_basic_count']      = 'Preguntas básicas';
$string['exam_medium_count']     = 'Preguntas medias';
$string['exam_advanced_count']   = 'Preguntas avanzadas';
$string['exam_time_limit']       = 'Tiempo límite (minutos)';
$string['exam_assign_all']       = 'Asignar a todos los alumnos';
$string['exam_assigning']        = 'Asignando exámenes únicos por alumno...';
$string['exam_assigned']         = 'Exámenes asignados. Cada alumno recibió un conjunto único de preguntas.';
$string['exam_status_assigned']  = 'Asignado';
$string['exam_status_started']   = 'En progreso';
$string['exam_status_submitted'] = 'Enviado';
$string['exam_status_graded']    = 'Calificado';
$string['exam_not_enough_questions'] = 'No hay suficientes preguntas aprobadas en el banco para este examen. Apruebe más preguntas primero.';

// Legajos (Fase 2)
$string['portfolio_coming_soon']    = 'Legajos — Disponible en Fase 2';
$string['portfolio_description']    = 'Aquí estarán los legajos de alumnos con historial de exámenes, calificaciones y observaciones del docente.';
$string['portfolio_students']       = 'Legajos de Alumnos';
$string['portfolio_select_student'] = 'Seleccione un alumno de la lista para ver su legajo.';
$string['portfolio_exam_history']   = 'Historial de Exámenes';
$string['portfolio_observations']   = 'Observaciones';
$string['portfolio_no_students']    = 'No hay alumnos inscriptos en este curso.';
$string['portfolio_no_exams']       = 'Sin exámenes calificados aún.';
$string['portfolio_note_saved']     = 'Observación guardada.';
$string['portfolio_note_empty']     = 'Sin observaciones registradas.';
$string['portfolio_avg_grade']      = 'Nota prom.';
$string['portfolio_total_exams']    = 'Exámenes';
$string['portfolio_last_activity']  = 'Última actividad';
$string['portfolio_grade_now']      = 'Calificar';
$string['portfolio_loading']        = 'Cargando legajos...';

// Examen del alumno (Fase 2B)
$string['student_exam_title']    = 'EVAL-IA — Rendir Examen';
$string['exam_submit_btn']       = 'Enviar examen';
$string['exam_submitted_ok']     = 'Examen enviado correctamente. El docente revisará tu resultado.';
$string['exam_timer_label']      = 'Tiempo restante';
$string['exam_expired_auto']     = 'Tiempo agotado — el examen fue enviado automáticamente.';
$string['portfolio_export_csv']  = 'Exportar CSV';

// Navegación
$string['nav_my_exams'] = '📝 Mis Exámenes';

// Calendario
$string['calendar_exam_event'] = 'Examen: {$a}';

// Cadenas de la API de Privacidad (GDPR)
$string['privacy:metadata:evalia_rubrics']                          = 'Rúbricas de evaluación creadas por docentes. Solo se almacena el ID del docente (created_by).';
$string['privacy:metadata:evalia_rubrics:created_by']               = 'ID del docente que creó la rúbrica.';
$string['privacy:metadata:evalia_exams']                            = 'Plantillas de examen creadas por docentes. Solo se almacena el ID del docente (created_by).';
$string['privacy:metadata:evalia_exams:created_by']                 = 'ID del docente que creó el examen.';
$string['privacy:metadata:evalia_student_exams']                    = 'Almacena los exámenes asignados a cada alumno, incluyendo respuestas y calificación.';
$string['privacy:metadata:evalia_student_exams:userid']             = 'ID del alumno.';
$string['privacy:metadata:evalia_student_exams:question_ids']       = 'Array JSON con los IDs de preguntas asignadas al alumno.';
$string['privacy:metadata:evalia_student_exams:answers']            = 'Objeto JSON que mapea IDs de preguntas a respuestas del alumno (puede incluir evaluaciones de ensayo por IA).';
$string['privacy:metadata:evalia_student_exams:score']              = 'Calificación numérica obtenida.';
$string['privacy:metadata:evalia_student_exams:status']             = 'Estado del examen (asignado, en progreso, enviado, calificado, publicado).';
$string['privacy:metadata:evalia_student_exams:timesubmitted']      = 'Marca de tiempo Unix del momento en que el alumno envió el examen.';

$string['privacy:metadata:evalia_portfolio']                        = 'Almacena un resumen del rendimiento en exámenes por alumno por curso.';
$string['privacy:metadata:evalia_portfolio:userid']                 = 'ID del alumno.';
$string['privacy:metadata:evalia_portfolio:avg_grade']              = 'Nota promedio de todos los exámenes calificados en el curso.';
$string['privacy:metadata:evalia_portfolio:total_exams']            = 'Total de exámenes realizados en el curso.';
$string['privacy:metadata:evalia_portfolio:last_activity']          = 'Marca de tiempo Unix de la última actividad en exámenes.';

$string['privacy:metadata:evalia_portfolio_notes']                  = 'Almacena las observaciones del docente sobre un alumno específico.';
$string['privacy:metadata:evalia_portfolio_notes:userid']           = 'ID del alumno observado.';
$string['privacy:metadata:evalia_portfolio_notes:note_text']        = 'Texto de la observación escrita por el docente.';
$string['privacy:metadata:evalia_portfolio_notes:created_by']       = 'ID del docente que escribió la observación.';
$string['privacy:metadata:evalia_portfolio_notes:timecreated']      = 'Marca de tiempo Unix de cuando se registró la observación.';

$string['privacy:metadata:evalia_feedback_log']                     = 'Registro de mensajes de retroalimentación generados por IA y enviados a los alumnos después de la calificación.';
$string['privacy:metadata:evalia_feedback_log:userid']              = 'ID del alumno que recibió el feedback.';
$string['privacy:metadata:evalia_feedback_log:channel']             = 'Canal de entrega (telegram, moodle).';
$string['privacy:metadata:evalia_feedback_log:message_text']        = 'Resumen del mensaje de feedback enviado.';
$string['privacy:metadata:evalia_feedback_log:timesent']            = 'Marca de tiempo Unix del momento en que se envió el mensaje.';
$string['privacy:metadata:evalia_feedback_log:status']              = 'Estado de entrega (enviado, fallido).';

$string['privacy:metadata:saipa_engine']                            = 'El contenido de los exámenes se envía al motor de IA SAIPA para su evaluación. No se incluye información de identificación personal — solo el texto de las preguntas y respuestas anonimizadas.';
$string['privacy:metadata:saipa_engine:question_stems']             = 'Texto de las preguntas utilizado para la evaluación por IA.';
$string['privacy:metadata:saipa_engine:answers']                    = 'Texto de respuestas del alumno anonimizado utilizado para la evaluación por IA.';

// Errores
$string['error_no_rubric']          = 'No se encontró una rúbrica activa para este curso.';
$string['error_engine_unreachable'] = 'No se pudo conectar con el motor de IA. Verifique la configuración del engine SAIPA.';
$string['error_not_enough_bank']    = 'El banco de preguntas no tiene suficientes preguntas aprobadas para completar la estructura solicitada.';
$string['error_already_assigned']   = 'Este alumno ya tiene un examen asignado.';
