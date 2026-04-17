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
 * Brazilian Portuguese language strings for local_evalia.
 *
 * @package    local_evalia
 * @copyright  2026 Schaller & Ponce <dev@schaller-ponce.com.ar>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Metadados do plugin
$string['calendar_exam_event'] = 'Prova: {$a}';
$string['difficulty_advanced']         = 'Avançada';
$string['difficulty_basic']            = 'Básica';
$string['difficulty_medium']           = 'Média';
$string['error_already_assigned']   = 'Este aluno já possui uma prova atribuída.';
$string['error_engine_unreachable'] = 'Não foi possível conectar ao motor de IA. Verifique a configuração do engine SAIPA.';
$string['error_no_rubric']          = 'Nenhuma rubrica ativa encontrada para este curso.';
$string['error_not_enough_bank']    = 'O banco de questões não possui questões aprovadas suficientes para completar a estrutura solicitada.';
$string['evalia:manage'] = 'Gerenciar avaliações EVAL-IA (professor)';
$string['evalia:take']   = 'Realizar provas EVAL-IA (aluno)';
$string['exam_advanced_count']   = 'Questões avançadas';
$string['exam_assign_all']       = 'Atribuir a todos os alunos';
$string['exam_assigned']         = 'Provas atribuídas. Cada aluno recebeu um conjunto único de questões.';
$string['exam_assigning']        = 'Atribuindo provas únicas por aluno...';
$string['exam_basic_count']      = 'Questões básicas';
$string['exam_create']           = 'Criar Prova';
$string['exam_expired_auto']     = 'Tempo esgotado — a prova foi enviada automaticamente.';
$string['exam_instructions']     = 'Instruções para os alunos';
$string['exam_medium_count']     = 'Questões médias';
$string['exam_name']             = 'Nome da prova';
$string['exam_not_enough_questions'] = 'Não há questões aprovadas suficientes no banco para esta prova. Aprove mais questões primeiro.';
$string['exam_status_assigned']  = 'Atribuída';
$string['exam_status_graded']    = 'Corrigida';
$string['exam_status_started']   = 'Em andamento';
$string['exam_status_submitted'] = 'Enviada';
$string['exam_submit_btn']       = 'Enviar prova';
$string['exam_submitted_ok']     = 'Prova enviada com sucesso. O professor revisará seu resultado.';
$string['exam_time_limit']       = 'Tempo limite (minutos)';
$string['exam_timer_label']      = 'Tempo restante';
$string['nav_my_exams'] = '📝 Minhas Provas';
$string['plugindescription'] = 'Avaliação inteligente com IA para cursos Moodle. Gera rubricas, bancos de questões e provas únicas por aluno usando RAG sobre o material do curso.';
$string['pluginname'] = 'EVAL-IA';
$string['portfolio_avg_grade']      = 'Nota média';
$string['portfolio_coming_soon']    = 'Dossiês — Disponível na Fase 2';
$string['portfolio_description']    = 'Aqui estarão os dossiês dos alunos com histórico de provas, notas e observações do professor.';
$string['portfolio_exam_history']   = 'Histórico de Provas';
$string['portfolio_export_csv']  = 'Exportar CSV';
$string['portfolio_grade_now']      = 'Corrigir';
$string['portfolio_last_activity']  = 'Última atividade';
$string['portfolio_loading']        = 'Carregando dossiês...';
$string['portfolio_no_exams']       = 'Nenhuma prova corrigida ainda.';
$string['portfolio_no_students']    = 'Nenhum aluno matriculado neste curso.';
$string['portfolio_note_empty']     = 'Nenhuma observação registrada.';
$string['portfolio_note_saved']     = 'Observação salva.';
$string['portfolio_observations']   = 'Observações';
$string['portfolio_select_student'] = 'Selecione um aluno da lista para ver seu dossiê.';
$string['portfolio_students']       = 'Dossiês dos Alunos';
$string['portfolio_total_exams']    = 'Provas';
$string['privacy:metadata:evalia_exams']                            = 'Modelos de prova criados por professores. Apenas o ID do professor (created_by) é armazenado.';
$string['privacy:metadata:evalia_exams:created_by']                 = 'ID do professor que criou a prova.';
$string['privacy:metadata:evalia_feedback_log']                     = 'Registro de mensagens de feedback geradas por IA e enviadas aos alunos após a correção.';
$string['privacy:metadata:evalia_feedback_log:channel']             = 'Canal de entrega (telegram, moodle).';
$string['privacy:metadata:evalia_feedback_log:message_text']        = 'Resumo da mensagem de feedback enviada.';
$string['privacy:metadata:evalia_feedback_log:status']              = 'Status de entrega (enviado, falhou).';
$string['privacy:metadata:evalia_feedback_log:timesent']            = 'Timestamp Unix do momento em que a mensagem foi enviada.';
$string['privacy:metadata:evalia_feedback_log:userid']              = 'ID do aluno que recebeu o feedback.';
$string['privacy:metadata:evalia_portfolio']                        = 'Armazena um resumo do desempenho em provas por aluno por curso.';
$string['privacy:metadata:evalia_portfolio:avg_grade']              = 'Nota média de todas as provas corrigidas no curso.';
$string['privacy:metadata:evalia_portfolio:last_activity']          = 'Timestamp Unix da última atividade em provas.';
$string['privacy:metadata:evalia_portfolio:total_exams']            = 'Total de provas realizadas no curso.';
$string['privacy:metadata:evalia_portfolio:userid']                 = 'ID do aluno.';
$string['privacy:metadata:evalia_portfolio_notes']                  = 'Armazena as observações do professor sobre um aluno específico.';
$string['privacy:metadata:evalia_portfolio_notes:created_by']       = 'ID do professor que escreveu a observação.';
$string['privacy:metadata:evalia_portfolio_notes:note_text']        = 'Texto da observação escrita pelo professor.';
$string['privacy:metadata:evalia_portfolio_notes:timecreated']      = 'Timestamp Unix de quando a observação foi registrada.';
$string['privacy:metadata:evalia_portfolio_notes:userid']           = 'ID do aluno observado.';
$string['privacy:metadata:evalia_rubrics']                          = 'Rubricas de avaliação criadas por professores. Apenas o ID do professor (created_by) é armazenado.';
$string['privacy:metadata:evalia_rubrics:created_by']               = 'ID do professor que criou a rubrica.';
$string['privacy:metadata:evalia_student_exams']                    = 'Armazena as provas atribuídas a cada aluno, incluindo respostas e nota.';
$string['privacy:metadata:evalia_student_exams:answers']            = 'Objeto JSON que mapeia IDs de questões para respostas do aluno (pode incluir avaliações de dissertação por IA).';
$string['privacy:metadata:evalia_student_exams:question_ids']       = 'Array JSON com os IDs de questões atribuídas ao aluno.';
$string['privacy:metadata:evalia_student_exams:score']              = 'Nota numérica obtida.';
$string['privacy:metadata:evalia_student_exams:status']             = 'Status da prova (atribuída, em andamento, enviada, corrigida, publicada).';
$string['privacy:metadata:evalia_student_exams:timesubmitted']      = 'Timestamp Unix do momento em que o aluno enviou a prova.';
$string['privacy:metadata:evalia_student_exams:userid']             = 'ID do aluno.';
$string['privacy:metadata:saipa_engine']                            = 'O conteúdo das provas é enviado ao motor de IA SAIPA para correção. Nenhuma informação de identificação pessoal é incluída — apenas o texto das questões e respostas anonimizadas.';
$string['privacy:metadata:saipa_engine:answers']                    = 'Texto de respostas do aluno anonimizado utilizado para avaliação por IA.';
$string['privacy:metadata:saipa_engine:question_stems']             = 'Texto das questões utilizado para avaliação por IA.';
$string['qtype_essay']                 = 'Dissertação';
$string['qtype_multichoice']           = 'Múltipla escolha';
$string['qtype_numerical']             = 'Numérica';
$string['qtype_shortanswer']           = 'Resposta curta';
$string['qtype_truefalse']             = 'Verdadeiro/Falso';
$string['questions_approve']           = 'Aprovar';
$string['questions_edit']              = 'Editar';
$string['questions_filter_difficulty'] = 'Filtrar por dificuldade';
$string['questions_filter_status']     = 'Filtrar por status';
$string['questions_filter_topic']      = 'Filtrar por tema';
$string['questions_generate']          = 'Gerar Questões';
$string['questions_generate_more']     = 'Gerar Mais';
$string['questions_generating']        = 'Gerando questões...';
$string['questions_none']              = 'O banco está vazio. Ative uma rubrica primeiro e depois gere questões por item.';
$string['questions_reject']            = 'Rejeitar';
$string['questions_status_approved']   = 'Aprovada';
$string['questions_status_draft']      = 'Pendente de revisão';
$string['questions_status_rejected']   = 'Rejeitada';
$string['rubric_activate']         = 'Ativar';
$string['rubric_activated']        = 'Rubrica ativada. Agora você pode gerar questões.';
$string['rubric_generate']         = 'Gerar Rubrica com IA';
$string['rubric_generating']       = 'Gerando rubrica a partir do material do curso...';
$string['rubric_item_description'] = 'Descrição';
$string['rubric_item_topic']       = 'Tema';
$string['rubric_item_weight']      = 'Peso de dificuldade';
$string['rubric_no_content']       = 'Nenhum conteúdo do curso indexado ainda. Primeiro indexe este curso no SAIPA.';
$string['rubric_save']             = 'Salvar Rubrica';
$string['rubric_saved']            = 'Rubrica salva com sucesso.';
$string['rubric_status_active']    = 'Ativa';
$string['rubric_status_archived']  = 'Arquivada';
$string['rubric_status_draft']     = 'Rascunho';
$string['student_exam_title']    = 'EVAL-IA — Realizar Prova';
$string['tab_exams']     = 'Provas';
$string['tab_portfolio'] = 'Dossiês';
$string['tab_questions'] = 'Banco de Questões';
$string['tab_rubric']    = 'Rubrica';
$string['teacher_page_heading'] = 'EVAL-IA: Avaliação Inteligente';
$string['teacher_page_title']   = 'EVAL-IA — Painel do Professor';
$string['weight_high']             = 'Alto';
$string['weight_low']              = 'Baixo';
$string['weight_medium']           = 'Médio';

// Assistente de configuração — etapas 1 a 3.
$string['wizard_btn_back']              = '← Voltar';
$string['wizard_btn_next']              = 'Próximo →';
$string['wizard_feat_gradebook_desc']   = 'Os resultados são publicados diretamente no livro de notas nativo do Moodle.';
$string['wizard_feat_gradebook_title']  = 'Integração com o livro de notas';
$string['wizard_feat_grading_desc']     = 'As questões objetivas são corrigidas instantaneamente. As dissertações são avaliadas pelo LLM usando contexto RAG.';
$string['wizard_feat_grading_title']    = 'Correção com IA';
$string['wizard_feat_qbank_desc']       = 'Gera questões de múltipla escolha, verdadeiro/falso, numéricas, resposta curta e dissertação por tema.';
$string['wizard_feat_qbank_title']      = 'Banco de questões';
$string['wizard_feat_rubric_desc']      = 'Gera rubricas de avaliação estruturadas a partir do material do curso indexado, em segundos.';
$string['wizard_feat_rubric_title']     = 'Geração de rubricas com IA';
$string['wizard_feat_telegram_desc']    = 'Os alunos recebem feedback pedagógico gerado por IA via Telegram após a correção.';
$string['wizard_feat_telegram_title']   = 'Feedback por Telegram';
$string['wizard_feat_unique_desc']      = 'Cada aluno recebe um conjunto de questões diferente, reduzindo o risco de cópia.';
$string['wizard_feat_unique_title']     = 'Provas únicas por aluno';
$string['wizard_mode_cloud_desc']       = 'saipa-engine configurado com uma chave de API compatível com OpenAI. Não requer GPU local.';
$string['wizard_mode_cloud_title']      = 'API na nuvem';
$string['wizard_mode_custom_desc']      = 'Qualquer motor compatível em uma URL personalizada. Controle total para implantações avançadas.';
$string['wizard_mode_custom_title']     = 'Personalizado / Empresa';
$string['wizard_mode_intro']            = 'Selecione a opção que corresponde à sua infraestrutura de IA implantada.';
$string['wizard_mode_local_desc']       = 'saipa-engine executando em seu servidor com Ollama como backend LLM. Privacidade total dos dados.';
$string['wizard_mode_local_title']      = 'Local — Ollama';
$string['wizard_mode_saipa_desc']       = 'Motor totalmente gerenciado por Schaller & Ponce. Assine e conecte-se com uma única chave de API.';
$string['wizard_mode_saipa_title']      = 'SAIPA Cloud';
$string['wizard_mode_title']            = 'Escolha seu modo de provisionamento de IA';
$string['wizard_prov_cloud_desc']       = 'Utilize qualquer provedor de API compatível com OpenAI (OpenAI, Azure OpenAI, Groq, Mistral, etc.) com sua própria chave de API.';
$string['wizard_prov_cloud_li1']        = 'Não requer GPU local';
$string['wizard_prov_cloud_li2']        = 'O custo da chave de API depende do uso e do provedor';
$string['wizard_prov_cloud_li3']        = 'Configure <code>OPENAI_API_KEY</code> no arquivo <code>.env</code> do saipa-engine';
$string['wizard_prov_cloud_title']      = 'API na nuvem';
$string['wizard_prov_custom_desc']      = 'Aponte o EVAL-IA para qualquer URL de motor que exponha uma API REST compatível (por exemplo, seu próprio fork de FastAPI, implantação local ou nuvem privada).';
$string['wizard_prov_custom_li1']       = 'Deve implementar <code>GET /health</code> retornando <code>{"status":"ok"}</code>';
$string['wizard_prov_custom_li2']       = 'Deve implementar <code>POST /eval/rubric/generate</code> e os endpoints relacionados';
$string['wizard_prov_custom_title']     = 'Personalizado / Empresa';
$string['wizard_prov_header']           = '🤖 Provisionamento do serviço de IA — escolha uma opção';
$string['wizard_prov_intro']            = 'O LLM que alimenta o EVAL-IA pode vir de três fontes. Você deve ter pelo menos uma opção pronta antes de continuar.';
$string['wizard_prov_local_desc']       = 'Execute o LLM em seu próprio servidor usando <a href="https://ollama.com" target="_blank">Ollama</a>. Privacidade total — nenhum dado sai da sua infraestrutura.';
$string['wizard_prov_local_li1']        = 'Modelo recomendado: <code>qwen2.5:14b</code> (requer ≥16 GB de RAM)';
$string['wizard_prov_local_li2']        = 'Mínimo: qualquer modelo 7B com ≥8 GB de RAM';
$string['wizard_prov_local_li3']        = 'O saipa-engine deve ser executado no mesmo equipamento ou ter acesso de rede ao Ollama';
$string['wizard_prov_local_title']      = 'Local — Ollama';
$string['wizard_prov_saipa_desc']       = 'Motor totalmente gerenciado por Schaller &amp; Ponce. Sem instalar Ollama nem ChromaDB. Assine e conecte-se com uma única chave de API.';
$string['wizard_prov_saipa_li1']        = 'Zero infraestrutura para gerenciar';
$string['wizard_prov_saipa_li2']        = 'Entre na lista de espera em <code>cloud.saipa.online</code>';
$string['wizard_prov_saipa_title']      = 'SAIPA Cloud';
$string['wizard_prov_warning']          = '<strong>⛔ Sem um serviço de IA ativo, o EVAL-IA não poderá:</strong> indexar material do curso, gerar rubricas, criar questões, corrigir provas nem entregar feedback. Todas essas funções dependem exclusivamente do motor de IA. <strong>Não continue</strong> a menos que tenha uma das opções acima implantada e pronta.';
$string['wizard_req_chroma_desc']       = 'Banco de dados vetorial que armazena o material do curso indexado.';
$string['wizard_req_chroma_label']      = 'ChromaDB (embarcado no saipa-engine)';
$string['wizard_req_chroma_value']      = 'Incluído no motor';
$string['wizard_req_confirm']           = 'Li os requisitos acima. Há um serviço de IA (saipa-engine + LLM) implantado e acessível a partir deste servidor.';
$string['wizard_req_curl_desc']         = 'Necessária para comunicação com o motor de IA.';
$string['wizard_req_curl_enabled']      = 'Habilitada';
$string['wizard_req_curl_label']        = 'Extensão PHP cURL';
$string['wizard_req_curl_missing']      = 'Ausente';
$string['wizard_req_db_desc']           = 'MySQL 8+ / MariaDB 10.6+ / PostgreSQL 13+';
$string['wizard_req_db_label']          = 'Banco de dados';
$string['wizard_req_engine_badge']      = 'Deve ser implantado separadamente';
$string['wizard_req_engine_desc']       = 'Gerencia toda a inferência LLM, busca vetorial (ChromaDB) e recuperação RAG.';
$string['wizard_req_engine_header']     = '⚠️ Motor de IA — <em>Obrigatório. O EVAL-IA não funcionará sem isso.</em>';
$string['wizard_req_engine_intro']      = 'O EVAL-IA utiliza um serviço Python complementar chamado <strong>saipa-engine</strong> para executar todas as operações de IA: geração de rubricas, criação de questões, correção de provas e entrega de feedback. Este serviço deve estar em execução e acessível a partir deste servidor Moodle antes de poder usar qualquer função do EVAL-IA.';
$string['wizard_req_engine_label']      = 'saipa-engine (Python 3.11+ / FastAPI)';
$string['wizard_req_intro']             = 'Por favor, verifique se seu ambiente atende a todos os requisitos antes de continuar. <strong>O EVAL-IA não funcionará sem um serviço de IA ativo.</strong>';
$string['wizard_req_llm_badge']         = 'Serviço de IA obrigatório';
$string['wizard_req_llm_desc']          = 'Gera rubricas, questões, corrige dissertações e redige feedback. Veja as opções de provisionamento abaixo.';
$string['wizard_req_llm_label']         = 'Modelo de linguagem grande (LLM)';
$string['wizard_req_moodle_desc']       = 'Versões anteriores não são suportadas.';
$string['wizard_req_moodle_label']      = 'Moodle 4.4 ou 4.5';
$string['wizard_req_php_desc']          = 'PHP 7.x não é suportado.';
$string['wizard_req_php_label']         = 'PHP 8.1+';
$string['wizard_req_platform_header']   = '🖥️ Plataforma';
$string['wizard_req_title']             = 'Requisitos mínimos';
$string['wizard_welcome_intro']         = 'O EVAL-IA automatiza seu fluxo de avaliação usando IA e Geração Aumentada por Recuperação (RAG) sobre o material do seu próprio curso:';
$string['wizard_welcome_subtitle']      = 'Este assistente configurará a conexão ao motor de IA em poucos passos.';
$string['wizard_welcome_title']         = 'Bem-vindo ao EVAL-IA';

// Assistente de configuração — etapas 4 a 6 e strings JavaScript em tempo de execução.
$string['wizard_btn_retry']             = '↻ Tentar novamente';
$string['wizard_btn_save_finish']       = '✅ Salvar e finalizar';
$string['wizard_btn_test']              = 'Testar conexão →';
$string['wizard_connecting']            = 'Conectando…';
$string['wizard_done_admin_btn']        = '⚙️ Configurações de administração';
$string['wizard_done_body']             = 'O EVAL-IA está conectado ao motor de IA e pronto para uso.<br>Abra qualquer curso e vá para <strong>EVAL-IA → Painel do Professor</strong> para começar.';
$string['wizard_done_courses_btn']      = 'Ir para meus cursos →';
$string['wizard_done_title']            = 'Configuração salva!';
$string['wizard_hint_cloud_body']       = 'Insira a URL onde o saipa-engine está implantado (com Cloud API configurada) e o token <code>ENGINE_SECRET</code>. O motor utilizará sua chave de API na nuvem internamente.';
$string['wizard_hint_cloud_title']      = '☁️ API na nuvem:';
$string['wizard_hint_custom_body']      = 'Insira a URL base do seu motor. O assistente testará <code>{url}/health</code>. A autenticação usa um token Bearer padrão.';
$string['wizard_hint_custom_title']     = '⚙️ Personalizado / Empresa:';
$string['wizard_hint_local_body']       = 'A porta padrão do saipa-engine é <code>8052</code>. Se você está executando com Docker no mesmo equipamento, use <code>http://localhost:8052</code>. O token é opcional, exceto se você configurou <code>ENGINE_SECRET</code> no <code>.env</code>.';
$string['wizard_hint_local_title']      = '🖥️ Local / Ollama:';
$string['wizard_hint_saipa_body']       = 'Quando lançado, a URL do motor será <code>https://engine.saipa.online</code> e o token será sua chave de API de assinatura. Por enquanto, selecione outro modo para continuar.';
$string['wizard_hint_saipa_title']      = '🌐 SAIPA Cloud ainda não está disponível.';
$string['wizard_js_connecting_engine']  = 'Conectando ao motor…';
$string['wizard_js_connection_failed']  = 'Conexão falhou';
$string['wizard_js_engine_reachable']   = 'Motor acessível';
$string['wizard_js_engine_version']     = 'Versão do motor';
$string['wizard_js_error_label']        = 'Erro:';
$string['wizard_js_network_error']      = 'Erro de rede';
$string['wizard_js_success_msg']        = '🎉 <strong>Conexão bem-sucedida!</strong> Clique em <em>Salvar e finalizar</em> para armazenar a configuração.';
$string['wizard_js_troubleshoot_header'] = 'Lista de verificação para resolução de problemas:';
$string['wizard_js_troubleshoot_li1']   = 'O saipa-engine está em execução? Execute: <code>docker compose ps</code>';
$string['wizard_js_troubleshoot_li2']   = 'A URL está correta? (padrão: <code>http://localhost:8052</code>)';
$string['wizard_js_troubleshoot_li3']   = 'Se usa um token, ele coincide com <code>ENGINE_SECRET</code> no <code>.env</code>?';
$string['wizard_js_troubleshoot_li4']   = 'Há um firewall ou proxy reverso bloqueando a porta 8052?';
$string['wizard_js_troubleshoot_li5']   = 'Se o Moodle roda dentro do Docker, use o nome do contêiner, não <code>localhost</code>.';
$string['wizard_js_unknown_error']      = 'Erro desconhecido';
$string['wizard_js_uptime']             = 'Tempo ativo';
$string['wizard_js_url_empty']          = 'A URL do motor está vazia. Volte e insira uma URL.';
$string['wizard_step4_intro']           = 'Insira a URL e o token de autenticação do saipa-engine.';
$string['wizard_step4_title']           = 'Dados de conexão';
$string['wizard_step5_intro']           = 'Verificando a conectividade com o motor SAIPA…';
$string['wizard_step5_title']           = 'Teste de conexão';
$string['wizard_token_help']            = 'Valor de <code>ENGINE_SECRET</code> no arquivo <code>.env</code> do motor. Deixe em branco se não configurou um segredo.';
$string['wizard_token_label']           = 'Token do motor';
$string['wizard_token_placeholder']     = 'Deixe em branco se não estiver configurado';
$string['wizard_url_help']              = 'URL base do saipa-engine, sem barra no final.';
$string['wizard_url_label']             = 'URL do motor';

// Assistente de configuração — cabeçalhos de página, barra de progresso, tela final e mensagens do lado PHP.
$string['wizard_completion_body']       = 'O motor de IA foi configurado. Agora você pode gerar rubricas,<br>criar bancos de questões e atribuir provas aos seus alunos.';
$string['wizard_completion_title']      = 'O EVAL-IA está pronto!';
$string['wizard_err_connection']        = 'Conexão falhou: {$a}';
$string['wizard_err_http_status']       = 'O motor retornou HTTP {$a}. Verifique a URL e o token.';
$string['wizard_err_unexpected']        = 'Resposta inesperada do motor: {$a}';
$string['wizard_err_url_required']      = 'A URL do motor é obrigatória.';
$string['wizard_page_heading']          = 'Assistente de configuração do EVAL-IA';
$string['wizard_page_title']            = 'EVAL-IA — Assistente de configuração';
$string['wizard_save_success']          = 'Configuração salva com sucesso.';
$string['wizard_step_ai_mode']          = 'Modo IA';
$string['wizard_step_connect']          = 'Conectar';
$string['wizard_step_done']             = 'Pronto';
$string['wizard_step_requirements']     = 'Requisitos';
$string['wizard_step_test']             = 'Teste';
$string['wizard_step_welcome']          = 'Boas-vindas';
