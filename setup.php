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
$PAGE->set_title(get_string('wizard_page_title', 'local_evalia'));
$PAGE->set_heading(get_string('wizard_page_heading', 'local_evalia'));

$action = optional_param('action', '', PARAM_ALPHA);

// ── AJAX: real-time health check ─────────────────────────────────────────────
if ($action === 'health') {
    require_sesskey();
    header('Content-Type: application/json');
    header('X-Content-Type-Options: nosniff');

    $engineurl   = optional_param('engine_url', '', PARAM_URL);
    $enginetoken = optional_param('engine_token', '', PARAM_RAW);

    if (empty($engineurl)) {
        echo json_encode(['error' => get_string('wizard_err_url_required', 'local_evalia')]);
        die();
    }

    $url = rtrim($engineurl, '/') . '/health';
    try {
        $client   = new \core\http_client(['timeout' => 10]);
        $response = $client->get($url, [
            'headers' => [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $enginetoken,
            ],
            'http_errors' => false,
        ]);
    } catch (\GuzzleHttp\Exception\GuzzleException $e) {
        echo json_encode(['error' => get_string('wizard_err_connection', 'local_evalia', $e->getMessage())]);
        die();
    }
    $http = $response->getStatusCode();
    $resp = (string) $response->getBody();

    if ($http !== 200) {
        echo json_encode(['error' => get_string('wizard_err_http_status', 'local_evalia', $http)]);
        die();
    }
    $decoded = json_decode($resp, true);
    if (!$decoded || ($decoded['status'] ?? '') !== 'ok') {
        echo json_encode(['error' => get_string('wizard_err_unexpected', 'local_evalia', substr($resp, 0, 200))]);
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
        get_string('wizard_save_success', 'local_evalia'),
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

// phpcs:disable moodle.Commenting.MissingDocblock.File -- False positive: this sniff
// re-fires on every reopened PHP tag in the HTML template below, although the
// file docblock is present at the top of the file. Re-enabled at end of file.
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
      <h3><?= get_string('wizard_completion_title', 'local_evalia') ?></h3>
      <p><?= get_string('wizard_completion_body', 'local_evalia') ?></p>
      <div class="d-flex gap-3 justify-content-center flex-wrap">
        <a href="<?= s($settingsurl) ?>" class="btn btn-outline-secondary">
          <?= get_string('wizard_done_admin_btn', 'local_evalia') ?>
        </a>
        <a href="<?= (new moodle_url('/course/index.php'))->out() ?>" class="btn btn-primary btn-lg px-5">
          <?= get_string('wizard_done_courses_btn', 'local_evalia') ?>
        </a>
      </div>
    </div>
  </div>

<?php else : /* ─── Wizard ─── */ ?>
  <!-- Progress bar -->
  <div class="evwiz-progress" id="evwiz-progress">
    <div class="step active" data-step="1"><div class="step-circle">1</div><div class="step-label"><?= get_string('wizard_step_welcome', 'local_evalia') ?></div></div>
    <div class="step"        data-step="2"><div class="step-circle">2</div><div class="step-label"><?= get_string('wizard_step_requirements', 'local_evalia') ?></div></div>
    <div class="step"        data-step="3"><div class="step-circle">3</div><div class="step-label"><?= get_string('wizard_step_ai_mode', 'local_evalia') ?></div></div>
    <div class="step"        data-step="4"><div class="step-circle">4</div><div class="step-label"><?= get_string('wizard_step_connect', 'local_evalia') ?></div></div>
    <div class="step"        data-step="5"><div class="step-circle">5</div><div class="step-label"><?= get_string('wizard_step_test', 'local_evalia') ?></div></div>
    <div class="step"        data-step="6"><div class="step-circle">6</div><div class="step-label"><?= get_string('wizard_step_done', 'local_evalia') ?></div></div>
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
          <h3 class="mb-1"><?= get_string('wizard_welcome_title', 'local_evalia') ?></h3>
          <p class="text-muted mb-0"><?= get_string('wizard_welcome_subtitle', 'local_evalia') ?></p>
        </div>
      </div>
      <hr class="my-3">

      <p class="mb-3" style="font-size:.9rem;">
        <?= get_string('wizard_welcome_intro', 'local_evalia') ?>
      </p>

      <div class="feature-grid mb-4">
        <div class="feature-item">
          <div class="fi-icon">📋</div>
          <div><h6><?= get_string('wizard_feat_rubric_title', 'local_evalia') ?></h6>
            <p><?= get_string('wizard_feat_rubric_desc', 'local_evalia') ?></p></div>
        </div>
        <div class="feature-item">
          <div class="fi-icon">❓</div>
          <div><h6><?= get_string('wizard_feat_qbank_title', 'local_evalia') ?></h6>
            <p><?= get_string('wizard_feat_qbank_desc', 'local_evalia') ?></p></div>
        </div>
        <div class="feature-item">
          <div class="fi-icon">📝</div>
          <div><h6><?= get_string('wizard_feat_unique_title', 'local_evalia') ?></h6>
            <p><?= get_string('wizard_feat_unique_desc', 'local_evalia') ?></p></div>
        </div>
        <div class="feature-item">
          <div class="fi-icon">🤖</div>
          <div><h6><?= get_string('wizard_feat_grading_title', 'local_evalia') ?></h6>
            <p><?= get_string('wizard_feat_grading_desc', 'local_evalia') ?></p></div>
        </div>
        <div class="feature-item">
          <div class="fi-icon">📊</div>
          <div><h6><?= get_string('wizard_feat_gradebook_title', 'local_evalia') ?></h6>
            <p><?= get_string('wizard_feat_gradebook_desc', 'local_evalia') ?></p></div>
        </div>
        <div class="feature-item">
          <div class="fi-icon">💬</div>
          <div><h6><?= get_string('wizard_feat_telegram_title', 'local_evalia') ?></h6>
            <p><?= get_string('wizard_feat_telegram_desc', 'local_evalia') ?></p></div>
        </div>
      </div>

      <div class="d-flex justify-content-end">
        <button class="btn btn-primary px-5" onclick="evwizGoto(2)"><?= get_string('wizard_btn_next', 'local_evalia') ?></button>
      </div>
    </div><!-- /step 1 -->


    <!-- ══════════════════════════════════════════
         STEP 2 — Requirements (CRITICAL)
         ══════════════════════════════════════════ -->
    <div class="evwiz-step" id="evwiz-step-2">

      <h4 class="mb-1"><?= get_string('wizard_req_title', 'local_evalia') ?></h4>
      <p class="text-muted mb-4" style="font-size:.88rem;">
        <?= get_string('wizard_req_intro', 'local_evalia') ?>
      </p>

      <!-- ─── Platform requirements ─── -->
      <div class="req-section">
        <div class="req-section-header" style="background:#f8f9fa;">
          <?= get_string('wizard_req_platform_header', 'local_evalia') ?>
        </div>
        <div class="req-section-body">

          <div class="req-row">
            <div class="req-status"><?= $moodleverok ? '✅' : '❌' ?></div>
            <div class="req-label">
              <strong><?= get_string('wizard_req_moodle_label', 'local_evalia') ?></strong>
              <span><?= get_string('wizard_req_moodle_desc', 'local_evalia') ?></span>
            </div>
            <div class="req-value"><?= s($moodleverstr) ?></div>
          </div>

          <div class="req-row">
            <div class="req-status"><?= $phpverok ? '✅' : '❌' ?></div>
            <div class="req-label">
              <strong><?= get_string('wizard_req_php_label', 'local_evalia') ?></strong>
              <span><?= get_string('wizard_req_php_desc', 'local_evalia') ?></span>
            </div>
            <div class="req-value"><?= s($phpverstr) ?></div>
          </div>

          <div class="req-row">
            <div class="req-status"><?= $curlok ? '✅' : '❌' ?></div>
            <div class="req-label">
              <strong><?= get_string('wizard_req_curl_label', 'local_evalia') ?></strong>
              <span><?= get_string('wizard_req_curl_desc', 'local_evalia') ?></span>
            </div>
            <div class="req-value"><?= $curlok
                ? get_string('wizard_req_curl_enabled', 'local_evalia')
                : '<span class="text-danger">' . get_string('wizard_req_curl_missing', 'local_evalia') . '</span>' ?></div>
          </div>

          <div class="req-row">
            <div class="req-status">ℹ️</div>
            <div class="req-label">
              <strong><?= get_string('wizard_req_db_label', 'local_evalia') ?></strong>
              <span><?= get_string('wizard_req_db_desc', 'local_evalia') ?></span>
            </div>
            <div class="req-value"><?= s($CFG->dbtype) ?></div>
          </div>

        </div>
      </div><!-- /platform -->

      <!-- ─── AI Engine requirement ─── -->
      <div class="req-section" style="border-color:#f0ad4e;">
        <div class="req-section-header" style="background:#fff8e1; color:#856404; border-bottom:1px solid #f0e0a0;">
          <?= get_string('wizard_req_engine_header', 'local_evalia') ?>
        </div>
        <div class="req-section-body">

          <p style="font-size:.87rem; margin-bottom:14px;">
            <?= get_string('wizard_req_engine_intro', 'local_evalia') ?>
          </p>

          <div class="req-row">
            <div class="req-status">🐍</div>
            <div class="req-label">
              <strong><?= get_string('wizard_req_engine_label', 'local_evalia') ?></strong>
              <span><?= get_string('wizard_req_engine_desc', 'local_evalia') ?></span>
            </div>
            <div class="req-value" style="white-space:normal;max-width:200px;text-align:right;">
              <span class="badge bg-warning text-dark" style="font-size:.72rem;"><?= get_string('wizard_req_engine_badge', 'local_evalia') ?></span>
            </div>
          </div>

          <div class="req-row">
            <div class="req-status">🗄️</div>
            <div class="req-label">
              <strong><?= get_string('wizard_req_chroma_label', 'local_evalia') ?></strong>
              <span><?= get_string('wizard_req_chroma_desc', 'local_evalia') ?></span>
            </div>
            <div class="req-value"><?= get_string('wizard_req_chroma_value', 'local_evalia') ?></div>
          </div>

          <div class="req-row">
            <div class="req-status">🔤</div>
            <div class="req-label">
              <strong><?= get_string('wizard_req_llm_label', 'local_evalia') ?></strong>
              <span><?= get_string('wizard_req_llm_desc', 'local_evalia') ?></span>
            </div>
            <div class="req-value" style="white-space:normal;max-width:200px;text-align:right;">
              <span class="badge bg-danger" style="font-size:.72rem;"><?= get_string('wizard_req_llm_badge', 'local_evalia') ?></span>
            </div>
          </div>

        </div>
      </div><!-- /engine -->

      <!-- ─── AI Provisioning options ─── -->
      <div class="req-section" style="border-color:#0d6efd;">
        <div class="req-section-header" style="background:#e7f1ff; color:#084298; border-bottom:1px solid #b6d4fe;">
          <?= get_string('wizard_prov_header', 'local_evalia') ?>
        </div>
        <div class="req-section-body">

          <p style="font-size:.85rem;margin-bottom:14px;color:#495057;">
            <?= get_string('wizard_prov_intro', 'local_evalia') ?>
          </p>

          <div class="ai-provision-cards">

            <div class="ai-pcard pc-local">
              <div class="pc-head">
                <div class="pc-icon">🖥️</div>
                <h6><?= get_string('wizard_prov_local_title', 'local_evalia') ?> <span class="mode-badge badge-local">SELF-HOSTED</span></h6>
              </div>
              <p><?= get_string('wizard_prov_local_desc', 'local_evalia') ?></p>
              <ul>
                <li><?= get_string('wizard_prov_local_li1', 'local_evalia') ?></li>
                <li><?= get_string('wizard_prov_local_li2', 'local_evalia') ?></li>
                <li><?= get_string('wizard_prov_local_li3', 'local_evalia') ?></li>
              </ul>
            </div>

            <div class="ai-pcard pc-cloud">
              <div class="pc-head">
                <div class="pc-icon">☁️</div>
                <h6><?= get_string('wizard_prov_cloud_title', 'local_evalia') ?> <span class="mode-badge badge-cloud">OPENAI-COMPATIBLE</span></h6>
              </div>
              <p><?= get_string('wizard_prov_cloud_desc', 'local_evalia') ?></p>
              <ul>
                <li><?= get_string('wizard_prov_cloud_li1', 'local_evalia') ?></li>
                <li><?= get_string('wizard_prov_cloud_li2', 'local_evalia') ?></li>
                <li><?= get_string('wizard_prov_cloud_li3', 'local_evalia') ?></li>
              </ul>
            </div>

            <div class="ai-pcard pc-saipa">
              <div class="pc-head">
                <div class="pc-icon">🌐</div>
                <h6><?= get_string('wizard_prov_saipa_title', 'local_evalia') ?> <span class="mode-badge badge-soon">COMING SOON</span></h6>
              </div>
              <p><?= get_string('wizard_prov_saipa_desc', 'local_evalia') ?></p>
              <ul>
                <li><?= get_string('wizard_prov_saipa_li1', 'local_evalia') ?></li>
                <li><?= get_string('wizard_prov_saipa_li2', 'local_evalia') ?></li>
              </ul>
            </div>

            <div class="ai-pcard pc-custom">
              <div class="pc-head">
                <div class="pc-icon">⚙️</div>
                <h6><?= get_string('wizard_prov_custom_title', 'local_evalia') ?> <span class="mode-badge badge-custom">ADVANCED</span></h6>
              </div>
              <p><?= get_string('wizard_prov_custom_desc', 'local_evalia') ?></p>
              <ul>
                <li><?= get_string('wizard_prov_custom_li1', 'local_evalia') ?></li>
                <li><?= get_string('wizard_prov_custom_li2', 'local_evalia') ?></li>
              </ul>
            </div>

          </div><!-- /ai-provision-cards -->

          <div class="alert alert-danger mt-3 mb-0 py-2 px-3" style="font-size:.84rem;">
            <?= get_string('wizard_prov_warning', 'local_evalia') ?>
          </div>

        </div>
      </div><!-- /ai provisioning -->

      <!-- Confirmation checkbox -->
      <div class="form-check mt-3 mb-1">
        <input class="form-check-input" type="checkbox" id="req-confirm">
        <label class="form-check-label" for="req-confirm" style="font-size:.88rem;">
          <?= get_string('wizard_req_confirm', 'local_evalia') ?>
        </label>
      </div>

      <div class="d-flex justify-content-between mt-3">
        <button class="btn btn-outline-secondary" onclick="evwizGoto(1)"><?= get_string('wizard_btn_back', 'local_evalia') ?></button>
        <button class="btn btn-primary px-5" id="req-next-btn" disabled
                onclick="evwizGoto(3)"><?= get_string('wizard_btn_next', 'local_evalia') ?></button>
      </div>
    </div><!-- /step 2 -->


    <!-- ══════════════════════════════════════════
         STEP 3 — AI Mode selection
         ══════════════════════════════════════════ -->
    <div class="evwiz-step" id="evwiz-step-3">

      <h4 class="mb-1"><?= get_string('wizard_mode_title', 'local_evalia') ?></h4>
      <p class="text-muted mb-4" style="font-size:.88rem;">
        <?= get_string('wizard_mode_intro', 'local_evalia') ?>
      </p>

      <div class="mode-cards">

        <label class="mode-card <?= ($cfgmode === 'local_ollama') ? 'selected' : '' ?>" for="mode-local">
          <input type="radio" name="engine_mode" id="mode-local" value="local_ollama"
                 <?= ($cfgmode === 'local_ollama') ? 'checked' : '' ?>>
          <div class="mc-icon">🖥️</div>
          <h5><?= get_string('wizard_mode_local_title', 'local_evalia') ?> <span class="mode-badge badge-local">SELF-HOSTED</span></h5>
          <p><?= get_string('wizard_mode_local_desc', 'local_evalia') ?></p>
        </label>

        <label class="mode-card <?= ($cfgmode === 'cloud_api') ? 'selected' : '' ?>" for="mode-cloud">
          <input type="radio" name="engine_mode" id="mode-cloud" value="cloud_api"
                 <?= ($cfgmode === 'cloud_api') ? 'checked' : '' ?>>
          <div class="mc-icon">☁️</div>
          <h5><?= get_string('wizard_mode_cloud_title', 'local_evalia') ?> <span class="mode-badge badge-cloud">OPENAI-COMPATIBLE</span></h5>
          <p><?= get_string('wizard_mode_cloud_desc', 'local_evalia') ?></p>
        </label>

        <label class="mode-card <?= ($cfgmode === 'saipa_cloud') ? 'selected' : '' ?>" for="mode-saipa">
          <input type="radio" name="engine_mode" id="mode-saipa" value="saipa_cloud"
                 <?= ($cfgmode === 'saipa_cloud') ? 'checked' : '' ?>>
          <div class="mc-icon">🌐</div>
          <h5><?= get_string('wizard_mode_saipa_title', 'local_evalia') ?> <span class="mode-badge badge-soon">COMING SOON</span></h5>
          <p><?= get_string('wizard_mode_saipa_desc', 'local_evalia') ?></p>
        </label>

        <label class="mode-card <?= ($cfgmode === 'custom') ? 'selected' : '' ?>" for="mode-custom">
          <input type="radio" name="engine_mode" id="mode-custom" value="custom"
                 <?= ($cfgmode === 'custom') ? 'checked' : '' ?>>
          <div class="mc-icon">⚙️</div>
          <h5><?= get_string('wizard_mode_custom_title', 'local_evalia') ?> <span class="mode-badge badge-custom">ADVANCED</span></h5>
          <p><?= get_string('wizard_mode_custom_desc', 'local_evalia') ?></p>
        </label>

      </div>

      <div class="d-flex justify-content-between mt-4">
        <button class="btn btn-outline-secondary" onclick="evwizGoto(2)"><?= get_string('wizard_btn_back', 'local_evalia') ?></button>
        <button class="btn btn-primary px-5" onclick="evwizGoto(4)"><?= get_string('wizard_btn_next', 'local_evalia') ?></button>
      </div>
    </div><!-- /step 3 -->


    <!-- ══════════════════════════════════════════
         STEP 4 — Connection details
         ══════════════════════════════════════════ -->
    <div class="evwiz-step" id="evwiz-step-4">

      <h4 class="mb-1"><?= get_string('wizard_step4_title', 'local_evalia') ?></h4>
      <p class="text-muted mb-4" style="font-size:.88rem;">
        <?= get_string('wizard_step4_intro', 'local_evalia') ?>
      </p>

      <!-- Mode-specific hints (shown/hidden by JS) -->
      <div id="hint-local_ollama" class="alert alert-light border mb-3 py-2 px-3" style="font-size:.82rem;">
        <strong><?= get_string('wizard_hint_local_title', 'local_evalia') ?></strong>
        <?= get_string('wizard_hint_local_body', 'local_evalia') ?>
      </div>
      <div id="hint-cloud_api" class="alert alert-light border mb-3 py-2 px-3" style="font-size:.82rem;display:none;">
        <strong><?= get_string('wizard_hint_cloud_title', 'local_evalia') ?></strong>
        <?= get_string('wizard_hint_cloud_body', 'local_evalia') ?>
      </div>
      <div id="hint-saipa_cloud" class="alert alert-warning mb-3 py-2 px-3" style="font-size:.82rem;display:none;">
        <strong><?= get_string('wizard_hint_saipa_title', 'local_evalia') ?></strong>
        <?= get_string('wizard_hint_saipa_body', 'local_evalia') ?>
      </div>
      <div id="hint-custom" class="alert alert-light border mb-3 py-2 px-3" style="font-size:.82rem;display:none;">
        <strong><?= get_string('wizard_hint_custom_title', 'local_evalia') ?></strong>
        <?= get_string('wizard_hint_custom_body', 'local_evalia') ?>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold" for="evwiz-url">
          <?= get_string('wizard_url_label', 'local_evalia') ?> <span class="text-danger">*</span>
        </label>
        <input type="url" class="form-control" id="evwiz-url"
               placeholder="http://localhost:8052"
               value="<?= s($cfgurl) ?>">
        <div class="form-text"><?= get_string('wizard_url_help', 'local_evalia') ?></div>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold" for="evwiz-token"><?= get_string('wizard_token_label', 'local_evalia') ?></label>
        <input type="password" class="form-control" id="evwiz-token"
               placeholder="<?= s(get_string('wizard_token_placeholder', 'local_evalia')) ?>"
               value="<?= s($cfgtoken) ?>">
        <div class="form-text">
          <?= get_string('wizard_token_help', 'local_evalia') ?>
        </div>
      </div>

      <div class="d-flex justify-content-between mt-4">
        <button class="btn btn-outline-secondary" onclick="evwizGoto(3)"><?= get_string('wizard_btn_back', 'local_evalia') ?></button>
        <button class="btn btn-primary px-5" onclick="evwizGoto(5)"><?= get_string('wizard_btn_test', 'local_evalia') ?></button>
      </div>
    </div><!-- /step 4 -->


    <!-- ══════════════════════════════════════════
         STEP 5 — Health check
         ══════════════════════════════════════════ -->
    <div class="evwiz-step" id="evwiz-step-5">

      <h4 class="mb-1"><?= get_string('wizard_step5_title', 'local_evalia') ?></h4>
      <p class="text-muted mb-4" style="font-size:.88rem;">
        <?= get_string('wizard_step5_intro', 'local_evalia') ?>
      </p>

      <div id="evwiz-health-result" class="mb-3">
        <div class="d-flex align-items-center gap-2 text-muted py-2">
          <div class="spinner-border spinner-border-sm" role="status"></div>
          <span><?= get_string('wizard_connecting', 'local_evalia') ?></span>
        </div>
      </div>

      <div class="d-flex justify-content-between mt-4">
        <button class="btn btn-outline-secondary" onclick="evwizGoto(4)"><?= get_string('wizard_btn_back', 'local_evalia') ?></button>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-secondary" id="evwiz-retry-btn" style="display:none"
                  onclick="evwizRunTest()"><?= get_string('wizard_btn_retry', 'local_evalia') ?></button>
          <button class="btn btn-success px-5" id="evwiz-save-btn" style="display:none"
                  onclick="evwizSave()"><?= get_string('wizard_btn_save_finish', 'local_evalia') ?></button>
        </div>
      </div>
    </div><!-- /step 5 -->


    <!-- ══════════════════════════════════════════
         STEP 6 — Done (inline, before POST redirect)
         ══════════════════════════════════════════ -->
    <div class="evwiz-step" id="evwiz-step-6">
      <div class="done-card">
        <div class="done-icon">✅</div>
        <h3><?= get_string('wizard_done_title', 'local_evalia') ?></h3>
        <p><?= get_string('wizard_done_body', 'local_evalia') ?></p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
          <a href="<?= s($settingsurl) ?>" class="btn btn-outline-secondary">
            <?= get_string('wizard_done_admin_btn', 'local_evalia') ?>
          </a>
          <a href="<?= (new moodle_url('/course/index.php'))->out() ?>" class="btn btn-primary btn-lg px-5">
            <?= get_string('wizard_done_courses_btn', 'local_evalia') ?>
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

    // Localized runtime strings injected from Moodle lang files.
    var L = <?= json_encode([
        'connecting_engine'   => get_string('wizard_js_connecting_engine', 'local_evalia'),
        'url_empty'           => get_string('wizard_js_url_empty', 'local_evalia'),
        'engine_reachable'    => get_string('wizard_js_engine_reachable', 'local_evalia'),
        'engine_version'      => get_string('wizard_js_engine_version', 'local_evalia'),
        'uptime'              => get_string('wizard_js_uptime', 'local_evalia'),
        'success_msg'         => get_string('wizard_js_success_msg', 'local_evalia'),
        'connection_failed'   => get_string('wizard_js_connection_failed', 'local_evalia'),
        'error_label'         => get_string('wizard_js_error_label', 'local_evalia'),
        'unknown_error'       => get_string('wizard_js_unknown_error', 'local_evalia'),
        'network_error'       => get_string('wizard_js_network_error', 'local_evalia'),
        'troubleshoot_header' => get_string('wizard_js_troubleshoot_header', 'local_evalia'),
        'troubleshoot_li1'    => get_string('wizard_js_troubleshoot_li1', 'local_evalia'),
        'troubleshoot_li2'    => get_string('wizard_js_troubleshoot_li2', 'local_evalia'),
        'troubleshoot_li3'    => get_string('wizard_js_troubleshoot_li3', 'local_evalia'),
        'troubleshoot_li4'    => get_string('wizard_js_troubleshoot_li4', 'local_evalia'),
        'troubleshoot_li5'    => get_string('wizard_js_troubleshoot_li5', 'local_evalia'),
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

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
            '<span>' + he(L.connecting_engine) + '</span></div>';

        var url   = (document.getElementById('evwiz-url')   || {}).value || '';
        var token = (document.getElementById('evwiz-token') || {}).value || '';
        url = url.trim();

        if (!url) {
            renderHealthError(L.url_empty, url);
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
                    '<div class="health-label"><strong>' + he(L.engine_reachable) + '</strong></div>' +
                    '<div class="health-value">' + he(url) + '</div></div>' +
                    '<div class="health-row">' +
                    '<div class="health-icon">🔢</div>' +
                    '<div class="health-label">' + he(L.engine_version) + '</div>' +
                    '<div class="health-value">' + he(data.version) + '</div></div>' +
                    '<div class="health-row">' +
                    '<div class="health-icon">⏱️</div>' +
                    '<div class="health-label">' + he(L.uptime) + '</div>' +
                    '<div class="health-value">' + he(data.uptime) + '</div></div>' +
                    '<div class="alert alert-success py-2 px-3 mt-3 mb-0" style="font-size:.85rem;">' +
                    L.success_msg + '</div>';
                saveBtn.style.display = '';
            } else {
                renderHealthError(data.error || L.unknown_error, url);
            }
        })
        .catch(function (e) { renderHealthError(e.message || L.network_error, url); });
    }
    window.evwizRunTest = evwizRunTest;

    /**
     * RenderHealthError.
     */
    function renderHealthError(msg, url) {
        document.getElementById('evwiz-health-result').innerHTML =
            '<div class="health-row">' +
            '<div class="health-icon">❌</div>' +
            '<div class="health-label"><strong>' + he(L.connection_failed) + '</strong></div>' +
            '<div class="health-value">' + he(url || '—') + '</div></div>' +
            '<div class="alert alert-danger py-2 px-3 mt-3 mb-0" style="font-size:.84rem;">' +
            '<strong>' + he(L.error_label) + '</strong> ' + he(msg) + '<br><br>' +
            '<strong>' + he(L.troubleshoot_header) + '</strong>' +
            '<ul class="mb-0 mt-1">' +
            '<li>' + L.troubleshoot_li1 + '</li>' +
            '<li>' + L.troubleshoot_li2 + '</li>' +
            '<li>' + L.troubleshoot_li3 + '</li>' +
            '<li>' + L.troubleshoot_li4 + '</li>' +
            '<li>' + L.troubleshoot_li5 + '</li>' +
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

<?php
// phpcs:enable moodle.Commenting.MissingDocblock.File
echo $OUTPUT->footer();

