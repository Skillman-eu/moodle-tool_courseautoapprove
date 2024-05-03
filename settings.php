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
 * Plugin administration pages are defined here.
 *
 * @package     tool_courseautoapprove
 * @category    admin
 * @copyright   2019 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree && $hassiteconfig) {
    $courserequest = $ADMIN->locate('courserequest');
    if (empty($courserequest)) {
        debugging('Unable to locate the courserequest admin node, this is unexpected.');
        $settings = new admin_settingpage('tool_courseautoapprove', new lang_string('pluginname', 'tool_courseautoapprove'));
        $ADMIN->add('courses', $settings);
    } else {
        $settings = $courserequest;
    }

    $name = 'tool_courseautoapprove/maxcourses';
    $title = new lang_string('maxcourses', 'tool_courseautoapprove');
    $description = new lang_string('maxcourses_desc', 'tool_courseautoapprove');
    $default = '1';
    $setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_INT);
    $settings->add($setting);

    $name = 'tool_courseautoapprove/reject';
    $title = new lang_string('reject', 'tool_courseautoapprove');
    $description = new lang_string('reject_desc', 'tool_courseautoapprove');
    $default = 1;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default);
    $settings->add($setting);

    // Skillman: use course template
    $name = 'tool_courseautoapprove/usetemplate';
    $title = new lang_string('usetemplate', 'tool_courseautoapprove');
    $description = new lang_string('usetemplate_desc', 'tool_courseautoapprove');
    $default = 0;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default);
    $settings->add($setting);

    // Skillman: select course as template.
    // Get all courses
    $allcourses = get_courses(null, null, 'c.shortname,c.fullname');
    // Extract course names.
    $courses = array();
    foreach ($allcourses as $course) {
        $courses[$course->id] = $course->fullname;
    }
    // Add course selector (there are admin_setting_configtext_autocomplete since Moodle 4.0).
    $name = 'tool_courseautoapprove/coursetemplate';
    $title = new lang_string('coursetemplate', 'tool_courseautoapprove');
    $description = new lang_string('coursetemplate_desc', 'tool_courseautoapprove');
    $default = '';
    $setting = new admin_setting_configselect($name, $title, $description, $default, $courses);
    //$setting->add_dependent_on('tool_courseautoapprove/usetemplate'); // Not working properly.
    $settings->add($setting);
}
