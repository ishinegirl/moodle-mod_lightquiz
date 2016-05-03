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
 * Prints a particular instance of lightquiz
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_lightquiz
 * @copyright  2014 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // lightquiz instance ID - it should be named as the first character of the module

if ($id) {
    $cm         = get_coursemodule_from_id('lightquiz', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $lightquiz  = $DB->get_record('lightquiz', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($n) {
    $lightquiz  = $DB->get_record('lightquiz', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $lightquiz->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('lightquiz', $lightquiz->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$modulecontext = context_module::instance($cm->id);

global $USER;

//Diverge logging logic at Moodle 2.7
if($CFG->version<2014051200){ 
	add_to_log($course->id, 'lightquiz', 'view', "view.php?id={$cm->id}", $lightquiz->name, $cm->id);
}else{
	// Trigger module viewed event.
	$event = \mod_lightquiz\event\course_module_viewed::create(array(
	   'objectid' => $lightquiz->id,
	   'context' => $modulecontext
	));
	$event->add_record_snapshot('course_modules', $cm);
	$event->add_record_snapshot('course', $course);
	$event->add_record_snapshot('lightquiz', $lightquiz);
	$event->trigger();
} 


//if we got this far, we can consider the activity "viewed"
$completion = new completion_info($course);
$completion->set_module_viewed($cm);


/// Set up the page header
$PAGE->set_url('/mod/lightquiz/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($lightquiz->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('course');


//get our javascript all ready to go
$jsmodule = array(
	'name'     => 'mod_lightquiz',
	'fullpath' => '/mod/lightquiz/module.js',
	'requires' => array('io','json','button')
);
//here we set up any info we need to pass into javascript
$opts =Array(); 
$opts['mediaid'] =$lightquiz->mediaid; 
$opts['field001'] =$lightquiz->field001==1;
$opts['field002'] =$lightquiz->field002==1; 
$opts['field005'] =$lightquiz->field005==1; 
$opts['field003'] =$lightquiz->field003==1; 
$opts['field004'] =$lightquiz->field004==1; 
$opts['lightbox'] =$lightquiz->field006==1;
$opts['field007'] =$lightquiz->field007==1;
$opts['resultsmode'] ='ajax';
$opts['playerdiv'] ='mod_lightquiz_playercontainer';
$opts['resultsdiv'] ='mod_lightquiz_resultscontainer';

//this inits the M.mod_lightquiz thingy, after the page has loaded.
$PAGE->requires->js_init_call('M.mod_lightquiz.playerhelper.init', array($opts),false,$jsmodule);

//this loads the strings we need into JS
$PAGE->requires->strings_for_js(array('sessionresults','sessionscore','sessiongrade','data006',
						'data005','compositescore','activetime','data003'), 'lightquiz');

//this loads any external JS libraries we need to call
//$PAGE->requires->js("/mod/lightquiz/js/ec.js");
//$PAGE->requires->js(new moodle_url('https://www.lightquiz.com/platform/ec.js'),true);

//This puts all our display logic into the renderer.php file in this plugin
//theme developers can override classes there, so it makes it customizable for others
//to do it this way.
$renderer = $PAGE->get_renderer('mod_lightquiz');

echo $renderer->header($lightquiz, $cm, 'view',null, get_string('view', 'lightquiz'));


echo $renderer->show_intro($lightquiz, $cm);

//if we have attempts and we are not a manager/teacher then lets show a summary of them
$hasattempts=false;
//if(!has_capability('mod/lightquiz:manageattempts', $module_context)){
	$attempts = $DB->get_records('lightquiz_attempt',array('userid'=>$USER->id,'lightquizid'=>$lightquiz->id));
	if($attempts){
		$hasattempts=true;
		echo $renderer->show_myattempts($lightquiz, $attempts);
	}
//}

// Replace the following lines with your own code
//echo $renderer->show_ec_options();
//$thumburl="http://demo.poodll.com/pluginfile.php/10/course/summary/pdclogo.jpg";
//echo $renderer->show_ec_link($lightquiz->mediatitle, $thumburl, $lightquiz->mediaid);
if($lightquiz->maxattempts == 0|| count($attempts)<$lightquiz->maxattempts){
	echo $renderer->show_bigbutton($hasattempts);
	echo $renderer->show_ec_box();
}else{
	echo $renderer->show_exceededattempts($lightquiz,$attempts);
}

// Finish the page
echo $renderer->footer();
