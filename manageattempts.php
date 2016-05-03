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
 * Action for adding/editing a lightquiz attempt. 
 *
 * @package mod_lightquiz
 * @copyright  2014 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

require_once("../../config.php");
require_once($CFG->dirroot.'/mod/lightquiz/locallib.php');

global $USER,$DB;

// first get the nfo passed in to set up the page
$attemptid = optional_param('attemptid',0 ,PARAM_INT);
$id     = required_param('id', PARAM_INT);         // Course Module ID
$action = optional_param('action','confirmdelete',PARAM_TEXT);

// get the objects we need
$cm = get_coursemodule_from_id('lightquiz', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$lightquiz = $DB->get_record('lightquiz', array('id' => $cm->instance), '*', MUST_EXIST);

//make sure we are logged in and can see this form
require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/lightquiz:manageattempts', $context);

//set up the page object
$PAGE->set_url('/mod/lightquiz/manageattempts.php', array('attemptid'=>$attemptid, 'id'=>$id));
$PAGE->set_title(format_string($lightquiz->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');

//are we in new or edit mode?
if ($attemptid) {
    $attempt = $DB->get_record('lightquiz_attempt', array('id'=>$attemptid,'lightquizid' => $cm->instance), '*', MUST_EXIST);
	if(!$attempt){
		print_error('could not find attempt of id:' . $attemptid);
	}
} else {
    $edit = false;
}

//we always head back to the lightquiz attempts page
$redirecturl = new moodle_url('/mod/lightquiz/reports.php', array('id'=>$cm->id));

//handle delete actions
switch($action){
	case 'confirmdelete':
		$renderer = $PAGE->get_renderer('mod_lightquiz');
		echo $renderer->header($lightquiz, $cm, '', null, get_string('confirmattemptdeletetitle', 'lightquiz'));
		echo $renderer->confirm(get_string("confirmattemptdelete","lightquiz"), 
			new moodle_url('manageattempts.php', array('action'=>'delete','id'=>$cm->id,'attemptid'=>$attemptid)), 
			$redirecturl);
		echo $renderer->footer();
		return;

/////// Delete attempt NOW////////
	case 'delete':
		require_sesskey();
		if (!$DB->delete_records("lightquiz_attempt", array('id'=>$attemptid))){
			print_error("Could not delete attempt");
			if (!$DB->delete_records("lightquiz_phs", array('attemptid'=>$attemptid))){
				print_error("Could not delete answerids");
			}
		}
		redirect($redirecturl);
		return;
	
	case 'confirmdeleteall':
		$renderer = $PAGE->get_renderer('mod_lightquiz');
		echo $renderer->header($lightquiz, $cm, '', null, get_string('confirmattemptdeletealltitle', 'lightquiz'));
		echo $renderer->confirm(get_string("confirmattemptdeleteall","lightquiz"), 
			new moodle_url('manageattempts.php', array('action'=>'deleteall','id'=>$cm->id)), 
			$redirecturl);
		echo $renderer->footer();
		return;
	
	/////// Delete ALL attempts ////////
	case 'deleteall':
		require_sesskey();
		if (!$DB->delete_records("lightquiz_attempt", array('lightquizid'=>$lightquiz->id))){
			print_error("Could not delete attempts (all)");
			if (!$DB->delete_records("lightquiz_phs", array('lightquizid'=>$lightquiz->id))){
				print_error("Could not delete logs (all)");
			}
		}
		redirect($redirecturl);
		return;

}

//we should never get here
echo "You should not get here";