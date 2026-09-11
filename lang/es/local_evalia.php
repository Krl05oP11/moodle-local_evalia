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
 * Cadenas de idioma español para local_evalia.
 *
 * @package    local_evalia
 * @copyright  2026 Schaller & Ponce <dev@schaller-ponce.com.ar>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Metadatos del plugin
$string['calendar_exam_event'] = 'Examen: {$a}';
$string['difficulty_advanced']         = 'Avanzada';
$string['difficulty_basic']            = 'Básica';
$string['difficulty_medium']           = 'Media';
$string['error_already_assigned']   = 'Este alumno ya tiene un examen asignado.';
$string['error_engine_unreachable'] = 'No se pudo conectar con el motor de IA. Verifique la configuración del engine SAIPA.';
$string['error_no_rubric']          = 'No se encontró una rúbrica activa para este curso.';
$string['error_not_enough_bank']    = 'El banco de preguntas no tiene suficientes preguntas aprobadas para completar la estructura solicitada.';
$string['evalia:manage'] = 'Gestionar evaluaciones EVAL-IA (docente)';
$string['evalia:take']   = 'Rendir exámenes EVAL-IA (alumno)';
$string['exam_advanced_count']   = 'Preguntas avanzadas';
$string['exam_assign_all']       = 'Asignar a todos los alumnos';
$string['exam_assign_partial']   = 'Se asignó examen a {$a} alumno(s) antes de perder contacto con el motor. Volvé a ejecutar esta acción cuando esté disponible para asignar el resto.';
$string['exam_assigned']         = 'Exámenes asignados. Cada alumno recibió un conjunto único de preguntas.';
$string['exam_assigning']        = 'Asignando exámenes únicos por alumno...';
$string['exam_basic_count']      = 'Preguntas básicas';
$string['exam_create']           = 'Crear Examen';
$string['exam_expired_auto']     = 'Tiempo agotado — el examen fue enviado automáticamente.';
$string['exam_instructions']     = 'Instrucciones para los alumnos';
$string['exam_medium_count']     = 'Preguntas medias';
$string['exam_name']             = 'Nombre del examen';
$string['exam_not_enough_questions'] = 'No hay suficientes preguntas aprobadas en el banco para este examen. Apruebe más preguntas primero.';
$string['exam_status_assigned']  = 'Asignado';
$string['exam_status_graded']    = 'Calificado';
$string['exam_status_started']   = 'En progreso';
$string['exam_status_submitted'] = 'Enviado';
$string['exam_submit_btn']       = 'Enviar examen';
$string['exam_submitted_ok']     = 'Examen enviado correctamente. El docente revisará tu resultado.';
$string['exam_time_limit']       = 'Tiempo límite (minutos)';
$string['exam_timer_label']      = 'Tiempo restante';
$string['nav_my_exams'] = '📝 Mis Exámenes';
$string['plugindescription'] = 'Evaluación inteligente con IA para cursos Moodle. Genera rúbricas, bancos de preguntas y exámenes únicos por alumno usando RAG sobre el material del curso.';
$string['pluginname'] = 'EVAL-IA';
$string['portfolio_avg_grade']      = 'Nota prom.';
$string['portfolio_coming_soon']    = 'Legajos — Disponible en Fase 2';
$string['portfolio_description']    = 'Aquí estarán los legajos de alumnos con historial de exámenes, calificaciones y observaciones del docente.';
$string['portfolio_exam_history']   = 'Historial de Exámenes';
$string['portfolio_export_csv']  = 'Exportar CSV';
$string['portfolio_grade_now']      = 'Calificar';
$string['portfolio_last_activity']  = 'Última actividad';
$string['portfolio_loading']        = 'Cargando legajos...';
$string['portfolio_no_exams']       = 'Sin exámenes calificados aún.';
$string['portfolio_no_students']    = 'No hay alumnos inscriptos en este curso.';
$string['portfolio_note_empty']     = 'Sin observaciones registradas.';
$string['portfolio_note_saved']     = 'Observación guardada.';
$string['portfolio_observations']   = 'Observaciones';
$string['portfolio_select_student'] = 'Seleccione un alumno de la lista para ver su legajo.';
$string['portfolio_students']       = 'Legajos de Alumnos';
$string['portfolio_total_exams']    = 'Exámenes';
$string['privacy:metadata:evalia_exams']                            = 'Plantillas de examen creadas por docentes. Solo se almacena el ID del docente (created_by).';
$string['privacy:metadata:evalia_exams:created_by']                 = 'ID del docente que creó el examen.';
$string['privacy:metadata:evalia_feedback_log']                     = 'Registro de mensajes de retroalimentación generados por IA y enviados a los alumnos después de la calificación.';
$string['privacy:metadata:evalia_feedback_log:channel']             = 'Canal de entrega (telegram, moodle).';
$string['privacy:metadata:evalia_feedback_log:message_text']        = 'Resumen del mensaje de feedback enviado.';
$string['privacy:metadata:evalia_feedback_log:status']              = 'Estado de entrega (enviado, fallido).';
$string['privacy:metadata:evalia_feedback_log:timesent']            = 'Marca de tiempo Unix del momento en que se envió el mensaje.';
$string['privacy:metadata:evalia_feedback_log:userid']              = 'ID del alumno que recibió el feedback.';
$string['privacy:metadata:evalia_portfolio']                        = 'Almacena un resumen del rendimiento en exámenes por alumno por curso.';
$string['privacy:metadata:evalia_portfolio:avg_grade']              = 'Nota promedio de todos los exámenes calificados en el curso.';
$string['privacy:metadata:evalia_portfolio:last_activity']          = 'Marca de tiempo Unix de la última actividad en exámenes.';
$string['privacy:metadata:evalia_portfolio:total_exams']            = 'Total de exámenes realizados en el curso.';
$string['privacy:metadata:evalia_portfolio:userid']                 = 'ID del alumno.';
$string['privacy:metadata:evalia_portfolio_notes']                  = 'Almacena las observaciones del docente sobre un alumno específico.';
$string['privacy:metadata:evalia_portfolio_notes:created_by']       = 'ID del docente que escribió la observación.';
$string['privacy:metadata:evalia_portfolio_notes:note_text']        = 'Texto de la observación escrita por el docente.';
$string['privacy:metadata:evalia_portfolio_notes:timecreated']      = 'Marca de tiempo Unix de cuando se registró la observación.';
$string['privacy:metadata:evalia_portfolio_notes:userid']           = 'ID del alumno observado.';
$string['privacy:metadata:evalia_rubrics']                          = 'Rúbricas de evaluación creadas por docentes. Solo se almacena el ID del docente (created_by).';
$string['privacy:metadata:evalia_rubrics:created_by']               = 'ID del docente que creó la rúbrica.';
$string['privacy:metadata:evalia_student_exams']                    = 'Almacena los exámenes asignados a cada alumno, incluyendo respuestas y calificación.';
$string['privacy:metadata:evalia_student_exams:answers']            = 'Objeto JSON que mapea IDs de preguntas a respuestas del alumno (puede incluir evaluaciones de ensayo por IA).';
$string['privacy:metadata:evalia_student_exams:question_ids']       = 'Array JSON con los IDs de preguntas asignadas al alumno.';
$string['privacy:metadata:evalia_student_exams:score']              = 'Calificación numérica obtenida.';
$string['privacy:metadata:evalia_student_exams:status']             = 'Estado del examen (asignado, en progreso, enviado, calificado, publicado).';
$string['privacy:metadata:evalia_student_exams:timesubmitted']      = 'Marca de tiempo Unix del momento en que el alumno envió el examen.';
$string['privacy:metadata:evalia_student_exams:userid']             = 'ID del alumno.';
$string['privacy:metadata:saipa_engine']                            = 'El contenido de los exámenes se envía al motor de IA SAIPA para su evaluación. No se incluye información de identificación personal — solo el texto de las preguntas y respuestas anonimizadas.';
$string['privacy:metadata:saipa_engine:answers']                    = 'Texto de respuestas del alumno anonimizado utilizado para la evaluación por IA.';
$string['privacy:metadata:saipa_engine:question_stems']             = 'Texto de las preguntas utilizado para la evaluación por IA.';
$string['qtype_essay']                 = 'Ensayo';
$string['qtype_multichoice']           = 'Opción múltiple';
$string['qtype_numerical']             = 'Numérica';
$string['qtype_shortanswer']           = 'Respuesta corta';
$string['qtype_truefalse']             = 'Verdadero/Falso';
$string['questions_approve']           = 'Aprobar';
$string['questions_edit']              = 'Editar';
$string['questions_filter_difficulty'] = 'Filtrar por dificultad';
$string['questions_filter_status']     = 'Filtrar por estado';
$string['questions_filter_topic']      = 'Filtrar por tema';
$string['questions_generate']          = 'Generar Preguntas';
$string['questions_generate_more']     = 'Generar Más';
$string['questions_generating']        = 'Generando preguntas...';
$string['questions_none']              = 'El banco está vacío. Active una rúbrica primero y luego genere preguntas por ítem.';
$string['questions_reject']            = 'Rechazar';
$string['questions_status_approved']   = 'Aprobada';
$string['questions_status_draft']      = 'Pendiente de revisión';
$string['questions_status_rejected']   = 'Rechazada';
$string['rubric_activate']         = 'Activar';
$string['rubric_activated']        = 'Rúbrica activada. Ya puede generar preguntas.';
$string['rubric_generate']         = 'Generar Rúbrica con IA';
$string['rubric_generating']       = 'Generando rúbrica desde el material del curso...';
$string['rubric_item_description'] = 'Descripción';
$string['rubric_item_topic']       = 'Tema';
$string['rubric_item_weight']      = 'Peso de dificultad';
$string['rubric_no_content']       = 'No hay contenido del curso indexado aún. Primero indexe este curso en SAIPA.';
$string['rubric_save']             = 'Guardar Rúbrica';
$string['rubric_saved']            = 'Rúbrica guardada correctamente.';
$string['rubric_status_active']    = 'Activa';
$string['rubric_status_archived']  = 'Archivada';
$string['rubric_status_draft']     = 'Borrador';
$string['student_exam_title']    = 'EVAL-IA — Rendir Examen';
$string['tab_exams']     = 'Exámenes';
$string['tab_portfolio'] = 'Legajos';
$string['tab_questions'] = 'Banco de Preguntas';
$string['tab_rubric']    = 'Rúbrica';
$string['teacher_page_heading'] = 'EVAL-IA: Evaluación Inteligente';
$string['teacher_page_title']   = 'EVAL-IA — Panel Docente';
$string['weight_high']             = 'Alto';
$string['weight_low']              = 'Bajo';
$string['weight_medium']           = 'Medio';

// Panel docente (templates/evalia_teacher.mustache) — texto estático del template.
$string['btn_close']                  = 'Cerrar';
$string['btn_copy']                   = 'Copiar';
$string['btn_download_csv_title']     = 'Descargar CSV';
$string['btn_grade_all']              = 'Calificar todos con IA';
$string['btn_index_material']         = 'Indexar Material';
$string['btn_publish_grades']         = 'Publicar notas';
$string['btn_reload_title']           = 'Recargar';
$string['btn_reset_prompt']           = 'Restaurar predeterminado';
$string['btn_save_note']              = 'Guardar';
$string['btn_save_prompt']            = 'Guardar prompt';
$string['btn_workflow']               = 'Flujo de trabajo';
$string['chat_input_placeholder']     = 'Escribir pregunta...';
$string['chat_welcome_msg']           = '¡Hola! Soy tu asistente EVAL-IA. Puedo guiarte en cada paso del proceso: indexar material, generar rúbricas, armar preguntas, crear y asignar exámenes. ¿En qué paso estás o en qué puedo ayudarte?';
$string['exam_instructions_placeholder'] = 'Lea cada pregunta con atención...';
$string['exam_name_placeholder']      = 'Ej: Parcial 1 — Introducción a IA';
$string['exam_none_created']          = 'Cree un examen para ver el estado por alumno.';
$string['exam_selector_none']         = '— Sin exámenes —';
$string['exam_window_close_label']    = 'Cierre';
$string['exam_window_hint']           = 'Dejá ambos vacíos para examen siempre disponible.';
$string['exam_window_open_label']     = 'Apertura';
$string['exam_window_title']          = 'Ventana de rendición';
$string['fab_aria_label']             = 'Abrir Asistente EVAL-IA';
$string['fab_title']                  = 'Asistente EVAL-IA';
$string['feedback_prompt_desc']       = 'Este texto instruye a la IA sobre cómo redactar el mensaje de retroalimentación que recibirá el alumno tras ser calificado. Podés personalizar el tono, el idioma, el nivel de detalle o cualquier aspecto pedagógico.';
$string['feedback_prompt_title']      = 'Prompt de retroalimentación IA';
$string['filter_all_difficulties']    = 'Todas las dificultades';
$string['filter_all_statuses']        = 'Todos los estados';
$string['filter_all_topics']          = 'Todos los temas';
$string['label_optional']             = '(opcional)';
$string['label_students']             = 'Alumnos';
$string['material_how_desc']          = 'La IA lee el material que el docente subió al curso para generar preguntas relevantes y explicar los errores de los alumnos con contexto del temario real.';
$string['material_how_title']         = '¿Cómo funciona?';
$string['material_indexing']          = 'Indexando páginas, PDFs y presentaciones (PPTX) del curso — puede tardar varios minutos...';
$string['material_step1']             = 'Agregá contenido al curso: <strong>Página</strong> (texto/HTML) o <strong>Archivo</strong> (PDF) desde el menú <em>Agregar actividad o recurso</em>.';
$string['material_step2']             = 'Volvé aquí y hacé clic en <strong>🔍 Indexar Material</strong> — el sistema leerá y procesará todo automáticamente.';
$string['material_step3']             = 'Repetí el paso 2 cada vez que subas nuevo material.';
$string['material_subtitle']          = 'Base de conocimiento para la IA (rúbrica, preguntas y feedback)';
$string['material_title']             = 'Material del Curso';
$string['note_placeholder']           = 'Escribir observación...';
$string['questions_loading']          = 'Cargando preguntas...';
$string['rubric_item_count_title']    = 'Cantidad de ítems en la rúbrica';
$string['rubric_scope_placeholder']   = 'Alcance (ej: Relaciones)';
$string['sources_loading']            = 'Cargando fuentes...';
$string['sources_select_all']         = 'Seleccionar todas';
$string['sources_select_none']        = 'Ninguna';
$string['sources_subtitle']           = '— filtrar qué capítulos usa la IA';
$string['sources_title']              = 'Fuentes del material';
$string['stats_distribution_title']   = 'Distribución de notas';
$string['stats_failed_title']         = 'Preguntas más falladas';
$string['stats_title']                = 'Estadísticas del Examen';
$string['student_link_label']         = 'Enlace para alumnos:';
$string['workflow_modal_title']       = 'Flujo de trabajo — EVAL-IA';
$string['workflow_step1_desc']        = 'Subir PDFs, PPTX o Páginas al curso en Moodle, luego clic en <strong>"🔍 Indexar Material"</strong> (tarjeta azul, Tab Rúbrica). Repetir cada vez que se agrega nuevo contenido.';
$string['workflow_step1_title']       = 'Indexar Material del Curso';
$string['workflow_step2_desc']        = 'Optativo: escribir un <strong>Alcance</strong> (ej: <em>"Relaciones"</em>) para exámenes parciales. Clic en <strong>"✨ Generar Rúbrica con IA"</strong> → revisar ítems → <strong>"💾 Guardar"</strong> y luego <strong>"✅ Activar"</strong>.';
$string['workflow_step2_title']       = 'Generar y Activar la Rúbrica';
$string['workflow_step3_desc']        = 'En el <strong>Tab Banco de Preguntas</strong>: seleccionar tema del dropdown → clic <strong>"✨ Generar Preguntas"</strong> → revisar y <strong>aprobar ✔</strong> o <strong>rechazar ✗</strong> cada pregunta. Repetir por cada tema de la rúbrica.';
$string['workflow_step3_title']       = 'Banco de Preguntas';
$string['workflow_step4_desc']        = 'En el <strong>Tab Exámenes</strong>: definir nombre, instrucciones y distribución de dificultad → <strong>"Crear Examen"</strong> → clic <strong>"📤 Asignar a todos"</strong>. Cada alumno recibe una selección de preguntas única (anti-copia).';
$string['workflow_step4_title']       = 'Crear y Asignar el Examen';
$string['workflow_step5_desc']        = 'Cada alumno accede desde Moodle o por enlace directo, responde las preguntas y hace clic en <strong>"Enviar examen"</strong>. El estado aparece en tiempo real en la tabla de alumnos.';
$string['workflow_step5_title']       = 'Los Alumnos Rinden el Examen';
$string['workflow_step6_desc']        = 'Clic en <strong>"⚡ Calificar todos con IA"</strong> — corrección automática de todos los envíos. Cada alumno recibe nota + explicación pedagógica por <strong>Telegram</strong>. Personalizá el mensaje desde el panel <em>"Prompt de retroalimentación"</em>.';
$string['workflow_step6_title']       = 'Corrección con IA y Feedback';
$string['workflow_step7_desc']        = 'En el <strong>Tab Legajos</strong>: historial de exámenes, promedio y tendencia por alumno. Agregá observaciones personalizadas y exportá a <strong>CSV</strong>.';

// Teacher panel (amd/src/evalia_teacher.js) — dynamic UI text.
$string['btn_grade_all_with_ai']      = '⚡ Calificar todos con IA';
$string['btn_publish_grades_count']   = '✅ Publicar notas ({$a})';
$string['btn_publish_single']         = '✅ Publicar';
$string['confirm_grade_all']          = '¿Calificar {$a} examen(es) con IA? Esto enviará feedback pedagógico a cada alumno por Telegram.';
$string['confirm_publish_all']        = '¿Publicar las notas de {$a} alumno(s) en el libro de calificaciones?';
$string['confirm_reset_prompt']       = '¿Restaurar el prompt predeterminado? Se perderán los cambios guardados.';
$string['default_feedback_prompt']    = 'Eres SAIPA, asistente pedagógico de acompañamiento universitario.
Acabas de conocer el resultado del examen de un alumno y tu misión es enviarle
un mensaje personal, cálido y educativo por Telegram.

El mensaje debe:
1. Saludar al alumno por su nombre de pila
2. Comunicar la nota de forma clara y honesta
3. Por cada pregunta INCORRECTA: explicar brevemente qué respondió el alumno,
   cuál era la respuesta correcta y POR QUÉ esa respuesta es la correcta
4. Si todas fueron correctas: felicitarlo genuinamente
5. Indicar en qué temas conviene profundizar según los errores
6. Cerrar con una frase motivadora: los errores son oportunidades de aprendizaje

Formato: HTML de Telegram (<b>negrita</b>, <i>cursiva</i>). Máximo ~600 palabras.
Tono: cálido, directo, universitario. No paternalista.
Responde ÚNICAMENTE con el mensaje, sin JSON ni comentarios.';
$string['default_rubric_name']        = 'Rúbrica curso {$a}';
$string['error_exam_assign']          = 'Error al asignar exámenes.';
$string['error_exam_create']          = 'Error al crear el examen.';
$string['error_exam_first']           = 'Primero cree un examen.';
$string['error_exam_min_questions']   = 'El examen debe tener al menos 1 pregunta.';
$string['error_exam_name_required']   = 'Ingrese un nombre para el examen.';
$string['error_exam_window_order']    = 'El cierre debe ser posterior a la apertura.';
$string['error_grade_all']            = 'Error al calificar.';
$string['error_grade_exams']          = 'Error al calificar los exámenes.';
$string['error_index_course']         = 'Error al indexar el material del curso.';
$string['error_index_generic']        = 'Error al indexar.';
$string['error_load_bank']            = 'Error al cargar el banco.';
$string['error_load_exam_history']    = 'Error al cargar el historial de exámenes.';
$string['error_load_notes']           = 'Error al cargar observaciones.';
$string['error_load_portfolio']       = 'Error al cargar legajos.';
$string['error_load_rubric']          = 'Error al cargar la rúbrica.';
$string['error_load_sources']         = 'Error al cargar fuentes.';
$string['error_load_students']        = 'Error al cargar alumnos.';
$string['error_no_rubric_to_save']    = 'No hay rúbrica para guardar.';
$string['error_note_required']        = 'Escriba una observación antes de guardar.';
$string['error_note_save']            = 'Error al guardar la observación.';
$string['error_prompt_save']          = 'Error al guardar.';
$string['error_publish_all']          = 'Error al publicar.';
$string['error_publish_grade']        = 'Error al publicar la nota.';
$string['error_publish_grades']       = 'Error al publicar las notas.';
$string['error_question_update']      = 'Error al actualizar la pregunta.';
$string['error_questions_generate']   = 'Error al generar preguntas.';
$string['error_rubric_first']         = 'Primero genere y active una rúbrica.';
$string['error_rubric_generate']      = 'Error al generar la rúbrica.';
$string['error_rubric_generate_failed'] = 'No se pudo generar: {$a}';
$string['error_rubric_must_be_active'] = 'Active la rúbrica antes de generar preguntas.';
$string['error_rubric_needs_item']    = 'La rúbrica debe tener al menos un ítem.';
$string['error_rubric_save']          = 'Error: {$a}';
$string['error_rubric_save_generic']  = 'Error al guardar.';
$string['error_select_qtype']         = 'Seleccione al menos un tipo de pregunta.';
$string['exam_assigned_result']       = 'Asignados: {$a->assigned} alumno(s). Omitidos: {$a->skipped}.';
$string['exam_count_plural']          = '{$a} exámenes';
$string['exam_count_singular']        = '{$a} examen';
$string['exam_created_ok']            = 'Examen creado. Asígnelo a los alumnos cuando el banco esté listo.';
$string['exam_grade_link_title']      = 'Calificar examen enviado';
$string['exam_preview_link_title']    = 'Previsualizar examen asignado';
$string['exam_publish_single_title']  = 'Publicar nota en el gradebook';
$string['exam_status_not_assigned']   = 'Sin asignar';
$string['exam_view_graded_title']     = 'Ver examen calificado';
$string['exam_view_published_title']  = 'Ver examen publicado';
$string['exam_watching_link_title']   = 'El alumno está rindiendo';
$string['fab_connect_error']          = 'Error al conectar con el asistente.';
$string['fab_no_response']            = 'Sin respuesta.';
$string['gen_questions_btn']          = 'Generar';
$string['gen_questions_count_label']  = 'Cantidad';
$string['gen_questions_difficulty_label'] = 'Dificultad';
$string['gen_questions_item_label']   = 'Ítem de rúbrica';
$string['gen_questions_loading']      = 'Generando preguntas desde el material del curso...';
$string['gen_questions_title']        = '✨ Generar Preguntas con IA';
$string['gen_questions_types_label']  = 'Tipos de pregunta';
$string['grade_all_result']           = '✔ {$a->graded} calificado(s)';
$string['grade_all_skipped_suffix']   = ' · {$a} omitido(s)';
$string['grade_published_ok']         = 'Nota publicada en el libro de calificaciones.';
$string['grades_published_result']    = '✅ {$a} nota(s) publicada(s) en el gradebook.';
$string['grading_in_progress']        = '⏳ Calificando...';
$string['index_result_errors']        = '{$a} error(es)';
$string['index_result_nothing']       = 'No se encontró material para indexar';
$string['index_result_sources_chunks'] = '{$a->sources} fuente(s), {$a->chunks} fragmentos indexados';
$string['link_grade']                 = '📝 Calificar';
$string['link_view']                  = '👁 Ver';
$string['link_view_star']             = '★ Ver';
$string['loading_ellipsis']           = 'Cargando...';
$string['loading_exam_history']       = 'Cargando historial...';
$string['loading_observations']       = 'Cargando observaciones...';
$string['no_exams_assigned_yet']      = 'Este alumno no tiene exámenes asignados aún.';
$string['portfolio_average_label']    = 'Promedio: {$a}';
$string['prompt_saved_ok']            = '✔ Guardado';
$string['prompt_saving']              = 'Guardando...';
$string['publishing_all']             = '⏳ Publicando...';
$string['publishing_ellipsis']        = '⏳...';
$string['qtype_short_truefalse']      = 'V/F';
$string['qtype_table_multichoice']    = 'OM';
$string['qtype_table_numerical']      = 'Núm';
$string['qtype_table_shortanswer']    = 'Corta';
$string['question_approved_ok']       = 'Pregunta aprobada.';
$string['question_rejected_ok']       = 'Pregunta rechazada.';
$string['questions_empty_filtered']   = 'No hay preguntas con los filtros seleccionados. Active una rúbrica y use <strong>Generar Preguntas</strong> por ítem.';
$string['questions_generated_ok']     = 'Se generaron {$a} pregunta(s). Apruebe las que considere correctas.';
$string['rubric_empty_state']         = 'No hay rúbrica para este curso. Haga clic en <strong>Generar Rúbrica con IA</strong> para comenzar.';
$string['rubric_generated_ok']        = 'Rúbrica generada. Revísela y active cuando esté lista.';
$string['rubric_item_weight_short']   = 'Peso';
$string['rubric_no_items']            = 'La rúbrica no tiene ítems.';
$string['sources_badge_count']        = '{$a->checked}/{$a->total} fuentes';
$string['sources_none_indexed']       = 'No hay material indexado para este curso.';
$string['stat_average']               = 'Promedio';
$string['stat_graded']                = 'Calificados';
$string['stat_passed']                = 'Aprobados';
$string['stat_pct_failed']            = '{$a}% fallaron';
$string['stat_submitted']             = 'Enviados';
$string['stat_topic_title']           = 'Tema';
$string['stat_total']                 = 'Total';
$string['stats_no_data']              = 'Sin datos aún.';
$string['students_none_enrolled_course'] = 'No hay alumnos inscriptos en este curso.';
$string['students_none_enrolled_exam']   = 'No hay alumnos inscriptos.';
$string['table_action']               = 'Acción';
$string['table_date']                 = 'Fecha';
$string['table_exam']                 = 'Examen';
$string['table_grade']                = 'Nota';
$string['table_question']             = 'Pregunta';
$string['table_questions']            = 'Preguntas';
$string['table_status']               = 'Estado';
$string['table_student']              = 'Alumno';
$string['table_type']                 = 'Tipo';
$string['view_exam_title']            = 'Ver examen';

// Asistente de configuración — pasos 1 a 3.
$string['wizard_btn_back']              = '← Volver';
$string['wizard_btn_next']              = 'Siguiente →';
$string['wizard_feat_gradebook_desc']   = 'Los resultados se publican directamente en el libro de calificaciones nativo de Moodle.';
$string['wizard_feat_gradebook_title']  = 'Integración con el libro de calificaciones';
$string['wizard_feat_grading_desc']     = 'Las preguntas objetivas se califican al instante. Los ensayos se evalúan con el LLM usando contexto RAG.';
$string['wizard_feat_grading_title']    = 'Calificación con IA';
$string['wizard_feat_qbank_desc']       = 'Genera preguntas de opción múltiple, verdadero/falso, numéricas, respuesta corta y ensayo por tema.';
$string['wizard_feat_qbank_title']      = 'Banco de preguntas';
$string['wizard_feat_rubric_desc']      = 'Genera rúbricas de evaluación estructuradas a partir del material del curso indexado, en segundos.';
$string['wizard_feat_rubric_title']     = 'Generación de rúbricas con IA';
$string['wizard_feat_telegram_desc']    = 'Los alumnos reciben retroalimentación pedagógica generada por IA vía Telegram luego de la calificación.';
$string['wizard_feat_telegram_title']   = 'Retroalimentación por Telegram';
$string['wizard_feat_unique_desc']      = 'Cada alumno recibe un conjunto de preguntas diferente, reduciendo el riesgo de copia.';
$string['wizard_feat_unique_title']     = 'Exámenes únicos por alumno';
$string['wizard_mode_cloud_desc']       = 'saipa-engine configurado con una clave de API compatible con OpenAI. No requiere GPU local.';
$string['wizard_mode_cloud_title']      = 'API en la nube';
$string['wizard_mode_custom_desc']      = 'Cualquier motor compatible en una URL personalizada. Control total para despliegues avanzados.';
$string['wizard_mode_custom_title']     = 'Personalizado / Empresa';
$string['wizard_mode_intro']            = 'Seleccione la opción que corresponde a su infraestructura de IA desplegada.';
$string['wizard_mode_local_desc']       = 'saipa-engine ejecutándose en su servidor con Ollama como backend LLM. Privacidad total de los datos.';
$string['wizard_mode_local_title']      = 'Local — Ollama';
$string['wizard_mode_saipa_desc']       = 'Motor totalmente gestionado por Schaller & Ponce. Suscríbase y conéctese con una única clave de API.';
$string['wizard_mode_saipa_title']      = 'SAIPA Cloud';
$string['wizard_mode_title']            = 'Elija su modo de aprovisionamiento de IA';
$string['wizard_prov_cloud_desc']       = 'Utilice cualquier proveedor de API compatible con OpenAI (OpenAI, Azure OpenAI, Groq, Mistral, etc.) con su propia clave de API.';
$string['wizard_prov_cloud_li1']        = 'No requiere GPU local';
$string['wizard_prov_cloud_li2']        = 'El costo de la clave de API depende del uso y del proveedor';
$string['wizard_prov_cloud_li3']        = 'Configure <code>OPENAI_API_KEY</code> en el archivo <code>.env</code> de saipa-engine';
$string['wizard_prov_cloud_title']      = 'API en la nube';
$string['wizard_prov_custom_desc']      = 'Apunte EVAL-IA a cualquier URL de motor que exponga una API REST compatible (por ejemplo, su propia bifurcación de FastAPI, despliegue local o nube privada).';
$string['wizard_prov_custom_li1']       = 'Debe implementar <code>GET /health</code> devolviendo <code>{"status":"ok"}</code>';
$string['wizard_prov_custom_li2']       = 'Debe implementar <code>POST /eval/rubric/generate</code> y los endpoints relacionados';
$string['wizard_prov_custom_title']     = 'Personalizado / Empresa';
$string['wizard_prov_header']           = '🤖 Aprovisionamiento del servicio de IA — elija una opción';
$string['wizard_prov_intro']            = 'El LLM que impulsa EVAL-IA puede provenir de tres fuentes. Debe tener al menos una opción lista antes de continuar.';
$string['wizard_prov_local_desc']       = 'Ejecute el LLM en su propio servidor usando <a href="https://ollama.com" target="_blank">Ollama</a>. Privacidad total — ningún dato sale de su infraestructura.';
$string['wizard_prov_local_li1']        = 'Modelo recomendado: <code>qwen2.5:14b</code> (requiere ≥16 GB de RAM)';
$string['wizard_prov_local_li2']        = 'Mínimo: cualquier modelo 7B con ≥8 GB de RAM';
$string['wizard_prov_local_li3']        = 'saipa-engine debe ejecutarse en el mismo equipo o tener acceso de red a Ollama';
$string['wizard_prov_local_title']      = 'Local — Ollama';
$string['wizard_prov_saipa_desc']       = 'Motor totalmente gestionado por Schaller &amp; Ponce. Sin instalar Ollama ni ChromaDB. Suscríbase y conéctese con una única clave de API.';
$string['wizard_prov_saipa_li1']        = 'Cero infraestructura que administrar';
$string['wizard_prov_saipa_li2']        = 'Únase a la lista de espera en <code>cloud.saipa.online</code>';
$string['wizard_prov_saipa_title']      = 'SAIPA Cloud';
$string['wizard_prov_warning']          = '<strong>⛔ Sin un servicio de IA activo, EVAL-IA no podrá:</strong> indexar material del curso, generar rúbricas, crear preguntas, calificar exámenes ni entregar retroalimentación. Todas estas funciones dependen exclusivamente del motor de IA. <strong>No continúe</strong> a menos que tenga una de las opciones de arriba desplegada y lista.';
$string['wizard_req_chroma_desc']       = 'Base de datos vectorial que almacena el material del curso indexado.';
$string['wizard_req_chroma_label']      = 'ChromaDB (embebido en saipa-engine)';
$string['wizard_req_chroma_value']      = 'Incluido en el motor';
$string['wizard_req_confirm']           = 'He leído los requisitos anteriores. Hay un servicio de IA (saipa-engine + LLM) desplegado y accesible desde este servidor.';
$string['wizard_req_curl_desc']         = 'Necesaria para comunicarse con el motor de IA.';
$string['wizard_req_curl_enabled']      = 'Habilitada';
$string['wizard_req_curl_label']        = 'Extensión PHP cURL';
$string['wizard_req_curl_missing']      = 'Ausente';
$string['wizard_req_db_desc']           = 'MySQL 8+ / MariaDB 10.6+ / PostgreSQL 13+';
$string['wizard_req_db_label']          = 'Base de datos';
$string['wizard_req_engine_badge']      = 'Debe desplegarse por separado';
$string['wizard_req_engine_desc']       = 'Maneja toda la inferencia del LLM, la búsqueda vectorial (ChromaDB) y la recuperación RAG.';
$string['wizard_req_engine_header']     = '⚠️ Motor de IA — <em>Requerido. EVAL-IA no funcionará sin esto.</em>';
$string['wizard_req_engine_intro']      = 'EVAL-IA utiliza un servicio Python complementario llamado <strong>saipa-engine</strong> para ejecutar todas las operaciones de IA: generación de rúbricas, creación de preguntas, calificación de exámenes y entrega de retroalimentación. Este servicio debe estar en ejecución y ser accesible desde este servidor Moodle antes de poder usar cualquier función de EVAL-IA.';
$string['wizard_req_engine_label']      = 'saipa-engine (Python 3.11+ / FastAPI)';
$string['wizard_req_intro']             = 'Por favor, verifique que su entorno cumple con todos los requisitos antes de continuar. <strong>EVAL-IA no funcionará sin un servicio de IA activo.</strong>';
$string['wizard_req_llm_badge']         = 'Servicio de IA requerido';
$string['wizard_req_llm_desc']          = 'Genera rúbricas, preguntas, califica ensayos y redacta retroalimentación. Vea las opciones de aprovisionamiento más abajo.';
$string['wizard_req_llm_label']         = 'Modelo de lenguaje grande (LLM)';
$string['wizard_req_moodle_desc']       = 'Las versiones anteriores no están soportadas.';
$string['wizard_req_moodle_label']      = 'Moodle 4.4 o 4.5';
$string['wizard_req_php_desc']          = 'PHP 7.x no está soportado.';
$string['wizard_req_php_label']         = 'PHP 8.1+';
$string['wizard_req_platform_header']   = '🖥️ Plataforma';
$string['wizard_req_title']             = 'Requisitos mínimos';
$string['wizard_welcome_intro']         = 'EVAL-IA automatiza su flujo de evaluación usando IA y Generación Aumentada por Recuperación (RAG) sobre el material de su propio curso:';
$string['wizard_welcome_subtitle']      = 'Este asistente configurará la conexión al motor de IA en unos pocos pasos.';
$string['wizard_welcome_title']         = 'Bienvenido a EVAL-IA';

// Asistente de configuración — pasos 4 a 6 y cadenas JavaScript en tiempo de ejecución.
$string['wizard_btn_retry']             = '↻ Reintentar';
$string['wizard_btn_save_finish']       = '✅ Guardar y finalizar';
$string['wizard_btn_test']              = 'Probar conexión →';
$string['wizard_connecting']            = 'Conectando…';
$string['wizard_done_admin_btn']        = '⚙️ Configuración de administración';
$string['wizard_done_body']             = 'EVAL-IA está conectado al motor de IA y listo para usarse.<br>Abra cualquier curso y vaya a <strong>EVAL-IA → Panel docente</strong> para comenzar.';
$string['wizard_done_courses_btn']      = 'Ir a mis cursos →';
$string['wizard_done_title']            = '¡Configuración guardada!';
$string['wizard_hint_cloud_body']       = 'Ingrese la URL donde está desplegado saipa-engine (con Cloud API configurado) y el token <code>SAIPA_API_TOKEN</code>. El motor utilizará su clave de API en la nube internamente.';
$string['wizard_hint_cloud_title']      = '☁️ API en la nube:';
$string['wizard_hint_custom_body']      = 'Ingrese la URL base de su motor. El asistente probará <code>{url}/health</code>. La autenticación usa un token Bearer estándar.';
$string['wizard_hint_custom_title']     = '⚙️ Personalizado / Empresa:';
$string['wizard_hint_local_body']       = 'El puerto por defecto de saipa-engine es <code>8052</code>. Si lo está ejecutando con Docker en el mismo equipo, use <code>http://localhost:8052</code>. El token es opcional, salvo que haya configurado <code>SAIPA_API_TOKEN</code> en <code>.env</code>.';
$string['wizard_hint_local_title']      = '🖥️ Local / Ollama:';
$string['wizard_hint_saipa_body']       = 'Cuando se lance, la URL del motor será <code>https://engine.saipa.online</code> y el token será su clave de API de suscripción. Por ahora, seleccione otro modo para continuar.';
$string['wizard_hint_saipa_title']      = '🌐 SAIPA Cloud aún no está disponible.';
$string['wizard_js_connecting_engine']  = 'Conectando al motor…';
$string['wizard_js_connection_failed']  = 'Conexión fallida';
$string['wizard_js_engine_reachable']   = 'Motor accesible';
$string['wizard_js_engine_version']     = 'Versión del motor';
$string['wizard_js_error_label']        = 'Error:';
$string['wizard_js_network_error']      = 'Error de red';
$string['wizard_js_success_msg']        = '🎉 <strong>¡Conexión exitosa!</strong> Haga clic en <em>Guardar y finalizar</em> para almacenar la configuración.';
$string['wizard_js_troubleshoot_header'] = 'Lista de verificación para resolución de problemas:';
$string['wizard_js_troubleshoot_li1']   = '¿Está saipa-engine en ejecución? Ejecute: <code>docker compose ps</code>';
$string['wizard_js_troubleshoot_li2']   = '¿Es correcta la URL? (predeterminada: <code>http://localhost:8052</code>)';
$string['wizard_js_troubleshoot_li3']   = 'Si usa un token, ¿coincide con <code>SAIPA_API_TOKEN</code> en <code>.env</code>?';
$string['wizard_js_troubleshoot_li4']   = '¿Hay un firewall o proxy inverso bloqueando el puerto 8052?';
$string['wizard_js_troubleshoot_li5']   = 'Si Moodle se ejecuta dentro de Docker, use el nombre del contenedor, no <code>localhost</code>.';
$string['wizard_js_unknown_error']      = 'Error desconocido';
$string['wizard_js_uptime']             = 'Tiempo activo';
$string['wizard_js_url_empty']          = 'La URL del motor está vacía. Vuelva atrás e ingrese una URL.';
$string['wizard_step4_intro']           = 'Ingrese la URL y el token de autenticación para saipa-engine.';
$string['wizard_step4_title']           = 'Datos de conexión';
$string['wizard_step5_intro']           = 'Verificando la conectividad con el motor SAIPA…';
$string['wizard_step5_title']           = 'Prueba de conexión';
$string['wizard_token_help']            = 'Valor de <code>SAIPA_API_TOKEN</code> en el archivo <code>.env</code> del motor. Déjelo en blanco si no configuró un secreto.';
$string['wizard_token_label']           = 'Token del motor';
$string['wizard_token_placeholder']     = 'Déjelo en blanco si no está configurado';
$string['wizard_url_help']              = 'URL base de saipa-engine, sin barra al final.';
$string['wizard_url_label']             = 'URL del motor';

// Asistente de configuración — cabeceras de página, barra de progreso, pantalla final y mensajes del lado PHP.
$string['wizard_completion_body']       = 'El motor de IA ha sido configurado. Ya puede generar rúbricas,<br>crear bancos de preguntas y asignar exámenes a sus alumnos.';
$string['wizard_completion_title']      = '¡EVAL-IA está listo!';
$string['wizard_err_connection']        = 'Conexión fallida: {$a}';
$string['wizard_err_http_status']       = 'El motor devolvió HTTP {$a}. Verifique la URL y el token.';
$string['wizard_err_unexpected']        = 'Respuesta inesperada del motor: {$a}';
$string['wizard_err_url_required']      = 'La URL del motor es obligatoria.';
$string['wizard_page_heading']          = 'Asistente de configuración de EVAL-IA';
$string['wizard_page_title']            = 'EVAL-IA — Asistente de configuración';
$string['wizard_save_success']          = 'Configuración guardada correctamente.';
$string['wizard_step_ai_mode']          = 'Modo IA';
$string['wizard_step_connect']          = 'Conectar';
$string['wizard_step_done']             = 'Listo';
$string['wizard_step_requirements']     = 'Requisitos';
$string['wizard_step_test']             = 'Prueba';
$string['wizard_step_welcome']          = 'Bienvenida';

// Admin settings.
$string['settings:engine_token']               = 'Token de autenticación del motor';
$string['settings:engine_token_desc']          = 'Bearer token configurado en el engine. Dejar en blanco para heredar el token de SAIPA.';
$string['settings:engine_url']                 = 'URL del motor de IA';
$string['settings:engine_url_desc']            = 'URL base del engine, por ejemplo: <code>http://localhost:8052</code> o '
    . '<code>https://engine.saipa.online</code>. Dejar en blanco para heredar la configuración de SAIPA.';
$string['settings:exam_default_advanced']      = 'Preguntas avanzadas por defecto';
$string['settings:exam_default_advanced_desc'] = 'Cantidad inicial de preguntas avanzadas al crear un examen.';
$string['settings:exam_default_basic']         = 'Preguntas básicas por defecto';
$string['settings:exam_default_basic_desc']    = 'Cantidad inicial de preguntas básicas al crear un examen.';
$string['settings:exam_default_medium']        = 'Preguntas medias por defecto';
$string['settings:exam_default_medium_desc']   = 'Cantidad inicial de preguntas medias al crear un examen.';
$string['settings:exam_default_time_limit']      = 'Tiempo límite por defecto (minutos)';
$string['settings:exam_default_time_limit_desc'] = 'Valor inicial del campo tiempo límite. Usar 0 para sin límite.';
$string['settings:feedback_telegram']          = 'Enviar feedback por Telegram';
$string['settings:feedback_telegram_desc']     = 'Al calificar un examen, envía al alumno su resultado y análisis '
    . 'pedagógico vía Telegram. Requiere que el alumno tenga vinculada su cuenta de Telegram en SAIPA.';
$string['settings:heading_engine']             = '🤖 Motor de IA (SAIPA Engine)';
$string['settings:heading_engine_desc']        = 'URL y token del motor de IA. Si se dejan en blanco, EVAL-IA utiliza '
    . 'la configuración del plugin SAIPA (si está instalado). Configúrelos aquí para despliegues independientes '
    . 'donde SAIPA no está instalado.';
$string['settings:heading_exam']               = '📝 Exámenes';
$string['settings:heading_feedback']           = '💬 Feedback';
$string['settings:heading_pdf']                = '📄 Indexado de PDFs';
$string['settings:heading_questions']          = '❓ Generación de preguntas';
$string['settings:heading_rubric']             = '📋 Rúbricas';
$string['settings:heading_weights']            = '⚖️ Ponderación por dificultad';
$string['settings:heading_weights_desc']       = 'Puntos asignados a cada pregunta según su dificultad. La nota final '
    . 'se calcula como (pesos correctos / total pesos) × 10.';
$string['settings:heading_wizard']             = '🚀 Setup Wizard';
$string['settings:heading_wizard_desc']        = 'Use the wizard to configure the AI engine step by step, with a '
    . 'real-time connection test.';
$string['settings:launch_wizard']              = '▶ Launch Setup Wizard';
$string['settings:pdf_page_ranges']            = 'Rangos de páginas por archivo PDF';
$string['settings:pdf_page_ranges_desc']       = 'JSON que mapea nombres de archivo a capítulos. Permite indexar cada '
    . 'capítulo como fuente independiente.<br>Formato: <code>{"archivo.pdf": [{"from": 1, "to": 50, "label": '
    . '"Cap1-Tema"}, ...]}</code><br>Dejar vacío para indexar el PDF completo sin dividir.';
$string['settings:questions_default_count']      = 'Preguntas por ítem de rúbrica (por defecto)';
$string['settings:questions_default_count_desc'] = 'Cantidad inicial en el diálogo "Generar preguntas" por cada ítem. '
    . 'Rango recomendado: 3–10.';
$string['settings:rubric_default_items']       = 'Cantidad de ítems por defecto';
$string['settings:rubric_default_items_desc']  = 'Valor inicial del campo al abrir el formulario de generación (rango: 5–40).';
$string['settings:weight_advanced']            = 'Peso — preguntas avanzadas';
$string['settings:weight_basic']               = 'Peso — preguntas básicas';
$string['settings:weight_medium']              = 'Peso — preguntas medias';

// Student panel (student.php).
$string['student:btn_continue']         = '▶️ Continuar →';
$string['student:btn_detail']           = 'Ver detalle →';
$string['student:btn_take']             = '📝 Rendir →';
$string['student:heading']              = '📝 Mis Exámenes';
$string['student:meta_from']            = '📅 Desde: {$a}';
$string['student:meta_timelimit']       = '⏱ Tiempo límite: <strong>{$a} min</strong>';
$string['student:meta_until']           = '⏰ Hasta: {$a}';
$string['student:msg_graded']           = 'Tu examen ya fue calificado. La nota estará disponible en cuanto el docente la publique.';
$string['student:msg_submitted_on']     = 'Examen enviado el {$a}. El docente lo revisará pronto.';
$string['student:none_assigned']        = 'No tenés exámenes asignados todavía.';
$string['student:none_assigned_desc']   = 'El docente te notificará cuando haya un examen disponible para este curso.';
$string['student:page_title']           = 'Mis Exámenes — EVAL-IA';
$string['student:status_assigned']      = 'Pendiente';
$string['student:status_graded']        = 'Calificado';
$string['student:status_published']     = '✅ Publicado';
$string['student:status_started']       = 'En progreso';
$string['student:status_submitted']     = 'Enviado';
$string['student:window_closed']        = '🔒 El período de entrega cerró el {$a}';
$string['student:window_opens']         = '🕐 Disponible a partir del {$a}';

// Exam-taking page (student_exam.php).
$string['exam:ai_score_label']          = 'IA: {$a}%';
$string['exam:btn_grade_ai']            = '⚡ Calificar con IA';
$string['exam:btn_submit_exam']         = '📤 Enviar examen';
$string['exam:correct_answer_label']    = 'Respuesta correcta:';
$string['exam:grade_panel_desc']        = 'Al confirmar, el engine de IA evaluará las respuestas del alumno y asignará una nota automáticamente.';
$string['exam:grade_panel_title']       = '📝 Calificar examen';
$string['exam:page_title']              = '{$a} — EVAL-IA';
$string['exam:placeholder_essay']       = 'Redactá tu respuesta aquí';
$string['exam:placeholder_numerical']   = 'Ingresá un valor numérico';
$string['exam:placeholder_shortanswer'] = 'Escribí tu respuesta';
$string['exam:points_of']               = '{$a->obtained}/{$a->total} pts';
$string['exam:points_plain']            = '{$a} pts';
$string['exam:preview_banner_title']    = 'Vista docente — solo lectura';
$string['exam:preview_graded_msg']      = '<strong>Calificado:</strong> {$a} / 10.0';
$string['exam:preview_status_label']    = 'Estado:';
$string['exam:preview_student_fallback'] = 'alumno';
$string['exam:preview_student_label']   = 'Alumno:';
$string['exam:question_num']            = 'Pregunta {$a}';
$string['exam:status_assigned']         = 'Asignado (aún no iniciado)';
$string['exam:status_graded']           = 'Calificado';
$string['exam:status_started']          = 'En progreso';
$string['exam:status_submitted_review'] = 'Enviado — pendiente de calificación';
$string['exam:submitted_grade_msg']     = 'Tu nota: <strong>{$a} / 10.0</strong>. El docente ya calificó tu examen.';
$string['exam:submitted_heading']       = '✅ Examen enviado';
$string['exam:submitted_pending_msg']   = 'Tu examen fue recibido correctamente. El docente lo revisará próximamente.';
$string['exam:timer_autosubmit']        = 'El examen se envía automáticamente al llegar a 00:00.';
$string['exam:timer_remaining']         = '⏱ Tiempo restante:';
