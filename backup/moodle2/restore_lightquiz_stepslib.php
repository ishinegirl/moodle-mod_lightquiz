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
 * @package   mod_lightquiz
 * @copyright 2014 Justin Hunt poodllsupport@gmail.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
 require_once($CFG->dirroot . '/mod/lightquiz/lib.php');

/**
 * Define all the restore steps that will be used by the restore_lightquiz_activity_task
 */

/**
 * Structure step to restore one lightquiz activity
 */
class restore_lightquiz_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();

        $userinfo = $this->get_setting_value('userinfo'); // are we including userinfo?

        ////////////////////////////////////////////////////////////////////////
        // XML interesting paths - non-user data
        ////////////////////////////////////////////////////////////////////////

        // root element describing lightquiz instance
        $lightquiz = new restore_path_element('lightquiz', '/activity/lightquiz');
        $paths[] = $lightquiz;

		

        // End here if no-user data has been selected
        if (!$userinfo) {
            return $this->prepare_activity_structure($paths);
        }

        ////////////////////////////////////////////////////////////////////////
        // XML interesting paths - user data
        ////////////////////////////////////////////////////////////////////////
		//attempts
		 $attempts= new restore_path_element('lightquiz_attempts',
                                            '/activity/lightquiz/attempts/attempt');
		$paths[] = $attempts;
		 
		 //answerids
		 $answerids= new restore_path_element('lightquiz_answerids',
                                            '/activity/lightquiz/answerids/answerid');
		 $paths[] = $answerids;


        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_lightquiz($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $data->timecreated = $this->apply_date_offset($data->timecreated);


        // insert the lightquiz record
        $newitemid = $DB->insert_record('lightquiz', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

	
	protected function process_lightquiz_attempts($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->timecreated = $this->apply_date_offset($data->timecreated);

		
        $data->lightquizid = $this->get_new_parentid('lightquiz');
        $newitemid = $DB->insert_record('lightquiz_attempt', $data);
       $this->set_mapping('lightquiz_attempt', $oldid, $newitemid, false); // Mapping without files
    }
	
	protected function process_lightquiz_answerids($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->timecreated = $this->apply_date_offset($data->timecreated);


        $data->lightquizid = $this->get_new_parentid('lightquiz');
		//$data->attemptid = $this->get_new_parentid('lightquiz_attempt');
		$data->attemptid = $this->get_mappingid('lightquiz_attempt',$data->attemptid);
        $newitemid = $DB->insert_record('lightquiz_phs', $data);
       $this->set_mapping('lightquiz_phs', $oldid, $newitemid); // Mapping without files
    }
	
    protected function after_execute() {
        // Add lightquiz related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_lightquiz', 'intro', null);
    }
}
