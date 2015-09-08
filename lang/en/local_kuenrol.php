<?php
// This file is a part of Kasetsart Moodle Kit - https://github.com/Pongkemon01/moodle-local_kuenrol
//
// Kasetsart Moodle Kit is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Kasetsart Moodle Kit is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Enrollment refresh is use to synchronize student enrollment with registration office.
 *
 * @package     local_kuenrol
 * @author      Akrapong Patchararungruang
 * @copyright   2015 Kasetsart University
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

$string['pluginname']		= 'Enrolment update with KU Regis';
$string['pluginshortname']	= 'local_kuenrol';
$string['menu_text']		= 'Update with KU';
$string['menu_short']		= 'KUupdater';
$string['btn_next']			= 'Next';
$string['btn_start']		= 'Start';
$string['btn_cancel']		= 'Cancel';

// String for form1
$string['f1_header']		= 'Information for KU Regis.';
$string['f1_account']		= 'Teacher\'s Nontri account';
$string['f1_password']		= 'Nontri password';
$string['f1_campus']		= 'Campus';
$string['f1_bangken']		= 'Bangken';
$string['f1_kumpangsaen']	= 'Kumpangsaen';
$string['f1_courseid']		= 'Course ID';
$string['f1_year']			= 'Academic year  25';
$string['f1_semester']		= 'Semester';
$string['f1_sem_first']		= 'First';
$string['f1_sem_second']	= 'Second';
$string['f1_sem_third']		= 'Third';
$string['f1_sem_summer']	= 'Summer';
$string['f1_sem_summer2']	= 'Summer 2';

// String for from2
$string['f2_header']		= 'Section choices.';
$string['f2_grplist']		= 'Available sections from KU Regis to update (lecture:lab)';
$string['f2_autocreate']	= 'Create user accounts in Moodle for new students';
$string['f2_choices']		= 'Updating choices:';
$string['f2_autoadd']		= 'Automatically enrol new users';
$string['f2_roleid']		= 'User role for auto-enrollment:';
$string['f2_noroleid']		= 'No Enrollments';
$string['f2_distribute']	= 'Invoke students into corresponding groups';
$string['f2_missingaction'] = 'Action to unmatch students';
$string['f2_missing_none']	= 'None';
$string['f2_missing_susp']	= 'Suspend';
$string['f2_missing_del']	= 'Withdraw';
$string['f2_revoke']		= 'Revoke students from unmatch groups';