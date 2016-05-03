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
 * Defines all the backup steps that will be used by {@link backup_lightquiz_activity_task}
 *
 * @package     mod_lightquiz
 * @category    backup
 * @copyright   2014 Justin Hunt <poodllsupport@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/lightquiz/lib.php');

/**
 * Defines the complete webquest structure for backup, with file and id annotations
 *
 */
class backup_lightquiz_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the structure of the 'lightquiz' element inside the webquest.xml file
     *
     * @return backup_nested_element
     */
    protected function define_structure() {

        // are we including userinfo?
        $userinfo = $this->get_setting_value('userinfo');

        ////////////////////////////////////////////////////////////////////////
        // XML nodes declaration - non-user data
        ////////////////////////////////////////////////////////////////////////

        // root element describing lightquiz instance
        $lightquiz = new backup_nested_element('lightquiz', array('id'), array(
            'course','name','intro','introformat','mediatitle','mediaid','field001','field002',
			'field003','field004','field005','field007','maxattempts','grade',
			'gradeoptions','timecreated','timemodified','field006'
			));
		
		//attempts
        $attempts = new backup_nested_element('attempts');
        $attempt = new backup_nested_element('attempt', array('id'),array(
			"lightquizid","userid","data001","data003","data002","activetime"
			,"data004","data005","data006","points","data007","sessiongrade"
			,"sessionscore","mediaid","status","timecreated"
		));
		
		//phenomes
        $answerids = new backup_nested_element('answerids');
        $answerid = new backup_nested_element('answerid', array('id'),array(
			 "lightquizid","attemptid","userid","answerid","chosenanswer","wascorrect","timecreated" 
		));
		
		// Build the tree.
        $lightquiz->add_child($attempts);
        $attempts->add_child($attempt);
        $lightquiz->add_child($answerids);
        $answerids->add_child($answerid);
		


        // Define sources.
        $lightquiz->set_source_table('lightquiz', array('id' => backup::VAR_ACTIVITYID));

        //sources if including user info
        if ($userinfo) {
			$attempt->set_source_table('lightquiz_attempt',
											array('lightquizid' => backup::VAR_PARENTID));
			$answerid->set_source_table('lightquiz_phs',
											array('lightquizid' => backup::VAR_PARENTID));
        }

        // Define id annotations.
        $attempt->annotate_ids('user', 'userid');
		$answerid->annotate_ids('user', 'userid');


        // Define file annotations.
        // intro file area has 0 itemid.
        $lightquiz->annotate_files('mod_lightquiz', 'intro', null);

        // Return the root element (choice), wrapped into standard activity structure.
        return $this->prepare_activity_structure($lightquiz);
		

    }
}
