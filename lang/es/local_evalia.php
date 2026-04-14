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
