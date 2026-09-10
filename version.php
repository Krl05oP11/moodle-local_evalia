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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * EVAL-IA local plugin version definition.
 *
 * @package    local_evalia
 * @copyright  2026 Schaller & Ponce <dev@schaller-ponce.com.ar>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component  = 'local_evalia';
$plugin->version    = 2026041001;
$plugin->requires   = 2024042200;   // Moodle 4.4 minimum
$plugin->maturity   = MATURITY_BETA;
$plugin->release    = '0.4.8';
$plugin->supported  = [404, 405];
// No hard dependencies — EVAL-IA ships as a standalone plugin.
// local_saipa engine settings are used as fallback when local_saipa is co-installed.
$plugin->dependencies = [];
