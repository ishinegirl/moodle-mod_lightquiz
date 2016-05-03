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
 * 
 *
 *
 * @package    mod_lightquiz
 * @copyright  2014 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');


$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$ecresult = optional_param('ecresult', '', PARAM_RAW); // JSON Data relayed by mod from EC

//call so that we know we are who we said we are
require_sesskey();

if ($id) {
    $cm         = get_coursemodule_from_id('lightquiz', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $lightquiz  = $DB->get_record('lightquiz', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

global $DB,$USER;

//init a few vars
$result = false;
$message ='';
$updatetime = time();

//turn json data into php assoc array

$ec_data = json_decode($ecresult,true);
//error_log(print_r($ec_data,true));
if(!$ec_data){
	$message = 'failed to decode json';
	$return =array('success'=>false,'message'=>$message);
	return;
}

	
	//flag the current attempt, by resetting old attempts to 0 (and current attempt to 1)
	$wheresql = "lightquizid=? AND userid=?";
	$params   = array($lightquiz->id, $USER->id);
	$DB->set_field_select('lightquiz_attempt', 'status',0, $wheresql, $params);

	//create a new attempt
	$attempt = new stdClass();
	$attempt->status=1;//This is the current, ie most recent, attempt
	$attempt->lightquizid=$lightquiz->id;
	$attempt->userid=$USER->id;
	$attempt->data001=$ec_data['linesTotal'];
	$attempt->data003=$ec_data['totalActiveTime'];
	$attempt->data002=$ec_data['watchedComplete'];
	$attempt->activetime=$ec_data['activeTime'];
	$attempt->data004=$updatetime;//$ec_data['dateCompleted'];
	$attempt->data005=$ec_data['linesRecorded'];
	$attempt->data006=$ec_data['linesWatched'];
	$attempt->points=$ec_data['points'];
	$attempt->data007=$ec_data['recordingComplete'];
	$attempt->sessiongrade=$ec_data['sessionGrade'];
	$attempt->sessionscore=(100* $ec_data['sessionScore']);
	$attempt->mediaid=$ec_data['mediaid'];
	$attempt->timecreated=$updatetime;
	$attemptid = $DB->insert_record('lightquiz_attempt',$attempt,true);
	if($attemptid){
		$attempt->id = $attemptid;
	}else{
		$attempt =false;
		$message = 'failed to write attempt to db';
	}

if($attempt && $attempt->status==1){
	//add the answerids
	$answerids = json_decode($ec_data['answeridsCount'],true);
	//error_log(print_r($answerids,true));
	$result=true;
	foreach($answerids['answerids'] as $sound=>$answerid){
		$phobj = new stdClass();
		$phobj->attemptid = $attempt->id;
		$phobj->lightquizid = $attempt->lightquizid;
		$phobj->userid = $attempt->userid;
		$phobj->answerid = $sound;
		$phobj->chosenanswer = $answerid['badCount'];
		$phobj->wascorrect = $answerid['goodCount'];
		$phobj->timecreated = $updatetime;
		$result = $DB->insert_record('lightquiz_phs', $phobj,true);
		if(!$result){
			$message = 'failed to write phenome data to db';
			break;
		}
	}
}
//update the gradebook
if($attempt){
	lightquiz_update_grades($lightquiz, $attempt->userid);
}

//return a success/failure flag to browser
if($attempt && $result){
	$message= "allgood";
	$return =array('success'=>true,'message'=>$message);
	echo json_encode($return);
}else{
	$return =array('success'=>false,'message'=>$message);
	echo json_encode($return);
}