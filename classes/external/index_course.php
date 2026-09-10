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
 * WS: index_course — crawl a course's content && index it in ChromaDB.
 *
 * Handles:
 *   mod_page     → extracts plain text from page intro + content HTML.
 *   mod_resource → PDF: sends bytes as base64 to /index-pdf-bytes.
 *                  PPTX: sends bytes to /index-pptx-bytes (engine converts via LibreOffice).
 *
 * @package    local_evalia
 * @copyright  2026 Schaller & Ponce <dev@schaller-ponce.com.ar>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evalia\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;


/**
 * Index_course.
 */
class index_course extends external_api {
    /**
     * Define the parameters for this web service.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID to index'),
            'token'    => new external_value(
                PARAM_ALPHANUMEXT,
                'Moodle webservice token used by saipa-engine to download files',
                VALUE_DEFAULT,
                ''
            ),
        ]);
    }

    /**
     * Execute the web service.
     */
    public static function execute(int $courseid, string $token): array {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/local/evalia/lib.php');
        require_once($CFG->libdir  . '/filelib.php');

        // Indexing PDFs && PPTX files via LibreOffice can take several minutes.
        \core_php_time_limit::raise(600);

        $params  = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'token'    => $token,
        ]);
        $courseid = (int) $params['courseid'];
        $token    = $params['token'];

        $course  = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $context = \context_course::instance($courseid);
        self::validate_context($context);
        require_capability('local/evalia:manage', $context);

        $indexed  = [];
        $errors   = [];

        // ── Retrieve the course's webservice download token ────────────────────
        // If the caller didn't pass a token, try to find a valid WS token for
        // the local_evalia or saipa service so the engine can download files.
        if (empty($token)) {
            $token = self::find_ws_token();
        }

        // ── mod_page ──────────────────────────────────────────────────────────
        $pages = $DB->get_records_sql(
            'SELECT p.id, p.name, p.intro, p.content
               FROM {page} p
               JOIN {course_modules} cm ON cm.instance = p.id
               JOIN {modules} m ON m.id = cm.module AND m.name = :modname
              WHERE cm.course = :courseid AND cm.deletioninprogress = 0 AND cm.visible = 1',
            ['modname' => 'page', 'courseid' => $courseid]
        );

        foreach ($pages as $page) {
            $text  = strip_tags($page->intro ?? '') . "\n\n" . strip_tags($page->content ?? '');
            $text  = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text  = preg_replace('/\s+/', ' ', $text);
            $text  = trim($text);
            if (strlen($text) < 20) {
                continue;   // skip near-empty pages
            }
            $source = 'page:' . $page->id;
            $result = local_evalia_raw_engine_request('/index', [
                'course_id' => $courseid,
                'content'   => $text,
                'source'    => $source,
            ], 120);

            if (isset($result['error'])) {
                $errors[] = ['source' => $source, 'error' => $result['error']];
            } else {
                $indexed[] = [
                    'source'      => $source,
                    'chunk_count' => (int) ($result['chunk_count'] ?? 0),
                    'status'      => $result['status'] ?? 'ok',
                ];
            }
        }

        // ── mod_resource (PDF files) ──────────────────────────────────────────
        if (!empty($token)) {
            $resources = $DB->get_records_sql(
                'SELECT r.id, r.name, cm.id AS cmid
                   FROM {resource} r
                   JOIN {course_modules} cm ON cm.instance = r.id
                   JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  WHERE cm.course = :courseid AND cm.deletioninprogress = 0 AND cm.visible = 1',
                ['modname' => 'resource', 'courseid' => $courseid]
            );

            foreach ($resources as $res) {
                $cmcontext = \context_module::instance((int) $res->cmid);
                $fs    = get_file_storage();
                $files = $fs->get_area_files(
                    $cmcontext->id,
                    'mod_resource',
                    'content',
                    0,
                    'sortorder DESC, id ASC',
                    false
                );

                foreach ($files as $file) {
                    $ext    = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));
                    $source = 'resource:' . $res->id . ':' . $file->get_filename();

                    if ($ext === 'pdf') {
                        // Read PDF bytes directly from Moodle's filestore && send as base64.
                        // The engine cannot reach Moodle via URL (NAT hairpinning fails && // the internal hostname triggers a Moodle redirect error).
                        $pdfbytes = $file->get_content();
                        if (empty($pdfbytes)) {
                            continue;
                        }
                        $b64 = base64_encode($pdfbytes);

                        // Check if page ranges are configured for this filename.
                        $rangesjson = get_config('local_evalia', 'pdf_page_ranges');
                        $rangesmap  = $rangesjson ? @json_decode($rangesjson, true) : [];
                        $filename    = $file->get_filename();

                        if (!empty($rangesmap[$filename])) {
                            // Index each configured chapter range as a separate source.
                            foreach ($rangesmap[$filename] as $range) {
                                $rangesource = $source . ':' . ($range['label'] ?? 'p' . $range['from'] . '-' . $range['to']);
                                $result = local_evalia_raw_engine_request('/index-pdf-bytes', [
                                    'course_id'   => $courseid,
                                    'content_b64' => $b64,
                                    'source'      => $rangesource,
                                    'page_from'   => (int) $range['from'],
                                    'page_to'     => (int) $range['to'],
                                ], 300);
                                if (isset($result['error'])) {
                                    $errors[] = ['source' => $rangesource, 'error' => $result['error']];
                                } else {
                                    $indexed[] = [
                                        'source'      => $rangesource,
                                        'chunk_count' => (int) ($result['chunk_count'] ?? 0),
                                        'status'      => $result['status'] ?? 'ok',
                                    ];
                                }
                            }
                            continue;   // skip the generic full-PDF index below
                        }

                        // No ranges configured — index the full PDF as one source.
                        $result = local_evalia_raw_engine_request('/index-pdf-bytes', [
                            'course_id'   => $courseid,
                            'content_b64' => $b64,
                            'source'      => $source,
                        ], 300);
                    } else if ($ext === 'pptx') {
                        // Convert PPTX → PDF in the engine (LibreOffice headless).
                        // Sending raw bytes avoids NAT hairpinning issues && captures
                        // SmartArt, tables && grouped shapes that XML extraction misses.
                        $pptxbytes = $file->get_content();
                        if (empty($pptxbytes)) {
                            continue;
                        }
                        $result = local_evalia_raw_engine_request('/index-pptx-bytes', [
                            'course_id'   => $courseid,
                            'content_b64' => base64_encode($pptxbytes),
                            'source'      => $source,
                        ], 300);
                    } else {
                        continue;   // unsupported format
                    }

                    if (isset($result['error'])) {
                        $errors[] = ['source' => $source, 'error' => $result['error']];
                    } else {
                        $indexed[] = [
                            'source'      => $source,
                            'chunk_count' => (int) ($result['chunk_count'] ?? 0),
                            'status'      => $result['status'] ?? 'ok',
                        ];
                    }
                }
            }
        }

        $totalchunks = array_sum(array_column($indexed, 'chunk_count'));

        return [
            'success'      => empty($errors),
            'total_chunks' => $totalchunks,
            'indexed'      => $indexed,
            'errors'       => $errors,
        ];
    }

    /**
     * Try to locate a valid webservice token that the engine can use to
     * download files via webservice/pluginfile.php.
     * Prefers tokens for the 'evalia_service' or 'saipa_service' services.
     */
    private static function find_ws_token(): string {
        global $DB;
        $row = $DB->get_record_sql(
            "SELECT t.token
               FROM {external_tokens} t
               JOIN {external_services} s ON s.id = t.externalserviceid
              WHERE s.shortname IN ('evalia_service', 'saipa_service')
                AND t.validuntil = 0
              ORDER BY t.id ASC
              LIMIT 1"
        );
        return $row ? $row->token : '';
    }

    /**
     * Define the return structure for this web service.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success'      => new external_value(PARAM_BOOL, 'True if no errors'),
            'total_chunks' => new external_value(PARAM_INT, 'Total chunks indexed'),
            'indexed'      => new external_multiple_structure(
                new external_single_structure([
                    'source'      => new external_value(PARAM_TEXT, 'Source identifier'),
                    'chunk_count' => new external_value(PARAM_INT, 'Chunks stored'),
                    'status'      => new external_value(PARAM_ALPHANUMEXT, 'ok / empty / ...'),
                ])
            ),
            'errors'       => new external_multiple_structure(
                new external_single_structure([
                    'source' => new external_value(PARAM_TEXT, 'Source that failed'),
                    'error'  => new external_value(PARAM_TEXT, 'Error message'),
                ])
            ),
        ]);
    }
}
