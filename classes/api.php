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
 * Public API for the dedication block.
 *
 * @package    block_dedication
 * @copyright  2026 University of Canterbury
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dedication;

use block_dedication\local\utils;

defined('MOODLE_INTERNAL') || die();

/**
 * Stable public interface for other plugins to consume dedication data.
 */
class api {
    /**
     * Calculate averages and totals for timespent in course.
     *
     * @param int $courseid Course ID.
     * @param int|null $duration Duration window in seconds.
     * @param bool $filter Whether to apply ACE filters.
     * @return array Associative array with average, total, usercount, roles keys.
     */
    public static function get_average(int $courseid, ?int $duration = null, bool $filter = false): array {
        return utils::get_average($courseid, $duration, $filter);
    }
}
