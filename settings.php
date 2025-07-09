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
 * Settings for block.
 * @package block_dedication
 * @copyright 2022 University of Canterbury
 * @author Pramith Dayananda <pramithd@catalyst.net.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configduration('block_dedication/ignore_sessions_limit',
        new lang_string('ignore_sessions_limit', 'block_dedication'),
        new lang_string('ignore_sessions_limit_desc', 'block_dedication') ,
        1 * MINSECS, PARAM_INT)
    );

    $settings->add(new admin_setting_configduration('block_dedication/session_limit',
        new lang_string('session_limit', 'block_dedication'),
        new lang_string('session_limit_desc', 'block_dedication'),
        HOURSECS, PARAM_INT)
    );

    $settings->add(new admin_setting_configduration(
        'block_dedication/allloglifetime',
        new lang_string('allloglifetime', 'block_dedication'),
        new lang_string('configallloglifetime', 'block_dedication'), YEARSECS, PARAM_INT));

    // Retrieve all roles from the Moodle system to populate a multiselect field,
    // allowing the Site Administrator to choose which roles should be included in the calculation.
    $moodleroles = $DB->get_records('role');

    // Create storing array based variables.
    $defaultselection = $roles = [];
    // Loop through data return and collect roles 'id' and 'name'.
    foreach ($moodleroles as $role) {
        $defaultselection[] = $role->id;
        $roles[$role->id] = $role->name;
    }
    asort($roles);

    // Add a multiselect setting field with available roles for selection.
    $settings->add(new admin_setting_configmultiselect(
        'block_dedication/rolespecify',
        get_string('rolespecify', 'block_dedication'),
        get_string('rolespecifydescription', 'block_dedication'),
        $defaultselection,
        $roles
    ));
}
