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
$lqresult = optional_param('ecresult', '', PARAM_RAW); // JSON Data relayed by mod from EC

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

$lq_data = json_decode($lqresult,true);
//error_log(print_r($ec_data,true));
if(!$lq_data){
	$message = 'failed to decode json';
	$return =array('success'=>false,'message'=>$message);
	return;
}
	
	//flag the current attempt, by resetting old attempts to 0 (and current attempt to 1)
	$wheresql = "lightquizid=? AND userid=?";
	$params   = array($lightquiz->id, $USER->id);
	$DB->set_field_select('lightquiz_attempt', 'status',0, $wheresql, $params);
	
	switch($lq_data['profile']){
		default:
		case 'squiz':
			$sessiongrade ='';
			$data001=$lq_data['totalquestions'];
			$data002=$lq_data['totalcorrect'];
			$sessiongrade="A";
			$sessionscore=$lq_data['percentscore'];
			break;
	}

	//create a new attempt
	$attempt = new stdClass();
	$attempt->status=1;//This is the current, ie most recent, attempt
	$attempt->lightquizid=$lightquiz->id;
	$attempt->userid=$USER->id;
	$attempt->data001=$data001;
	$attempt->data002=$data002;
	$attempt->sessiongrade=$sessiongrade;
	$attempt->sessionscore=$sessionscore;
	$attempt->timecreated=$updatetime;
	$attemptid = $DB->insert_record('lightquiz_attempt',$attempt,true);
	if($attemptid){
		$attempt->id = $attemptid;
	}else{
		$attempt =false;
		$message = 'failed to write attempt to db';
	}
/*	

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
*/

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