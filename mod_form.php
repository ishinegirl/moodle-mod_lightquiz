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
 * The main lightquiz configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_lightquiz
 * @copyright  2014 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/lightquiz/lib.php');

/**
 * Module instance settings form
 */
class mod_lightquiz_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;
		
		//just for now
		/*
		$config = new stdClass();
		$config->field001=1;
		$config->field002=1;
		$config->field005=0;
		$config->field007=0;
		$config->field003=1;
		$config->field006=0;
		$config->field004=0;
		*/
		
		$config= get_config('lightquiz');

        //-------------------------------------------------------------------------------
        // Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('lightquizname', 'lightquiz'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'lightquizname', 'lightquiz');

        // Adding the standard "intro" and "introformat" fields
        if($CFG->version < 2015051100){
        	$this->add_intro_editor();
        }else{
        	$this->standard_intro_elements();
		}

        //-------------------------------------------------------------------------------
        // Adding the rest of lightquiz settings, spreeading all them into this fieldset
        // or adding more fieldsets ('header' elements) if needed for better logic
        $mform->addElement('text', 'mediatitle', get_string('mediatitle', 'lightquiz'), array('size'=>'64'));
        $mform->addElement('text', 'mediaid', get_string('mediaid', 'lightquiz'), array('size'=>'24'));
        $mform->addRule('mediatitle', null, 'required', null, 'client');
        $mform->addRule('mediaid', null, 'required', null, 'client');
        $mform->setType('mediatitle', PARAM_TEXT);
        $mform->setType('mediaid', PARAM_INT);
        
        //player options
        $mform->addElement('advcheckbox', 'field006', get_string('field006', 'lightquiz'));
        $mform->setDefault('field006', $config->field006);
        
        $mform->addElement('advcheckbox', 'field007', get_string('field007', 'lightquiz'));
        $mform->setDefault('field007', $config->field007);
        $mform->addElement('advcheckbox', 'field001', get_string('field001', 'lightquiz'));
        $mform->setDefault('field001', $config->field001);
        $mform->addElement('advcheckbox', 'field002', get_string('field002', 'lightquiz'));
        $mform->setDefault('field002', $config->field002);
        $mform->addElement('advcheckbox', 'field005', get_string('field005', 'lightquiz'));
        $mform->setDefault('field005', $config->field005);
        $mform->addElement('advcheckbox', 'field003', get_string('field003', 'lightquiz'));
        $mform->setDefault('field003', $config->field003);
        $mform->addElement('advcheckbox', 'field004', get_string('field004', 'lightquiz'));
        $mform->setDefault('field004', $config->field004);
       
   
        // Grade.
        $this->standard_grading_coursemodule_elements();
		
        //attempts
        $attemptoptions = array(0 => get_string('unlimited', 'lightquiz'),
                            1 => '1',2 => '2',3 => '3',4 => '4',5 => '5',);
        $mform->addElement('select', 'maxattempts', get_string('maxattempts', 'lightquiz'), $attemptoptions);
        
        //grade options
        $gradeoptions = array(MOD_LIGHTQUIZ_GRADEHIGHEST => get_string('gradehighest', 'lightquiz'),
                            MOD_LIGHTQUIZ_GRADELOWEST => get_string('gradelowest', 'lightquiz'),
                            MOD_LIGHTQUIZ_GRADELATEST => get_string('gradelatest', 'lightquiz'),
                            MOD_LIGHTQUIZ_GRADEAVERAGE => get_string('gradeaverage', 'lightquiz'),
							MOD_LIGHTQUIZ_GRADENONE => get_string('gradenone', 'lightquiz'));
        $mform->addElement('select', 'gradeoptions', get_string('gradeoptions', 'lightquiz'), $gradeoptions);
        

        //-------------------------------------------------------------------------------
        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();
        //-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();
    }
}
