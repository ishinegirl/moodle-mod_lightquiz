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
 * lightquiz module admin settings and defaults
 *
 * @package    mod
 * @subpackage lightquiz
 * @copyright  2014 Justin Hunt poodllsupport@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {



	  $settings->add(new admin_setting_configtext('lightquiz/consumerkey',
        get_string('consumerkey', 'lightquiz'), get_string('consumerkeyexplain', 'lightquiz'), 'YOUR CONSUMER KEY', PARAM_TEXT));
		
	 $settings->add(new admin_setting_configtext('lightquiz/consumersecret',
        get_string('consumersecret', 'lightquiz'), get_string('consumersecretexplain', 'lightquiz'), 'YOUR CONSUMER SECRET', PARAM_TEXT));

	$settings->add(new admin_setting_configcheckbox('lightquiz/field006', get_string('field006', 'lightquiz'), '', 1));
	$settings->add(new admin_setting_heading('lightquiz/defaultsettings', get_string('defaultsettings', 'lightquiz'), ''));
	$settings->add(new admin_setting_configcheckbox('lightquiz/field001', get_string('field001', 'lightquiz'), '', 1));
	$settings->add(new admin_setting_configcheckbox('lightquiz/field002', get_string('field002', 'lightquiz'), '', 1));
	$settings->add(new admin_setting_configcheckbox('lightquiz/field003', get_string('field003', 'lightquiz'), '', 0));
	$settings->add(new admin_setting_configcheckbox('lightquiz/field007', get_string('field007', 'lightquiz'), '', 0));
	$settings->add(new admin_setting_configcheckbox('lightquiz/field005', get_string('field005', 'lightquiz'), '', 0));
	$settings->add(new admin_setting_configcheckbox('lightquiz/field004', get_string('field004', 'lightquiz'), '', 0));

}
