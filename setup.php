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

// AJAX: real-time health check.
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
        // Securityhelper: see local_evalia\engine_security_helper docblock —
        // narrows Moodle's default private-IP block to exactly this host
        // instead of widening the site-wide blocklist.
        $client   = new \core\http_client([
            'timeout'        => 10,
            'securityhelper' => new \local_evalia\engine_security_helper($engineurl),
        ]);
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
            ? round($decoded['uptime_seconds'] / 60, 1) . ' ' . get_string('min')
            : '?',
        'message' => $decoded['message'] ?? 'Running',
    ]);
    die();
}

// POST: save configuration.
if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $mode         = required_param('engine_mode', PARAM_ALPHANUMEXT);
    $engineurl   = required_param('engine_url', PARAM_URL);
    $enginetoken = optional_param('engine_token', '', PARAM_RAW);

    $validmodes = ['local_ollama', 'cloud_api', 'custom'];
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

// Strings for amd/src/setup_wizard.js, passed in the wizard's data-config attribute.
$wizardconfig = [
    'url'     => (new moodle_url('/local/evalia/setup.php'))->out(false),
    'strings' => [
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
    ],
];

// Detect server environment for the requirements screen.
$phpverok     = version_compare(PHP_VERSION, '8.1.0', '>=');
$moodleverok  = ($CFG->version >= 2024042200);   // Moodle 4.4.
$curlok        = function_exists('curl_init');
$phpverstr    = PHP_VERSION;
$moodleverstr = $CFG->release ?? 'unknown';

// The page is a Mustache template (templates/setup_wizard.mustache); values are escaped there.
$templatecontext = [
    'done'         => (bool) $done,
    'wizardconfig' => json_encode($wizardconfig, JSON_UNESCAPED_UNICODE),
    'settingsurl'  => $settingsurl,
    'coursesurl'   => (new moodle_url('/course/index.php'))->out(false),
    'saveurl'      => (new moodle_url('/local/evalia/setup.php', ['action' => 'save']))->out(false),
    'sesskey'      => $sesskey,
    'dbtype'       => $CFG->dbtype,
    'moodleverok'  => $moodleverok,
    'phpverok'     => $phpverok,
    'curlok'       => $curlok,
    'moodleverstr' => $moodleverstr,
    'phpverstr'    => $phpverstr,
    'cfgurl'       => $cfgurl,
    'cfgtoken'     => $cfgtoken,
    'modelocal'    => $cfgmode === 'local_ollama',
    'modecloud'    => $cfgmode === 'cloud_api',
    'modecustom'   => $cfgmode === 'custom',
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_evalia/setup_wizard', $templatecontext);
$PAGE->requires->js_call_amd('local_evalia/setup_wizard', 'init');
echo $OUTPUT->footer();
