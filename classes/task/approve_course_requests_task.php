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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Provides the {@link 'tool_courseautoapprove\task\pprove_course_requests_task} class.
 *
 * @package     tool_courseautoapprove
 * @category    task
 * @copyright   2014 Dan Poltawski <dan@moodle.com>, 2019 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_courseautoapprove\task;

defined('MOODLE_INTERNAL') || die();

/**
 * A scheduled task for approving course requests.
 *
 * @package    tool_courseautoapprove
 * @copyright  2014 Dan Poltawski <dan@moodle.com>, 2019 David Mudrák <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class approve_course_requests_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('courseautoapprovetask', 'tool_courseautoapprove');
    }

    /**
     * Run the task.
     */
    public function execute() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/lib.php');

        if (empty($CFG->enablecourserequests)) {
            mtrace('... Automatic approval of course requests skipped ($CFG->enablecourserequests disabled).');
            return;
        }

        $config = get_config('tool_courseautoapprove');

        if (empty($config->maxcourses)) {
            mtrace('... Automatic approval of course requests skipped (maxcourses set to zero).');
            return;
        }

        mtrace('... Starting to auto-approve course requests.');

        // Skillman: add sorting to process requests per user.
        $rs = $DB->get_recordset('course_request', [], 'requester, id');
        $curruser = 0;
        $userreqcount = 0;
        
        // Process each request.
        foreach ($rs as $request) {
            $courserequest = new \course_request($request);
            $currentcourses = self::count_courses_user_is_teacher($request->requester);

            if ($currentcourses >= $config->maxcourses) {
                mtrace("... - Denying course request from userid {$request->requester} as they are already a teacher ".
                    "in {$currentcourses} existing course(s) and the limit is {$config->maxcourses}.");

                if ($config->reject && (empty($config->maxreqtoreject) || (int)$config->maxreqtoreject == 1)) {
                    mtrace("...   Marking the course request as rejected and notifying the user.");
                    $courserequest->reject(get_string('rejectmsgcount', 'tool_courseautoapprove',
                        ['currentcourses' => $currentcourses, 'maxcourses' => $config->maxcourses]));
                } elseif ($config->reject && !empty($config->maxreqtoreject) && (int)$config->maxreqtoreject > 1) {
                    // Skillman: count requests of each user.
                    if ($curruser != $request->requester) {
                        $curruser = $request->requester;
                        $userreqcount = 1;
                    } else {
                        $userreqcount++;
                    }
                    // Skillman: reject requests over maxreqtoreject.
                    if ($userreqcount > (int)$config->maxreqtoreject) {
                    mtrace("...   Override maxreqtoreject - marking the course request as rejected and notifying the user.");
                    $courserequest->reject(get_string('rejectmsgmaxreqcount', 'tool_courseautoapprove',
                        ['currentcourses' => $currentcourses, 'maxreqtoreject' => $config->maxcourses]));
                    }
                }

                continue;
            }

            if ($courserequest->check_shortname_collision()) {
                mtrace("... - Denying course request with shortname {$request->shortname} as there is another with the same shortname.");

                if ($config->reject) {
                    mtrace("...   Marking the course request as rejected and notifying the user.");
                    $courserequest->reject(get_string('rejectmshshortname', 'tool_courseautoapprove'));
                }

                continue;
            }

            mtrace("... - Approving course request from userid {$request->requester} for the course {$request->shortname}.");
            if (!empty($config->usetemplate) && !empty($config->coursetemplate)) {
                if ($DB->record_exists('course', ['id' => $config->coursetemplate])) {
                    $res = $this->create_course_from_template($courserequest, $config);
                } else {
                    // Course template not available - general approval.
                    $courserequest->approve();
                }
            } else {
                // General approval.
                $courserequest->approve();
            }
        }
        $rs->close();

        mtrace('... Finished auto-approving course requests.');
    }

    /**
     * Return the number of courses where the given user acts as a teacher.
     *
     * @param int $userid The id of user to check.
     * @return int Number of courses.
     */
    public static function count_courses_user_is_teacher($userid) {

        $result = 0;
        $enroledcourses = enrol_get_all_users_courses($userid);

        foreach ($enroledcourses as $course) {
            \context_helper::preload_from_record($course);
            $context = \context_course::instance($course->id);
            if (has_capability('moodle/course:update', $context, $userid)) {
                $result++;
            }
        }

        return $result;
    }

    private function create_course_from_template(\course_request &$courserequest, object $config): int {
        global $CFG, $DB, $USER;

        require_once($CFG->dirroot . '/course/externallib.php');
        require_once($CFG->dirroot . '/course/format/lib.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        require_once($CFG->dirroot . '/backup/util/dbops/restore_dbops.class.php');

        // TODO: check option users further.
        list($fullname, $shortname) = \restore_dbops::calculate_course_names(0, $courserequest->fullname, $courserequest->shortname);
        $categoryid = $courserequest->get_category()->id;
        $courseid = $config->coursetemplate;

        $options = [
            array('name' => 'blocks', 'value' => 1),
            array('name' => 'activities', 'value' => 1),
            array('name' => 'filters', 'value' => 1),
            array('name' => 'users', 'value' => 0),
        ];
        $visible = 1;
        $startdatetime = usergetmidnight(time());

        if (!$fullname || !$shortname || !$categoryid || !$courseid) {
            $data = json_encode(['status' => 2, 'id' => $courseid, 'cateid' => $categoryid]);
            mtrace('Course duplication error. Data:' . $data);
            return 0;
        }

        $externalobj = new \core_course_external();

        try {
            // Make sure the course's sections have proper labels.
            // See the set_label() method in /backup/util/ui/backup_ui_setting.class.php.
            // The set_label() method sanitizes the section name using PARAM_CLEANHTML (as of Moodle 3.11).
            $sections = $DB->get_records('course_sections', array('course' => $courseid), 'section');

            foreach ($sections as $section) {
                if (isset($section->name) && clean_param($section->name, PARAM_CLEANHTML) === '') {
                    course_get_format($section->course)->inplace_editable_update_section_name($section, 'sectionname', null);

                    $section->name = null;
                    $DB->update_record('course_sections', $section);
                }
            }

            $res = $externalobj->duplicate_course($courseid, $fullname, $shortname, $categoryid, $visible, $options);
        } catch (\moodle_exception $e) {
            $data = json_encode(['status' => 0, 'msg' => $e->getMessage()]);
            mtrace('Course duplication error. Data:' . $data);
            return 0;
        }

        sleep(3);

        if (@isset($res['id'])) {
            try {
                $course = $DB->get_record('course', array('id' => $res['id']));

                if (!empty($startdatetime)) {
                    $course->startdate = $startdatetime;
                    //$course->enddate = $enddatetime;
                    $DB->update_record('course', $course);
                }

                if (!empty($location)) {
                    $eventoption = $DB->get_record(
                        'course_format_options',
                        array(
                            'courseid' => $course->id,
                            'format' => 'event',
                            'name' => 'location'
                        )
                    );
                    $eventoption->value = $location;
                    $DB->update_record('course_format_options', $eventoption);
                }

                $user = $DB->get_record('user', ['id' => $courserequest->get_requester()->id, 'deleted'=>0], '*', MUST_EXIST);

                // Enroll requester user as teacher.
                $context = \context_course::instance($course->id);
                // Add enrol instances.
                if (!$DB->record_exists('enrol', ['courseid' => $course->id, 'enrol' => 'manual'])) {
                    if ($manual = enrol_get_plugin('manual')) {
                        $manual->add_default_instance($course);
                    }
                }
                // Enrol the requester as teacher if necessary.
                if (!empty($CFG->creatornewroleid) and !is_viewing($context, $user, 'moodle/role:assign') and !is_enrolled($context, $user, 'moodle/role:assign')) {
                    enrol_try_internal_enrol($course->id, $user->id, $CFG->creatornewroleid);
                }

                // Delete request.
                $courserequest->delete();

                // Notify user on success.
                $a = new \stdClass();
                $a->name = format_string($course->fullname, true, ['context' => \context_course::instance($course->id, MUST_EXIST)]);
                $a->url = $CFG->wwwroot.'/course/view.php?id=' . $course->id;
                $this->notify($user, $USER, 'courserequestapproved', get_string('courseapprovedsubject'), get_string('courseapprovedemail2', 'moodle', $a), $course->id);
            } catch (\Exception $e) {
                $data = json_encode(['status' => 0, 'msg' => $e->getMessage()]);
                mtrace('Course duplication error. Data:' . $data);
                return 0;
            }

            $data = json_encode(['status' => 1, 'ID' => $res['id']]);
            mtrace('Course duplication success! Data:' . $data);
            return $res['id'];
        } else {
            $data = json_encode(['status' => 0, 'msg' => get_string('unknownerror', 'core')]);
            mtrace('Course duplication error. Data:' . $data);
            return 0;
        }
    }

    /**
     * Send a message from one user to another using events_trigger
     *
     * @param object $touser
     * @param object $fromuser
     * @param string $name
     * @param string $subject
     * @param string $message
     * @param int|null $courseid
     * @throws \coding_exception
     */
    protected function notify($touser, $fromuser, $name='courserequested', $subject, $message, $courseid = null) {
        $eventdata = new \core\message\message();
        $eventdata->courseid          = empty($courseid) ? SITEID : $courseid;
        $eventdata->component         = 'moodle';
        $eventdata->name              = $name;
        $eventdata->userfrom          = $fromuser;
        $eventdata->userto            = $touser;
        $eventdata->subject           = $subject;
        $eventdata->fullmessage       = $message;
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml   = '';
        $eventdata->smallmessage      = '';
        $eventdata->notification      = 1;
        message_send($eventdata);
    }
}
