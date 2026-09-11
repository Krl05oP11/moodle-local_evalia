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
        get_string('settings:heading_wizard', 'local_evalia'),
        html_writer::tag(
            'p',
            get_string('settings:heading_wizard_desc', 'local_evalia') . ' '
            . html_writer::link($setupurl, get_string('settings:launch_wizard', 'local_evalia'), ['class' => 'btn btn-sm btn-primary ms-2']),
            ['style' => 'margin-top:6px;']
        )
    ));

    // ── AI Engine ────────────────────────────────────────────────────────────
    $settings->add(new admin_setting_heading(
        'local_evalia_engine_heading',
        get_string('settings:heading_engine', 'local_evalia'),
        get_string('settings:heading_engine_desc', 'local_evalia')
    ));

    $settings->add(new admin_setting_configtext(
        'local_evalia/engine_url',
        get_string('settings:engine_url', 'local_evalia'),
        get_string('settings:engine_url_desc', 'local_evalia'),
        '',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'local_evalia/engine_token',
        get_string('settings:engine_token', 'local_evalia'),
        get_string('settings:engine_token_desc', 'local_evalia'),
        ''
    ));

    // ── Rubrics ──────────────────────────────────────────────────────────────
    $settings->add(new admin_setting_heading(
        'local_evalia_rubric_heading',
        get_string('settings:heading_rubric', 'local_evalia'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'local_evalia/rubric_default_items',
        get_string('settings:rubric_default_items', 'local_evalia'),
        get_string('settings:rubric_default_items_desc', 'local_evalia'),
        18,
        PARAM_INT
    ));

    // ── Exams ────────────────────────────────────────────────────────────────
    $settings->add(new admin_setting_heading(
        'local_evalia_exam_heading',
        get_string('settings:heading_exam', 'local_evalia'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'local_evalia/exam_default_basic',
        get_string('settings:exam_default_basic', 'local_evalia'),
        get_string('settings:exam_default_basic_desc', 'local_evalia'),
        3,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_evalia/exam_default_medium',
        get_string('settings:exam_default_medium', 'local_evalia'),
        get_string('settings:exam_default_medium_desc', 'local_evalia'),
        4,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_evalia/exam_default_advanced',
        get_string('settings:exam_default_advanced', 'local_evalia'),
        get_string('settings:exam_default_advanced_desc', 'local_evalia'),
        2,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_evalia/exam_default_time_limit',
        get_string('settings:exam_default_time_limit', 'local_evalia'),
        get_string('settings:exam_default_time_limit_desc', 'local_evalia'),
        60,
        PARAM_INT
    ));

    // ── Difficulty weighting ─────────────────────────────────────────────────
    $settings->add(new admin_setting_heading(
        'local_evalia_weights_heading',
        get_string('settings:heading_weights', 'local_evalia'),
        get_string('settings:heading_weights_desc', 'local_evalia')
    ));

    $settings->add(new admin_setting_configtext(
        'local_evalia/weight_basic',
        get_string('settings:weight_basic', 'local_evalia'),
        '',
        '1',
        PARAM_FLOAT
    ));

    $settings->add(new admin_setting_configtext(
        'local_evalia/weight_medium',
        get_string('settings:weight_medium', 'local_evalia'),
        '',
        '2',
        PARAM_FLOAT
    ));

    $settings->add(new admin_setting_configtext(
        'local_evalia/weight_advanced',
        get_string('settings:weight_advanced', 'local_evalia'),
        '',
        '3',
        PARAM_FLOAT
    ));

    // ── Question generation ──────────────────────────────────────────────────
    $settings->add(new admin_setting_heading(
        'local_evalia_questions_heading',
        get_string('settings:heading_questions', 'local_evalia'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'local_evalia/questions_default_count',
        get_string('settings:questions_default_count', 'local_evalia'),
        get_string('settings:questions_default_count_desc', 'local_evalia'),
        5,
        PARAM_INT
    ));

    // ── PDF indexing ─────────────────────────────────────────────────────────
    $settings->add(new admin_setting_heading(
        'local_evalia_pdf_heading',
        get_string('settings:heading_pdf', 'local_evalia'),
        ''
    ));

    $settings->add(new admin_setting_configtextarea(
        'local_evalia/pdf_page_ranges',
        get_string('settings:pdf_page_ranges', 'local_evalia'),
        get_string('settings:pdf_page_ranges_desc', 'local_evalia'),
        '',
        PARAM_RAW
    ));

    // ── Feedback ─────────────────────────────────────────────────────────────
    $settings->add(new admin_setting_heading(
        'local_evalia_feedback_heading',
        get_string('settings:heading_feedback', 'local_evalia'),
        ''
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_evalia/feedback_telegram',
        get_string('settings:feedback_telegram', 'local_evalia'),
        get_string('settings:feedback_telegram_desc', 'local_evalia'),
        1
    ));
}
