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

namespace block_dedication;

use block_dedication\privacy\provider;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\userlist;
use core_privacy\tests\provider_testcase;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the privacy provider.
 *
 * @package    block_dedication
 * @copyright  2026 University of Canterbury
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_dedication\privacy\provider
 */
final class privacy_provider_test extends provider_testcase {
    /** @var \stdClass Course A. */
    private $coursea;

    /** @var \stdClass Course B. */
    private $courseb;

    /** @var \context_block Dedication block context in course A. */
    private $blocka;

    /** @var \context_block Dedication block context in course B. */
    private $blockb;

    /** @var \stdClass User with dedication rows in course A only. */
    private $usera;

    /** @var \stdClass User with dedication rows in course B only. */
    private $userb;

    /**
     * Two courses, each with a dedication block and one user who has dedication rows there.
     */
    protected function setUp(): void {
        global $DB;

        parent::setUp();
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $this->coursea = $generator->create_course();
        $this->courseb = $generator->create_course();
        $this->blocka = $this->add_dedication_block($this->coursea);
        $this->blockb = $this->add_dedication_block($this->courseb);

        $this->usera = $generator->create_user();
        $this->userb = $generator->create_user();
        $DB->insert_record('block_dedication', (object) [
            'userid' => $this->usera->id, 'courseid' => $this->coursea->id, 'timespent' => 600, 'timestart' => time() - 3600,
        ]);
        $DB->insert_record('block_dedication', (object) [
            'userid' => $this->userb->id, 'courseid' => $this->courseb->id, 'timespent' => 900, 'timestart' => time() - 7200,
        ]);
    }

    /**
     * Add a dedication block to a course and return its context.
     *
     * @param \stdClass $course The course.
     * @return \context_block The new block's context.
     */
    private function add_dedication_block(\stdClass $course): \context_block {
        $instance = $this->getDataGenerator()->create_block('dedication', [
            'parentcontextid' => \context_course::instance($course->id)->id,
        ]);
        return \context_block::instance($instance->id);
    }

    /**
     * The userlist for a block context holds only the users with rows in that block's course.
     */
    public function test_get_users_in_context_is_scoped_to_the_course(): void {
        $userlist = new userlist($this->blocka, 'block_dedication');
        provider::get_users_in_context($userlist);
        $this->assertEquals([$this->usera->id], $userlist->get_userids());

        $userlist = new userlist($this->blockb, 'block_dedication');
        provider::get_users_in_context($userlist);
        $this->assertEquals([$this->userb->id], $userlist->get_userids());
    }

    /**
     * Only block contexts are answered.
     */
    public function test_get_users_in_context_ignores_other_contexts(): void {
        $userlist = new userlist(\context_course::instance($this->coursea->id), 'block_dedication');
        provider::get_users_in_context($userlist);
        $this->assertEmpty($userlist->get_userids());
    }

    /**
     * A user's contexts are the dedication blocks of the courses they have rows in.
     */
    public function test_get_contexts_for_userid(): void {
        $this->assertEquals([$this->blocka->id], provider::get_contexts_for_userid($this->usera->id)->get_contextids());
        $this->assertEquals([$this->blockb->id], provider::get_contexts_for_userid($this->userb->id)->get_contextids());

        $nobody = $this->getDataGenerator()->create_user();
        $this->assertEmpty(provider::get_contexts_for_userid($nobody->id)->get_contextids());
    }

    /**
     * A course with two dedication blocks yields both block contexts; a course with none yields nothing.
     */
    public function test_get_contexts_for_userid_follows_block_instances(): void {
        global $DB;

        $second = $this->add_dedication_block($this->coursea);
        $this->assertEqualsCanonicalizing(
            [$this->blocka->id, $second->id],
            provider::get_contexts_for_userid($this->usera->id)->get_contextids()
        );

        $blockless = $this->getDataGenerator()->create_course();
        $DB->insert_record('block_dedication', (object) [
            'userid' => $this->userb->id, 'courseid' => $blockless->id, 'timespent' => 60, 'timestart' => time(),
        ]);
        $this->assertEquals([$this->blockb->id], provider::get_contexts_for_userid($this->userb->id)->get_contextids());
    }

    /**
     * Deleting a user's data in a block context removes their rows for that block's course only.
     */
    public function test_delete_data_for_user_in_block_context(): void {
        global $DB;

        // The user also has rows in course B, which are not in the approved contexts.
        $DB->insert_record('block_dedication', (object) [
            'userid' => $this->usera->id, 'courseid' => $this->courseb->id, 'timespent' => 120, 'timestart' => time(),
        ]);

        provider::delete_data_for_user(new approved_contextlist($this->usera, 'block_dedication', [$this->blocka->id]));

        $this->assertEquals(0, $DB->count_records('block_dedication', ['userid' => $this->usera->id, 'courseid' => $this->coursea->id]));
        $this->assertEquals(1, $DB->count_records('block_dedication', ['userid' => $this->usera->id, 'courseid' => $this->courseb->id]));
        $this->assertEquals(1, $DB->count_records('block_dedication', ['userid' => $this->userb->id]));
    }

    /**
     * Deleting everything in a block context removes every row for that block's course only.
     */
    public function test_delete_data_for_all_users_in_block_context(): void {
        global $DB;

        provider::delete_data_for_all_users_in_context($this->blocka);

        $this->assertEquals(0, $DB->count_records('block_dedication', ['courseid' => $this->coursea->id]));
        $this->assertEquals(1, $DB->count_records('block_dedication', ['courseid' => $this->courseb->id]));
    }

    /**
     * Course and user contexts keep working as before.
     */
    public function test_delete_data_in_course_and_user_contexts(): void {
        global $DB;

        provider::delete_data_for_all_users_in_context(\context_course::instance($this->courseb->id));
        $this->assertEquals(0, $DB->count_records('block_dedication', ['courseid' => $this->courseb->id]));

        $usercontext = \context_user::instance($this->usera->id);
        provider::delete_data_for_user(new approved_contextlist($this->usera, 'block_dedication', [$usercontext->id]));
        $this->assertEquals(0, $DB->count_records('block_dedication', ['userid' => $this->usera->id]));
    }
}
