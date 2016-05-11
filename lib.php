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
 * Library of interface functions and constants for module lightquiz
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the lightquiz specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod_lightquiz
 * @copyright  2014 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

define('MOD_LIGHTQUIZ_GRADEHIGHEST', 0);
define('MOD_LIGHTQUIZ_GRADELOWEST', 1);
define('MOD_LIGHTQUIZ_GRADELATEST', 2);
define('MOD_LIGHTQUIZ_GRADEAVERAGE', 3);
define('MOD_LIGHTQUIZ_GRADENONE', 4);

define('MOD_LIGHTQUIZ_INT_DATA1', 'data001');
define('MOD_LIGHTQUIZ_INT_DATA2', 'data002');
define('MOD_LIGHTQUIZ_INT_DATA3', 'data003');
define('MOD_LIGHTQUIZ_TEXT_DATA1', 'data004');
define('MOD_LIGHTQUIZ_TEXT_DATA2', 'data005');
define('MOD_LIGHTQUIZ_TEXT_DATA3', 'data006');
define('MOD_LIGHTQUIZ_BOOL_DATA1', 'data007');




////////////////////////////////////////////////////////////////////////////////
// Moodle core API                                                            //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the information on whether the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function lightquiz_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:         return true;
        case FEATURE_SHOW_DESCRIPTION:  return true;
		case FEATURE_GRADE_HAS_GRADE:         return true;
		case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
		case FEATURE_GRADE_OUTCOMES:          return true;
        case FEATURE_BACKUP_MOODLE2:          return true;
        default:                        return null;
    }
}


/**
 * Create grade item for given Englsh Central
 *
 * @category grade
 * @uses GRADE_TYPE_VALUE
 * @uses GRADE_TYPE_NONE
 * @param object $lightquiz object with extra cmidnumber
 * @param array|object $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function lightquiz_grade_item_update($lightquiz, $grades=null) {
    global $CFG;
    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }

    if (array_key_exists('cmidnumber', $lightquiz)) { //it may not be always present
        $params = array('itemname'=>$lightquiz->name, 'idnumber'=>$lightquiz->cmidnumber);
    } else {
        $params = array('itemname'=>$lightquiz->name);
    }

    if ($lightquiz->grade > 0) {
        $params['gradetype']  = GRADE_TYPE_VALUE;
        $params['grademax']   = $lightquiz->grade;
        $params['grademin']   = 0;
    } else if ($lightquiz->grade < 0) {
        $params['gradetype']  = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$lightquiz->grade;

        // Make sure current grade fetched correctly from $grades
        $currentgrade = null;
        if (!empty($grades)) {
            if (is_array($grades)) {
                $currentgrade = reset($grades);
            } else {
                $currentgrade = $grades;
            }
        }

        // When converting a score to a scale, use scale's grade maximum to calculate it.
        if (!empty($currentgrade) && $currentgrade->rawgrade !== null) {
            $grade = grade_get_grades($lightquiz->course, 'mod', 'lightquiz', $lightquiz->id, $currentgrade->userid);
            $params['grademax']   = reset($grade->items)->grademax;
        }
    } else {
        $params['gradetype']  = GRADE_TYPE_NONE;
    }

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = null;
    } else if (!empty($grades)) {
        // Need to calculate raw grade (Note: $grades has many forms)
        if (is_object($grades)) {
            $grades = array($grades->userid => $grades);
        } else if (array_key_exists('userid', $grades)) {
            $grades = array($grades['userid'] => $grades);
        }
        foreach ($grades as $key => $grade) {
            if (!is_array($grade)) {
                $grades[$key] = $grade = (array) $grade;
            }
            //check raw grade isnt null otherwise we insert a grade of 0
            if ($grade['rawgrade'] !== null) {
                $grades[$key]['rawgrade'] = ($grade['rawgrade'] * $params['grademax'] / 100);
            } else {
                //setting rawgrade to null just in case user is deleting a grade
                $grades[$key]['rawgrade'] = null;
            }
        }
    }


    return grade_update('mod/lightquiz', $lightquiz->course, 'mod', 'lightquiz', $lightquiz->id, 0, $grades, $params);
}

/**
 * Update grades in central gradebook
 *
 * @category grade
 * @param object $lightquiz
 * @param int $userid specific user only, 0 means all
 * @param bool $nullifnone
 */
function lightquiz_update_grades($lightquiz, $userid=0, $nullifnone=true) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    if ($lightquiz->grade == 0) {
        lightquiz_grade_item_update($lightquiz);

    } else if ($grades = lightquiz_get_user_grades($lightquiz, $userid)) {
        lightquiz_grade_item_update($lightquiz, $grades);

    } else if ($userid and $nullifnone) {
        $grade = new stdClass();
        $grade->userid   = $userid;
        $grade->rawgrade = null;
        lightquiz_grade_item_update($lightquiz, $grade);

    } else {
        lightquiz_grade_item_update($lightquiz);
    }
	
	//echo "updategrades" . $userid;
}

/**
 * Return grade for given user or all users.
 *
 * @global stdClass
 * @global object
 * @param int $lightquizid id of lightquiz
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function lightquiz_get_user_grades($lightquiz, $userid=0) {
    global $CFG, $DB;

    $params = array("lightquizid" => $lightquiz->id);

    if (!empty($userid)) {
        $params["userid"] = $userid;
        $user = "AND u.id = :userid";
    }
    else {
        $user="";

    }

	if($lightquiz->field005 !=1){
		$overallgrade = '(a.sessionscore * (a.data005 * (1 / a.data001)))';
	}else{
		$overallgrade = '(a.sessionscore * a.data007)';
	}

    if ($lightquiz->maxattempts==1 || $lightquiz->gradeoptions == MOD_LIGHTQUIZ_GRADELATEST) {

        $sql = "SELECT u.id, u.id AS userid, $overallgrade AS rawgrade
                  FROM {user} u,  {lightquiz_attempt} a
                 WHERE u.id = a.userid AND a.lightquizid = :lightquizid
                       AND a.status = 1
                       $user";
	
	}else{
		switch($lightquiz->gradeoptions){
			case MOD_LIGHTQUIZ_GRADEHIGHEST:
				$sql = "SELECT u.id, u.id AS userid, MAX( $overallgrade ) AS rawgrade
                      FROM {user} u, {lightquiz_attempt} a
                     WHERE u.id = a.userid AND a.lightquizid = :lightquizid
                           $user
                  GROUP BY u.id";
				  break;
			case MOD_LIGHTQUIZ_GRADELOWEST:
				$sql = "SELECT u.id, u.id AS userid, MIN(  $overallgrade ) AS rawgrade
                      FROM {user} u, {lightquiz_attempt} a
                     WHERE u.id = a.userid AND a.lightquizid = :lightquizid
                           $user
                  GROUP BY u.id";
				  break;
			case MOD_LIGHTQUIZ_GRADEAVERAGE:
            $sql = "SELECT u.id, u.id AS userid, AVG(  $overallgrade ) AS rawgrade
                      FROM {user} u, {lightquiz_attempt} a
                     WHERE u.id = a.userid AND a.lightquizid = :lightquizid
                           $user
                  GROUP BY u.id";
				  break;

        }

    } 
	/*
echo $sql;
print_r($params);	
print_r($DB->get_records_sql($sql, $params));
*/
    return $DB->get_records_sql($sql, $params);
}


/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the lightquiz.
 *
 * @param $mform form passed by reference
 */
function lightquiz_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'lightquizheader', get_string('modulenameplural', 'lightquiz'));
    $mform->addElement('advcheckbox', 'reset_lightquiz', get_string('deleteallattempts','lightquiz'));
}

/**
 * Course reset form defaults.
 * @param object $course
 * @return array
 */
function lightquiz_reset_course_form_defaults($course) {
    return array('reset_lightquiz'=>1);
}

/**
 * Removes all grades from gradebook
 *
 * @global stdClass
 * @global object
 * @param int $courseid
 * @param string optional type
 */
function lightquiz_reset_gradebook($courseid, $type='') {
    global $CFG, $DB;

    $sql = "SELECT l.*, cm.idnumber as cmidnumber, l.course as courseid
              FROM {lightquiz} l, {course_modules} cm, {modules} m
             WHERE m.name='lightquiz' AND m.id=cm.module AND cm.instance=l.id AND l.course=:course";
    $params = array ("course" => $courseid);
    if ($lightquizs = $DB->get_records_sql($sql,$params)) {
        foreach ($lightquizs as $lightquiz) {
            lightquiz_grade_item_update($lightquiz, 'reset');
        }
    }
}

/**
 * Actual implementation of the reset course functionality, delete all the
 * lightquiz attempts for course $data->courseid.
 *
 * @global stdClass
 * @global object
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function lightquiz_reset_userdata($data) {
    global $CFG, $DB;

    $componentstr = get_string('modulenameplural', 'lightquiz');
    $status = array();

    if (!empty($data->reset_lightquiz)) {
        $lightquizssql = "SELECT l.id
                         FROM {lightquiz} l
                        WHERE l.course=:course";

        $params = array ("course" => $data->courseid);
        $DB->delete_records_select('lightquiz_attempt', "lightquizid IN ($lightquizssql)", $params);
        $DB->delete_records_select('lightquiz_phs', "lightquizid IN ($lightquizssql)", $params);

        // remove all grades from gradebook
        if (empty($data->reset_gradebook_grades)) {
            lightquiz_reset_gradebook($data->courseid);
        }

        $status[] = array('component'=>$componentstr, 'item'=>get_string('deleteallattempts', 'lightquiz'), 'error'=>false);
    }

    /// updating dates - shift may be negative too
    if ($data->timeshift) {
        shift_course_mod_dates('lightquiz', array('available', 'deadline'), $data->timeshift, $data->courseid);
        $status[] = array('component'=>$componentstr, 'item'=>get_string('datechanged'), 'error'=>false);
    }

    return $status;
}


/**
 * Saves a new instance of the lightquiz into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $lightquiz An object from the form in mod_form.php
 * @param mod_lightquiz_mod_form $mform
 * @return int The id of the newly inserted lightquiz record
 */
function lightquiz_add_instance(stdClass $lightquiz, mod_lightquiz_mod_form $mform = null) {
    global $DB;

    $lightquiz->timecreated = time();

    # You may have to add extra stuff in here #

    $ecid =  $DB->insert_record('lightquiz', $lightquiz);
	$lightquiz->id = $ecid;
	
	  // update grade item definition
    lightquiz_grade_item_update($lightquiz);
	
	return $ecid;


}

/**
 * Updates an instance of the lightquiz in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $lightquiz An object from the form in mod_form.php
 * @param mod_lightquiz_mod_form $mform
 * @return boolean Success/Fail
 */
function lightquiz_update_instance(stdClass $lightquiz, mod_lightquiz_mod_form $mform = null) {
    global $DB;

    $lightquiz->timemodified = time();
    $lightquiz->id = $lightquiz->instance;

    # You may have to add extra stuff in here #

   $DB->update_record('lightquiz', $lightquiz);
	
	// update grade item definition
    lightquiz_grade_item_update($lightquiz);

    // update grades - TODO: do it only when grading style changes
    lightquiz_update_grades($lightquiz, 0, false);
	
	return true;
}

/**
 * Removes an instance of the lightquiz from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function lightquiz_delete_instance($id) {
    global $DB;

    if (! $lightquiz = $DB->get_record('lightquiz', array('id' => $id))) {
        return false;
    }

    # Delete any dependent records here #

    $DB->delete_records('lightquiz', array('id' => $lightquiz->id));

    return true;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return stdClass|null
 */
function lightquiz_user_outline($course, $user, $mod, $lightquiz) {

    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $lightquiz the module instance record
 * @return void, is supposed to echp directly
 */
function lightquiz_user_complete($course, $user, $mod, $lightquiz) {
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in lightquiz activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 */
function lightquiz_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;  //  True if anything was printed, otherwise false
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link lightquiz_print_recent_mod_activity()}.
 *
 * @param array $activities sequentially indexed array of objects with the 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 * @return void adds items into $activities and increases $index
 */
function lightquiz_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@see lightquiz_get_recent_mod_activity()}

 * @return void
 */
function lightquiz_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function lightquiz_cron () {
    return true;
}

/**
 * Returns all other caps used in the module
 *
 * @example return array('moodle/site:accessallgroups');
 * @return array
 */
function lightquiz_get_extra_capabilities() {
    return array();
}

////////////////////////////////////////////////////////////////////////////////
// Gradebook API                                                              //
////////////////////////////////////////////////////////////////////////////////

/**
 * Is a given scale used by the instance of lightquiz?
 *
 * This function returns if a scale is being used by one lightquiz
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $lightquizid ID of an instance of this module
 * @return bool true if the scale is used by the given lightquiz instance
 */
function lightquiz_scale_used($lightquizid, $scaleid) {
    global $DB;

    /** @example */
    if ($scaleid and $DB->record_exists('lightquiz', array('id' => $lightquizid, 'grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks if scale is being used by any instance of lightquiz.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param $scaleid int
 * @return boolean true if the scale is used by any lightquiz instance
 */
function lightquiz_scale_used_anywhere($scaleid) {
    global $DB;

    /** @example */
    if ($scaleid and $DB->record_exists('lightquiz', array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}


////////////////////////////////////////////////////////////////////////////////
// File API                                                                   //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function lightquiz_get_file_areas($course, $cm, $context) {
    return array();
}

/**
 * File browsing support for lightquiz file areas
 *
 * @package mod_lightquiz
 * @category files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 */
function lightquiz_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the lightquiz file areas
 *
 * @package mod_lightquiz
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the lightquiz's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function lightquiz_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);

    send_file_not_found();
}

////////////////////////////////////////////////////////////////////////////////
// Navigation API                                                             //
////////////////////////////////////////////////////////////////////////////////

/**
 * Extends the global navigation tree by adding lightquiz nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the lightquiz module instance
 * @param stdClass $course
 * @param stdClass $module
 * @param cm_info $cm
 */
function lightquiz_extend_navigation(navigation_node $navref, stdclass $course, stdclass $module, cm_info $cm) {
}

/**
 * Extends the settings navigation with the lightquiz settings
 *
 * This function is called when the context for the page is a lightquiz module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {@link settings_navigation}
 * @param navigation_node $lightquiznode {@link navigation_node}
 */
function lightquiz_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $lightquiznode=null) {
}
