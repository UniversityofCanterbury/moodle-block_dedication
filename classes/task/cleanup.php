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
 * Data cleanup. Supports cli and restore type.
 * @package     block_dedication
 * @copyright   2022 University of Canterbury
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dedication\task;

/**
 * Scheduled task to delete logs with origin cli and restore.
 */
class cleanup extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).     *
     * @return string
     */
    public function get_name() {
        return get_string('cleanuptask', 'block_dedication');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $DB;
        $loglifetime = (int) get_config('block_dedication', 'allloglifetime');

        if (empty($loglifetime) || $loglifetime < 0) {
            return;
        }

        // Convert the retention period (stored in seconds) to a cutoff timestamp.
        $cutoff = time() - $loglifetime;
        $start = time();

        // Delete in daily chunks to avoid long-running transactions.
        while ($DB->record_exists_select('block_dedication', 'timestart < :cutoff', ['cutoff' => $cutoff])) {
            $batchend = $cutoff;
            $min = $DB->get_field_select('block_dedication', 'MIN(timestart)', 'timestart < ?', [$cutoff]);
            if ($min !== false) {
                $batchend = min($min + DAYSECS, $cutoff);
            }
            $DB->delete_records_select('block_dedication', 'timestart < :batchend', ['batchend' => $batchend]);

            if (time() > $start + 300) {
                mtrace('  Cleanup time limit reached, will continue next run.');
                break;
            }
        }

        mtrace('  Deleted old records from block_dedication table.');
    }
}
