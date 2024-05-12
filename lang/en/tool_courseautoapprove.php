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
 * Plugin strings are defined here.
 *
 * @package     tool_courseautoapprove
 * @category    string
 * @copyright   2014 Dan Poltawski <dan@moodle.com>, 2019 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['courseautoapprovetask'] = 'Automatic approval of course requests';
$string['maxcourses'] = 'Maximum auto-approved courses';
$string['maxcourses_desc'] = 'How many courses per user are automatically approved. To disable the feature, set the number to zero.';
$string['pluginname'] = 'Course auto-approval';
$string['privacy:metadata'] = 'Course auto-approval does not store any personal data';
$string['reject'] = 'Auto-reject extra courses';
$string['reject_desc'] = 'Set this to \'Yes\' to automatically reject course requests once the limit is reached. If not set, requests that were not approved automatically, will have to be processed manually.';
$string['rejectmsgcount'] = 'You are already a teacher in {$a->currentcourses} course(s) and the limit has been set to {$a->maxcourses} course(s).';
$string['rejectmshshortname'] = 'There is another course with that short name';
$string['usetemplate'] = 'Use course template';
$string['usetemplate_desc'] = 'Set this to \'Yes\' to automatically create course from specified template.';
$string['coursetemplate'] = 'Select a course template';
$string['coursetemplate_desc'] = 'Select a course to be used as template when request has been approved.';
$string['maxreqtoreject'] = 'Auto-reject if number of requests exceeded';
$string['maxreqtoreject_desc'] = 'Set maximal number of requests that auto-rejection will start automatically once it will be reached.';
$string['rejectmsgmaxreqcount'] = 'You are already a teacher in {$a->currentcourses} course(s), requested {$a->userreqcount} more so far and the limit of requests has been set to {$a->maxreqtoreject} course(s).';
$string['approvemessage'] = 'Custom approve message';
$string['approvemessage_desc'] = 'Text of custom message being sent on request approval. Supported placeholders {COURSENAME}, {COURSEURL}, {USERNAME}, {FIRSTNAMME}, {LASTNAME}';
$string['courseapprovemessage'] = 'Your requested DQF ambient in form of Moodle course, {COURSENAME}, has been approved. To access it, go to {COURSEURL}';

