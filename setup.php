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
 * EVAL-IA Setup Wizard — post-installation configuration guide.
 *
 * Steps:
 *   1 — Welcome & feature overview
 *   2 — Minimum requirements & AI provisioning (CRITICAL)
 *   3 — AI service mode selection
 *   4 — Connection details
 *   5 — Real-time engine health check
 *   6 — Done
 *
 * @package    local_evalia
 * @copyright  2026 Schaller & Ponce <dev@schaller-ponce.com.ar>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/evalia/lib.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/evalia/setup.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title('EVAL-IA — Setup Wizard');
$PAGE->set_heading('EVAL-IA Setup Wizard');

$action = optional_param('action', '', PARAM_ALPHA);

// ── AJAX: real-time health check ─────────────────────────────────────────────
if ($action === 'health') {
    require_sesskey();
    header('Content-Type: application/json');
    header('X-Content-Type-Options: nosniff');

    $engineurl   = optional_param('engine_url', '', PARAM_URL);
    $enginetoken = optional_param('engine_token', '', PARAM_RAW);

    if (empty($engineurl)) {
        echo json_encode(['error' => 'Engine URL is required.']);
        die();
    }

    $url     = rtrim($engineurl, '/') . '/health';
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . $enginetoken,
    ];
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => $headers,
    ]);
    $resp  = curl_exec($ch);
    $errno = curl_errno($ch);
    $err   = curl_error($ch);
    $http  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno) {
        echo json_encode(['error' => 'Connection failed: ' . $err]);
        die();
    }
    if ($http !== 200) {
        echo json_encode(['error' => "Engine returned HTTP $http. Check the URL && token."]);
        die();
    }
    $decoded = json_decode($resp, true);
    if (!$decoded || ($decoded['status'] ?? '') !== 'ok') {
        echo json_encode(['error' => 'Unexpected engine response: ' . substr($resp, 0, 200)]);
        die();
    }
    echo json_encode([
        'ok'      => true,
        'version' => $decoded['version'] ?? '?',
        'uptime'  => isset($decoded['uptime_seconds'])
            ? round($decoded['uptime_seconds'] / 60, 1) . ' min'
            : '?',
        'message' => $decoded['message'] ?? 'Running',
    ]);
    die();
}

// ── POST: save configuration ──────────────────────────────────────────────────
if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $mode         = required_param('engine_mode', PARAM_ALPHA);
    $engineurl   = required_param('engine_url', PARAM_URL);
    $enginetoken = optional_param('engine_token', '', PARAM_RAW);

    $validmodes = ['local_ollama', 'cloud_api', 'saipa_cloud', 'custom'];
    if (!in_array($mode, $validmodes)) {
        $mode = 'custom';
    }

    set_config('engine_mode', $mode, 'local_evalia');
    set_config('engine_url', $engineurl, 'local_evalia');
    set_config('engine_token', $enginetoken, 'local_evalia');
    set_config('setup_complete', 1, 'local_evalia');

    redirect(
        new moodle_url('/local/evalia/setup.php', ['done' => 1]),
        'Configuration saved successfully.',
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$done        = optional_param('done', 0, PARAM_INT);
$sesskey     = sesskey();
$settingsurl = (new moodle_url('/admin/settings.php', ['section' => 'local_evalia']))->out(false);

// Pre-fill from existing config (wizard re-run).
$cfgmode    = get_config('local_evalia', 'engine_mode') ?: 'local_ollama';
$cfgurl     = get_config('local_evalia', 'engine_url') ?: '';
$cfgtoken   = get_config('local_evalia', 'engine_token') ?: '';

// Fallback: inherit from local_saipa when co-installed.
if (empty($cfgurl)) {
    $cfgurl   = get_config('local_saipa', 'engine_url') ?: '';
    $cfgtoken = get_config('local_saipa', 'engine_token') ?: '';
}

// Detect server environment for the requirements screen.
$phpverok     = version_compare(PHP_VERSION, '8.1.0', '>=');
$moodleverok  = ($CFG->version >= 2024042200);   // Moodle 4.4
$curlok        = function_exists('curl_init');
$phpverstr    = PHP_VERSION;
$moodleverstr = $CFG->release ?? 'unknown';

echo $OUTPUT->header();
?>
<style>
/* ════════════════════════════════════════════════════════
   EVAL-IA Setup Wizard — styles
   ════════════════════════════════════════════════════════ */

.evwiz { max-width: 800px; margin: 0 auto; padding: 0 0 80px; }

/* ── Progress bar ── */
.evwiz-progress { display: flex; align-items: flex-start; margin-bottom: 32px; }
.evwiz-progress .step {
    display: flex; flex-direction: column; align-items: center; gap: 5px;
    flex: 1; position: relative;
}
.evwiz-progress .step::after {
    content: ''; position: absolute; top: 15px; left: 50%; width: 100%;
    height: 2px; background: #dee2e6; z-index: 0;
}
.evwiz-progress .step:last-child::after { display: none; }
.evwiz-progress .step-circle {
    width: 30px; height: 30px; border-radius: 50%;
    background: #dee2e6; color: #6c757d;
    display: flex; align-items: center; justify-content: center;
    font-size: .78rem; font-weight: 700; position: relative; z-index: 1;
    transition: background .25s, color .25s;
}
.evwiz-progress .step.active .step-circle { background: #0d6efd; color: #fff; }
.evwiz-progress .step.done   .step-circle { background: #198754; color: #fff; }
.evwiz-progress .step.done::after         { background: #198754; }
.evwiz-progress .step-label { font-size: .67rem; color: #6c757d; text-align: center; line-height: 1.2; }
.evwiz-progress .step.active .step-label  { color: #0d6efd; font-weight: 600; }
.evwiz-progress .step.done  .step-label   { color: #198754; }

/* ── Steps ── */
.evwiz-step { display: none; }
.evwiz-step.active { display: block; animation: fadein .2s ease; }
@keyframes fadein { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }

/* ── Feature grid (step 1) ── */
.feature-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.feature-item { display: flex; gap: 10px; }
.fi-icon { font-size: 1.4rem; flex-shrink: 0; line-height: 1.3; }
.feature-item h6 { font-size: .86rem; font-weight: 600; margin-bottom: 2px; }
.feature-item p  { font-size: .78rem; color: #6c757d; margin: 0; }

/* ── Requirements (step 2) ── */
.req-section { border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden; margin-bottom: 18px; }
.req-section-header {
    padding: 10px 16px; font-weight: 700; font-size: .88rem;
    display: flex; align-items: center; gap: 8px;
}
.req-section-body { padding: 14px 16px; }

.req-row { display: flex; align-items: flex-start; gap: 10px; padding: 6px 0;
           border-bottom: 1px solid #f5f5f5; }
.req-row:last-child { border-bottom: none; }
.req-status { font-size: 1rem; flex-shrink: 0; width: 20px; text-align: center; margin-top: 1px; }
.req-label  { flex: 1; font-size: .85rem; }
.req-label strong { display: block; }
.req-label span   { color: #6c757d; font-size: .78rem; }
.req-value  { font-size: .82rem; color: #6c757d; text-align: right; white-space: nowrap; }

.ai-provision-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 6px; }
.ai-pcard {
    border: 1px solid #dee2e6; border-radius: 8px; padding: 14px 14px 12px;
    font-size: .82rem;
}
.ai-pcard .pc-head { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
.ai-pcard .pc-icon { font-size: 1.4rem; }
.ai-pcard h6 { font-size: .86rem; font-weight: 700; margin: 0; }
.ai-pcard p  { color: #6c757d; margin: 0; line-height: 1.4; }
.ai-pcard ul { margin: 6px 0 0 0; padding-left: 16px; color: #6c757d; }
.ai-pcard ul li { margin-bottom: 2px; }

.pc-local  { border-top: 3px solid #198754; }
.pc-cloud  { border-top: 3px solid #0d6efd; }
.pc-saipa  { border-top: 3px solid #fd7e14; }
.pc-custom { border-top: 3px solid #6c757d; }

/* ── Mode cards (step 3) ── */
.mode-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.mode-card {
    border: 2px solid #dee2e6; border-radius: 10px; padding: 18px 16px 14px;
    cursor: pointer; transition: border-color .2s, box-shadow .2s; position: relative;
}
.mode-card:hover   { border-color: #86b7fe; box-shadow: 0 0 0 3px rgba(13,110,253,.08); }
.mode-card.selected { border-color: #0d6efd; box-shadow: 0 0 0 3px rgba(13,110,253,.12); }
.mode-card input[type=radio] { position: absolute; opacity: 0; pointer-events: none; }
.mode-card .mc-icon { font-size: 1.8rem; margin-bottom: 8px; }
.mode-card h5  { font-size: .92rem; font-weight: 700; margin-bottom: 5px; }
.mode-card p   { font-size: .78rem; color: #6c757d; margin: 0; }
.mode-badge {
    display: inline-block; font-size: .63rem; font-weight: 700;
    padding: 2px 7px; border-radius: 20px; margin-left: 5px; vertical-align: middle;
}
.badge-local  { background: #d1e7dd; color: #0a3622; }
.badge-cloud  { background: #cfe2ff; color: #084298; }
.badge-soon   { background: #fff3cd; color: #664d03; }
.badge-custom { background: #e2e3e5; color: #41464b; }

/* ── Health check (step 5) ── */
.health-row { display: flex; align-items: flex-start; gap: 12px;
              padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
.health-row:last-child { border-bottom: none; }
.health-icon  { font-size: 1.2rem; width: 24px; text-align: center; flex-shrink: 0; }
.health-label { flex: 1; font-size: .88rem; }
.health-value { font-size: .82rem; color: #6c757d; text-align: right; }

/* ── Done screen ── */
.done-card { text-align: center; padding: 44px 20px 36px; }
.done-card .done-icon { font-size: 3.5rem; margin-bottom: 14px; }
.done-card h3 { font-weight: 700; margin-bottom: 8px; }
.done-card p  { color: #6c757d; margin-bottom: 28px; }
</style>

<div class="evwiz mt-4">

<?php if ($done) : /* ─── Completion screen (after save + redirect) ─── */ ?>
  <div class="card shadow-sm">
    <div class="card-body done-card">
      <div class="done-icon">🎉</div>
      <h3>EVAL-IA is ready!</h3>
      <p>The AI engine has been configured. You can now generate rubrics,<br>
         create question banks, && assign exams to your students.</p>
      <div class="d-flex gap-3 justify-content-center flex-wrap">
        <a href="<?= s($settingsurl) ?>" class="btn btn-outline-secondary">
          ⚙️ Admin Settings
        </a>
        <a href="<?= (new moodle_url('/course/index.php'))->out() ?>" class="btn btn-primary btn-lg px-5">
          Go to My Courses →
        </a>
      </div>
    </div>
  </div>

<?php else : /* ─── Wizard ─── */ ?>
  <!-- Progress bar -->
  <div class="evwiz-progress" id="evwiz-progress">
    <div class="step active" data-step="1"><div class="step-circle">1</div><div class="step-label">Welcome</div></div>
    <div class="step"        data-step="2"><div class="step-circle">2</div><div class="step-label">Requirements</div></div>
    <div class="step"        data-step="3"><div class="step-circle">3</div><div class="step-label">AI Mode</div></div>
    <div class="step"        data-step="4"><div class="step-circle">4</div><div class="step-label">Connect</div></div>
    <div class="step"        data-step="5"><div class="step-circle">5</div><div class="step-label">Test</div></div>
    <div class="step"        data-step="6"><div class="step-circle">6</div><div class="step-label">Done</div></div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body p-4">

    <!-- ══════════════════════════════════════════
         STEP 1 — Welcome
         ══════════════════════════════════════════ -->
    <div class="evwiz-step active" id="evwiz-step-1">

      <div class="d-flex align-items-center gap-3 mb-3">
        <div style="font-size:2.8rem;line-height:1">🧠</div>
        <div>
          <h3 class="mb-1">Welcome to EVAL-IA</h3>
          <p class="text-muted mb-0">This wizard will configure the AI engine connection in a few steps.</p>
        </div>
      </div>
      <hr class="my-3">

      <p class="mb-3" style="font-size:.9rem;">
        EVAL-IA automates your evaluation workflow using AI && Retrieval-Augmented Generation (RAG)
        over your own course materials:
      </p>

      <div class="feature-grid mb-4">
        <div class="feature-item">
          <div class="fi-icon">📋</div>
          <div><h6>AI Rubric Generation</h6>
            <p>Generates structured evaluation rubrics from indexed course materials in seconds.</p></div>
        </div>
        <div class="feature-item">
          <div class="fi-icon">❓</div>
          <div><h6>Question Bank</h6>
            <p>Creates multiple-choice, true/false, numerical, short-answer && essay questions per topic.</p></div>
        </div>
        <div class="feature-item">
          <div class="fi-icon">📝</div>
          <div><h6>Unique Per-Student Exams</h6>
            <p>Each student receives a different question set, reducing collusion risk.</p></div>
        </div>
        <div class="feature-item">
          <div class="fi-icon">🤖</div>
          <div><h6>AI Grading</h6>
            <p>Objective questions graded instantly. Essays evaluated by the LLM with RAG context.</p></div>
        </div>
        <div class="feature-item">
          <div class="fi-icon">📊</div>
          <div><h6>Gradebook Integration</h6>
            <p>Results published directly to Moodle's native gradebook.</p></div>
        </div>
        <div class="feature-item">
          <div class="fi-icon">💬</div>
          <div><h6>Telegram Feedback</h6>
            <p>Students receive AI-generated pedagogical feedback via Telegram after grading.</p></div>
        </div>
      </div>

      <div class="d-flex justify-content-end">
        <button class="btn btn-primary px-5" onclick="evwizGoto(2)">Next →</button>
      </div>
    </div><!-- /step 1 -->


    <!-- ══════════════════════════════════════════
         STEP 2 — Requirements (CRITICAL)
         ══════════════════════════════════════════ -->
    <div class="evwiz-step" id="evwiz-step-2">

      <h4 class="mb-1">Minimum requirements</h4>
      <p class="text-muted mb-4" style="font-size:.88rem;">
        Please verify that your environment meets all requirements before continuing.
        <strong>EVAL-IA will not work without an active AI service.</strong>
      </p>

      <!-- ─── Platform requirements ─── -->
      <div class="req-section">
        <div class="req-section-header" style="background:#f8f9fa;">
          🖥️ Platform
        </div>
        <div class="req-section-body">

          <div class="req-row">
            <div class="req-status"><?= $moodleverok ? '✅' : '❌' ?></div>
            <div class="req-label">
              <strong>Moodle 4.4 || 4.5</strong>
              <span>Older versions are not supported.</span>
            </div>
            <div class="req-value"><?= s($moodleverstr) ?></div>
          </div>

          <div class="req-row">
            <div class="req-status"><?= $phpverok ? '✅' : '❌' ?></div>
            <div class="req-label">
              <strong>PHP 8.1+</strong>
              <span>PHP 7.x is not supported.</span>
            </div>
            <div class="req-value"><?= s($phpverstr) ?></div>
          </div>

          <div class="req-row">
            <div class="req-status"><?= $curlok ? '✅' : '❌' ?></div>
            <div class="req-label">
              <strong>PHP cURL extension</strong>
              <span>Required to communicate with the AI engine.</span>
            </div>
            <div class="req-value"><?= $curlok ? 'Enabled' : '<span class="text-danger">Missing</span>' ?></div>
          </div>

          <div class="req-row">
            <div class="req-status">ℹ️</div>
            <div class="req-label">
              <strong>Database</strong>
              <span>MySQL 8+ / MariaDB 10.6+ / PostgreSQL 13+</span>
            </div>
            <div class="req-value"><?= s($CFG->dbtype) ?></div>
          </div>

        </div>
      </div><!-- /platform -->

      <!-- ─── AI Engine requirement ─── -->
      <div class="req-section" style="border-color:#f0ad4e;">
        <div class="req-section-header" style="background:#fff8e1; color:#856404; border-bottom:1px solid #f0e0a0;">
          ⚠️ AI Engine — <em>Required. EVAL-IA will not function without this.</em>
        </div>
        <div class="req-section-body">

          <p style="font-size:.87rem; margin-bottom:14px;">
            EVAL-IA uses a companion Python service called <strong>saipa-engine</strong> to run all
            AI operations: rubric generation, question creation, exam grading, && feedback delivery.
            This service must be running && reachable from this Moodle server before you can use
            any EVAL-IA feature.
          </p>

          <div class="req-row">
            <div class="req-status">🐍</div>
            <div class="req-label">
              <strong>saipa-engine (Python 3.11+ / FastAPI)</strong>
              <span>Handles all LLM inference, vector search (ChromaDB), && RAG retrieval.</span>
            </div>
            <div class="req-value" style="white-space:normal;max-width:200px;text-align:right;">
              <span class="badge bg-warning text-dark" style="font-size:.72rem;">Must be deployed separately</span>
            </div>
          </div>

          <div class="req-row">
            <div class="req-status">🗄️</div>
            <div class="req-label">
              <strong>ChromaDB (embedded in saipa-engine)</strong>
              <span>Vector database that stores indexed course materials.</span>
            </div>
            <div class="req-value">Included in engine</div>
          </div>

          <div class="req-row">
            <div class="req-status">🔤</div>
            <div class="req-label">
              <strong>Large Language Model (LLM)</strong>
              <span>Generates rubrics, questions, grades essays, && writes feedback.
                See provisioning options below.</span>
            </div>
            <div class="req-value" style="white-space:normal;max-width:200px;text-align:right;">
              <span class="badge bg-danger" style="font-size:.72rem;">AI service required</span>
            </div>
          </div>

        </div>
      </div><!-- /engine -->

      <!-- ─── AI Provisioning options ─── -->
      <div class="req-section" style="border-color:#0d6efd;">
        <div class="req-section-header" style="background:#e7f1ff; color:#084298; border-bottom:1px solid #b6d4fe;">
          🤖 AI service provisioning — choose one option
        </div>
        <div class="req-section-body">

          <p style="font-size:.85rem;margin-bottom:14px;color:#495057;">
            The LLM that powers EVAL-IA can come from three sources.
            You must have at least one option ready before proceeding.
          </p>

          <div class="ai-provision-cards">

            <div class="ai-pcard pc-local">
              <div class="pc-head">
                <div class="pc-icon">🖥️</div>
                <h6>Local — Ollama <span class="mode-badge badge-local">SELF-HOSTED</span></h6>
              </div>
              <p>Run the LLM on your own server using <a href="https://ollama.com" target="_blank">Ollama</a>.
                 Full privacy — no data leaves your infrastructure.</p>
              <ul>
                <li>Recommended model: <code>qwen2.5:14b</code> (requires ≥16 GB RAM)</li>
                <li>Minimum: any 7B model with ≥8 GB RAM</li>
                <li>saipa-engine must run on the same host || have network access to Ollama</li>
              </ul>
            </div>

            <div class="ai-pcard pc-cloud">
              <div class="pc-head">
                <div class="pc-icon">☁️</div>
                <h6>Cloud API <span class="mode-badge badge-cloud">OPENAI-COMPATIBLE</span></h6>
              </div>
              <p>Use any OpenAI-compatible API provider (OpenAI, Azure OpenAI, Groq, Mistral, etc.)
                 with your own API key.</p>
              <ul>
                <li>No local GPU required</li>
                <li>API key cost depends on usage && provider</li>
                <li>Configure <code>OPENAI_API_KEY</code> in saipa-engine's <code>.env</code></li>
              </ul>
            </div>

            <div class="ai-pcard pc-saipa">
              <div class="pc-head">
                <div class="pc-icon">🌐</div>
                <h6>SAIPA Cloud <span class="mode-badge badge-soon">COMING SOON</span></h6>
              </div>
              <p>Fully managed engine hosted by Schaller &amp; Ponce. No Ollama, no ChromaDB installation.
                 Subscribe && connect with a single API key.</p>
              <ul>
                <li>Zero infrastructure to manage</li>
                <li>Join the waitlist at <code>cloud.saipa.online</code></li>
              </ul>
            </div>

            <div class="ai-pcard pc-custom">
              <div class="pc-head">
                <div class="pc-icon">⚙️</div>
                <h6>Custom / Enterprise <span class="mode-badge badge-custom">ADVANCED</span></h6>
              </div>
              <p>Point EVAL-IA at any engine URL that exposes a compatible REST API
                 (e.g. your own FastAPI fork, on-premise deployment, || private cloud).</p>
              <ul>
                <li>Must implement <code>GET /health</code> returning <code>{"status":"ok"}</code></li>
                <li>Must implement <code>POST /eval/rubric/generate</code> && related endpoints</li>
              </ul>
            </div>

          </div><!-- /ai-provision-cards -->

          <div class="alert alert-danger mt-3 mb-0 py-2 px-3" style="font-size:.84rem;">
            <strong>⛔ Without an active AI service, EVAL-IA will not be able to:</strong>
            index course materials, generate rubrics, create questions, grade exams, || deliver feedback.
            All these functions depend exclusively on the AI engine. <strong>Do not continue</strong> unless
            you have one of the options above deployed && ready.
          </div>

        </div>
      </div><!-- /ai provisioning -->

      <!-- Confirmation checkbox -->
      <div class="form-check mt-3 mb-1">
        <input class="form-check-input" type="checkbox" id="req-confirm">
        <label class="form-check-label" for="req-confirm" style="font-size:.88rem;">
          I have read the requirements above. An AI service (saipa-engine + LLM) is deployed && reachable from this server.
        </label>
      </div>

      <div class="d-flex justify-content-between mt-3">
        <button class="btn btn-outline-secondary" onclick="evwizGoto(1)">← Back</button>
        <button class="btn btn-primary px-5" id="req-next-btn" disabled
                onclick="evwizGoto(3)">Next →</button>
      </div>
    </div><!-- /step 2 -->


    <!-- ══════════════════════════════════════════
         STEP 3 — AI Mode selection
         ══════════════════════════════════════════ -->
    <div class="evwiz-step" id="evwiz-step-3">

      <h4 class="mb-1">Choose your AI provisioning mode</h4>
      <p class="text-muted mb-4" style="font-size:.88rem;">
        Select the option that matches your deployed AI infrastructure.
      </p>

      <div class="mode-cards">

        <label class="mode-card <?= ($cfgmode === 'local_ollama') ? 'selected' : '' ?>" for="mode-local">
          <input type="radio" name="engine_mode" id="mode-local" value="local_ollama"
                 <?= ($cfgmode === 'local_ollama') ? 'checked' : '' ?>>
          <div class="mc-icon">🖥️</div>
          <h5>Local — Ollama <span class="mode-badge badge-local">SELF-HOSTED</span></h5>
          <p>saipa-engine running on your server with Ollama as the LLM backend. Full data privacy.</p>
        </label>

        <label class="mode-card <?= ($cfgmode === 'cloud_api') ? 'selected' : '' ?>" for="mode-cloud">
          <input type="radio" name="engine_mode" id="mode-cloud" value="cloud_api"
                 <?= ($cfgmode === 'cloud_api') ? 'checked' : '' ?>>
          <div class="mc-icon">☁️</div>
          <h5>Cloud API <span class="mode-badge badge-cloud">OPENAI-COMPATIBLE</span></h5>
          <p>saipa-engine configured with an OpenAI-compatible API key. No local GPU required.</p>
        </label>

        <label class="mode-card <?= ($cfgmode === 'saipa_cloud') ? 'selected' : '' ?>" for="mode-saipa">
          <input type="radio" name="engine_mode" id="mode-saipa" value="saipa_cloud"
                 <?= ($cfgmode === 'saipa_cloud') ? 'checked' : '' ?>>
          <div class="mc-icon">🌐</div>
          <h5>SAIPA Cloud <span class="mode-badge badge-soon">COMING SOON</span></h5>
          <p>Fully managed engine by Schaller &amp; Ponce. Subscribe && connect with a single API key.</p>
        </label>

        <label class="mode-card <?= ($cfgmode === 'custom') ? 'selected' : '' ?>" for="mode-custom">
          <input type="radio" name="engine_mode" id="mode-custom" value="custom"
                 <?= ($cfgmode === 'custom') ? 'checked' : '' ?>>
          <div class="mc-icon">⚙️</div>
          <h5>Custom / Enterprise <span class="mode-badge badge-custom">ADVANCED</span></h5>
          <p>Any compatible engine at a custom URL. Full control for advanced deployments.</p>
        </label>

      </div>

      <div class="d-flex justify-content-between mt-4">
        <button class="btn btn-outline-secondary" onclick="evwizGoto(2)">← Back</button>
        <button class="btn btn-primary px-5" onclick="evwizGoto(4)">Next →</button>
      </div>
    </div><!-- /step 3 -->


    <!-- ══════════════════════════════════════════
         STEP 4 — Connection details
         ══════════════════════════════════════════ -->
    <div class="evwiz-step" id="evwiz-step-4">

      <h4 class="mb-1">Connection details</h4>
      <p class="text-muted mb-4" style="font-size:.88rem;">
        Enter the URL && authentication token for the saipa-engine.
      </p>

      <!-- Mode-specific hints (shown/hidden by JS) -->
      <div id="hint-local_ollama" class="alert alert-light border mb-3 py-2 px-3" style="font-size:.82rem;">
        <strong>🖥️ Local / Ollama:</strong>
        The default port for saipa-engine is <code>8052</code>.
        If you are running it via Docker on the same host, use <code>http://localhost:8052</code>.
        Token is optional unless you configured <code>ENGINE_SECRET</code> in <code>.env</code>.
      </div>
      <div id="hint-cloud_api" class="alert alert-light border mb-3 py-2 px-3" style="font-size:.82rem;" style="display:none">
        <strong>☁️ Cloud API:</strong>
        Enter the URL where saipa-engine is deployed (with Cloud API configured), && the
        <code>ENGINE_SECRET</code> token. The engine will use your cloud API key internally.
      </div>
      <div id="hint-saipa_cloud" class="alert alert-warning mb-3 py-2 px-3" style="font-size:.82rem;">
        <strong>🌐 SAIPA Cloud is not yet available.</strong>
        When launched, the engine URL will be <code>https://engine.saipa.online</code> && the token will be your subscription API key. For now, select another mode to continue.
      </div>
      <div id="hint-custom" class="alert alert-light border mb-3 py-2 px-3" style="font-size:.82rem;">
        <strong>⚙️ Custom / Enterprise:</strong>
        Enter the base URL of your engine. The wizard will test <code>{url}/health</code>.
        Authentication uses a standard Bearer token.
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold" for="evwiz-url">
          Engine URL <span class="text-danger">*</span>
        </label>
        <input type="url" class="form-control" id="evwiz-url"
               placeholder="http://localhost:8052"
               value="<?= s($cfgurl) ?>">
        <div class="form-text">Base URL of the saipa-engine — without trailing slash.</div>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold" for="evwiz-token">Engine Token</label>
        <input type="password" class="form-control" id="evwiz-token"
               placeholder="Leave blank if not configured"
               value="<?= s($cfgtoken) ?>">
        <div class="form-text">
          Value of <code>ENGINE_SECRET</code> in the engine's <code>.env</code> file.
          Leave blank if you did not configure a secret.
        </div>
      </div>

      <div class="d-flex justify-content-between mt-4">
        <button class="btn btn-outline-secondary" onclick="evwizGoto(3)">← Back</button>
        <button class="btn btn-primary px-5" onclick="evwizRunTest()">Test Connection →</button>
      </div>
    </div><!-- /step 4 -->


    <!-- ══════════════════════════════════════════
         STEP 5 — Health check
         ══════════════════════════════════════════ -->
    <div class="evwiz-step" id="evwiz-step-5">

      <h4 class="mb-1">Connection test</h4>
      <p class="text-muted mb-4" style="font-size:.88rem;">
        Verifying connectivity with the SAIPA Engine…
      </p>

      <div id="evwiz-health-result" class="mb-3">
        <div class="d-flex align-items-center gap-2 text-muted py-2">
          <div class="spinner-border spinner-border-sm" role="status"></div>
          <span>Connecting…</span>
        </div>
      </div>

      <div class="d-flex justify-content-between mt-4">
        <button class="btn btn-outline-secondary" onclick="evwizGoto(4)">← Back</button>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-secondary" id="evwiz-retry-btn" style="display:none"
                  onclick="evwizRunTest()">↻ Retry</button>
          <button class="btn btn-success px-5" id="evwiz-save-btn" style="display:none"
                  onclick="evwizSave()">✅ Save & Finish</button>
        </div>
      </div>
    </div><!-- /step 5 -->


    <!-- ══════════════════════════════════════════
         STEP 6 — Done (inline, before POST redirect)
         ══════════════════════════════════════════ -->
    <div class="evwiz-step" id="evwiz-step-6">
      <div class="done-card">
        <div class="done-icon">✅</div>
        <h3>Configuration saved!</h3>
        <p>EVAL-IA is connected to the AI engine && ready to use.<br>
           Open any course && navigate to <strong>EVAL-IA → Teacher Panel</strong> to start.</p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
          <a href="<?= s($settingsurl) ?>" class="btn btn-outline-secondary">
            ⚙️ Admin Settings
          </a>
          <a href="<?= (new moodle_url('/course/index.php'))->out() ?>" class="btn btn-primary btn-lg px-5">
            Go to My Courses →
          </a>
        </div>
      </div>
    </div><!-- /step 6 -->

    </div><!-- /card-body -->
  </div><!-- /card -->

  <!-- Hidden save form -->
  <form id="evwiz-save-form" method="post"
        action="<?= (new moodle_url('/local/evalia/setup.php', ['action' => 'save']))->out(false) ?>"
        style="display:none">
    <input type="hidden" name="sesskey"      value="<?= s($sesskey) ?>">
    <input type="hidden" name="engine_mode"  id="sf-mode"  value="">
    <input type="hidden" name="engine_url"   id="sf-url"   value="">
    <input type="hidden" name="engine_token" id="sf-token" value="">
  </form>

<?php endif; /* /wizard */ ?>
</div><!-- /evwiz -->

<script>
(function () {
    'use strict';

    var currentStep = 1;
    var healthOk    = false;

    // ── Step navigation ───────────────────────────────────────────────────────
    /**
     * EvwizGoto.
     */
    function evwizGoto(step) {
        var prev = document.getElementById('evwiz-step-' + currentStep);
        if (prev) prev.classList.remove('active');
        currentStep = step;
        var next = document.getElementById('evwiz-step-' + step);
        if (next) next.classList.add('active');

        var steps = document.querySelectorAll('#evwiz-progress .step');
        steps.forEach(function (el) {
            var n = parseInt(el.dataset.step, 10);
            el.classList.remove('active', 'done');
            if (n === step) el.classList.add('active');
            if (n < step)   el.classList.add('done');
        });

        if (step === 4) updateHints();
        if (step === 5) evwizRunTest();

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    window.evwizGoto = evwizGoto;

    // ── Requirements confirmation checkbox ───────────────────────────────────
    var reqCheck   = document.getElementById('req-confirm');
    var reqNextBtn = document.getElementById('req-next-btn');
    if (reqCheck) {
        reqCheck.addEventListener('change', function () {
            reqNextBtn.disabled = !reqCheck.checked;
        });
    }

    // ── Mode card selection ───────────────────────────────────────────────────
    document.querySelectorAll('.mode-card').forEach(function (card) {
        card.addEventListener('click', function () {
            document.querySelectorAll('.mode-card').forEach(function (c) {
                c.classList.remove('selected');
            });
            card.classList.add('selected');
            card.querySelector('input[type=radio]').checked = true;
        });
    });

    /**
     * GetSelectedMode.
     */
    function getSelectedMode() {
        var checked = document.querySelector('input[name="engine_mode"]:checked');
        return checked ? checked.value : 'local_ollama';
    }

    /**
     * UpdateHints.
     */
    function updateHints() {
        var mode  = getSelectedMode();
        var modes = ['local_ollama', 'cloud_api', 'saipa_cloud', 'custom'];
        modes.forEach(function (m) {
            var el = document.getElementById('hint-' + m);
            if (el) el.style.display = (m === mode) ? '' : 'none';
        });
    }

    // ── Real-time health check ────────────────────────────────────────────────
    /**
     * EvwizRunTest.
     */
    function evwizRunTest() {
        healthOk = false;
        var resultEl  = document.getElementById('evwiz-health-result');
        var saveBtn   = document.getElementById('evwiz-save-btn');
        var retryBtn  = document.getElementById('evwiz-retry-btn');

        saveBtn.style.display  = 'none';
        retryBtn.style.display = 'none';
        resultEl.innerHTML =
            '<div class="d-flex align-items-center gap-2 text-muted py-2">' +
            '<div class="spinner-border spinner-border-sm" role="status"></div>' +
            '<span>Connecting to engine…</span></div>';

        var url   = (document.getElementById('evwiz-url')   || {}).value || '';
        var token = (document.getElementById('evwiz-token') || {}).value || '';
        url = url.trim();

        if (!url) {
            renderHealthError('Engine URL is empty. Go back && enter a URL.', url);
            return;
        }

        var fd = new FormData();
        fd.append('action',       'health');
        fd.append('sesskey',      '<?= $sesskey ?>');
        fd.append('engine_url',   url);
        fd.append('engine_token', token);

        fetch(
            '<?= (new moodle_url('/local/evalia/setup.php'))->out(false) ?>',
            { method: 'POST', credentials: 'same-origin', body: fd }
        )
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.ok) {
                healthOk = true;
                resultEl.innerHTML =
                    '<div class="health-row">' +
                    '<div class="health-icon">✅</div>' +
                    '<div class="health-label"><strong>Engine reachable</strong></div>' +
                    '<div class="health-value">' + he(url) + '</div></div>' +
                    '<div class="health-row">' +
                    '<div class="health-icon">🔢</div>' +
                    '<div class="health-label">Engine version</div>' +
                    '<div class="health-value">' + he(data.version) + '</div></div>' +
                    '<div class="health-row">' +
                    '<div class="health-icon">⏱️</div>' +
                    '<div class="health-label">Uptime</div>' +
                    '<div class="health-value">' + he(data.uptime) + '</div></div>' +
                    '<div class="alert alert-success py-2 px-3 mt-3 mb-0" style="font-size:.85rem;">' +
                    '🎉 <strong>Connection successful!</strong> ' +
                    'Click <em>Save &amp; Finish</em> to store the configuration.</div>';
                saveBtn.style.display = '';
            } else {
                renderHealthError(data.error || 'Unknown error', url);
            }
        })
        .catch(function (e) { renderHealthError(e.message || 'Network error', url); });
    }
    window.evwizRunTest = evwizRunTest;

    /**
     * RenderHealthError.
     */
    function renderHealthError(msg, url) {
        document.getElementById('evwiz-health-result').innerHTML =
            '<div class="health-row">' +
            '<div class="health-icon">❌</div>' +
            '<div class="health-label"><strong>Connection failed</strong></div>' +
            '<div class="health-value">' + he(url || '—') + '</div></div>' +
            '<div class="alert alert-danger py-2 px-3 mt-3 mb-0" style="font-size:.84rem;">' +
            '<strong>Error:</strong> ' + he(msg) + '<br><br>' +
            '<strong>Troubleshooting checklist:</strong>' +
            '<ul class="mb-0 mt-1">' +
            '<li>Is saipa-engine running? Run: <code>docker compose ps</code></li>' +
            '<li>Is the URL correct? (default: <code>http://localhost:8052</code>)</li>' +
            '<li>If using a token, does it match <code>ENGINE_SECRET</code> in <code>.env</code>?</li>' +
            '<li>Is there a firewall || reverse proxy blocking port 8052?</li>' +
            '<li>If Moodle runs inside Docker, use the container hostname, not <code>localhost</code>.</li>' +
            '</ul></div>';
        document.getElementById('evwiz-retry-btn').style.display = '';
    }

    // ── Save ──────────────────────────────────────────────────────────────────
    /**
     * EvwizSave.
     */
    function evwizSave() {
        document.getElementById('sf-mode').value  = getSelectedMode();
        document.getElementById('sf-url').value   = (document.getElementById('evwiz-url')   || {}).value || '';
        document.getElementById('sf-token').value = (document.getElementById('evwiz-token') || {}).value || '';
        document.getElementById('evwiz-save-form').submit();
    }
    window.evwizSave = evwizSave;

    // ── HTML escape ───────────────────────────────────────────────────────────
    /**
     * He.
     */
    function he(s) {
        return String(s)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
}());
</script>

<?php echo $OUTPUT->footer(); ?>
