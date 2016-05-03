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


defined('MOODLE_INTERNAL') || die();


/**
 * A custom renderer class that extends the plugin_renderer_base.
 *
 * @package mod_lightquiz
 * @copyright COPYRIGHTNOTICE
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_lightquiz_renderer extends plugin_renderer_base {

     /**
     * Returns the header for the lightquiz module
     *
     * @param lesson $lightquiz a lightquiz Object.
     * @param string $currenttab current tab that is shown.
     * @param int    $question id of the question that needs to be displayed.
     * @param string $extrapagetitle String to append to the page title.
     * @return string
     */
    public function header($lightquiz, $cm, $currenttab = 'view', $questionid = null, $extrapagetitle = null) {
        global $CFG;

        $activityname = format_string($lightquiz->name, true, $lightquiz->course);
        if (empty($extrapagetitle)) {
            $title = $this->page->course->shortname.": ".$activityname;
        } else {
            $title = $this->page->course->shortname.": ".$activityname.": ".$extrapagetitle;
        }

        // Build the buttons
        $context = context_module::instance($cm->id);

    /// Header setup
        $this->page->set_title($title);
        $this->page->set_heading($this->page->course->fullname);
       // lesson_add_header_buttons($cm, $context, $extraeditbuttons, $lessonpageid);
        $output = $this->output->header();

        if (has_capability('mod/lightquiz:manage', $context)) {
            $output .= $this->output->heading_with_help($activityname, 'overview', 'lightquiz');

            if (!empty($currenttab)) {
                ob_start();
                include($CFG->dirroot.'/mod/lightquiz/tabs.php');
                $output .= ob_get_contents();
                ob_end_clean();
            }
        } else {
            $output .= $this->output->heading($activityname);
        }


        return $output;
    }

    
    /**
     * Return HTML to display limited header
     */
      public function notabsheader(){
      	return $this->output->header();
      }

	 /**
     * Show the introduction as entered on edit page
     */
	public function show_intro($lightquiz,$cm){
		$ret = "";
		if (trim(strip_tags($lightquiz->intro))) {
			$ret.= $this->output->box_start('mod_introbox');
			$ret.=  format_module_intro('lightquiz', $lightquiz, $cm->id);
			$ret.=  $this->output->box_end();
		}
		return $ret;
	}
	
	/**
     * Show a message to state that the maximum number of attempts has been exceeded
     */
	public function show_exceededattempts($lightquiz,$attempts){
		$ret= $this->output->box_start('mod_lightquiz_exceededattempts');
		$ret.= $this->output->heading(get_string('exceededattempts','lightquiz',$lightquiz->maxattempts));
		$ret.=  $this->output->box_end();
		return $ret;
	}
	
	 /**
     * Show all the attempts on this EC for logged in user(for arrival page)
     */
	public function show_myattempts($lightquiz, $attempts){
		global $CFG;
		if(empty($attempts)){
			return '';
		}
		
		//set up our table and head attributes
		$tableattributes = array('class'=>'generaltable lightquiz_table lightquiz_myattempts_table');
		$headrow_attributes = array('class'=>'lightquiz_myattempts_headrow');
		
		$htmltable = new html_table();
		$htmltable->attributes = $tableattributes;
		

		$htr = new html_table_row();
		$htr->attributes = $headrow_attributes;
		$h_date = new html_table_cell(get_string('date','lightquiz'));
		$htr->cells[] = $h_date;
		$h_activetime = new html_table_cell(get_string('activetime','lightquiz'));
		$htr->cells[] = $h_activetime;
		$h_completed = new html_table_cell(get_string('completed','lightquiz'));
		$htr->cells[] = $h_completed;
		$h_score = new html_table_cell(get_string('sessionscore','lightquiz'));
		$htr->cells[] = $h_score;
		$h_grade = new html_table_cell(get_string('sessiongrade','lightquiz'));
		$htr->cells[] = $h_grade;
		$h_compositescore = new html_table_cell(get_string('compositescore','lightquiz'));
		$htr->cells[] = $h_compositescore;
		$htmltable->data[]=$htr;

		
		foreach($attempts as $attempt){
			$htr = new html_table_row();
			//set up descrption cell
			$cells = array();
			//time created
			$date = new html_table_cell(date("Y-m-d H:i:s",$attempt->timecreated));
			$htr->cells[] = $date;
			//active time
			$activetime = new html_table_cell(gmdate("H:i:s",$attempt->activetime));
			$htr->cells[] = $activetime;
			//completed
			$completionrate = $attempt->data007 ? 1 : 0;
			//this won't work in field005 because data001 is for watchable, not recordable
			if( !$lightquiz->field005 && $attempt->data005 > 0){
				$completionrate = $attempt->data005 / $attempt->data001;
			}
			$completed = new html_table_cell($completionrate ? get_string('yes') : get_string('no'));
			//$completed = $attempt->data005 . '/' . $attempt->data001;
			$htr->cells[] = $completed;
			//Score
			$score = new html_table_cell($attempt->sessionscore);
			$htr->cells[] = $score;
			//Grade
			$grade = new html_table_cell($attempt->sessiongrade);
			$htr->cells[] = $grade;
			//Composite Score
			$compositescore = new html_table_cell(round($completionrate*$attempt->sessionscore,0) .'%');
			$htr->cells[] = $compositescore;
			$htmltable->data[]=$htr;
		}
		$html = $this->output->heading(get_string('myattempts','lightquiz'), 4);
		$html .= html_writer::table($htmltable);
		return $html;
		
	}
	
	/**
     * Show the start/finish button on arrival page
     */
	  public function show_bigbutton($hasattempts) {
		if($hasattempts){
			$caption=get_string('reattempt','lightquiz');
		}else{
			$caption=get_string('start','lightquiz');
		}
		$bigbuttonhtml = html_writer::tag('button',$caption,  
				array('type'=>'button','class'=>'mod_lightquiz_bigbutton yui3-button mod_lightquiz_startfinish_button',
				'id'=>'mod_lightquiz_startfinish_button','onclick'=>'M.mod_lightquiz.playerhelper.startfinish()'));	
		return html_writer::tag('div', $bigbuttonhtml, array('class'=>'mod_lightquiz_bigbutton_start_container','id'=>'mod_lightquiz_bigbutton_start_container'));
				
	 }
	
	/**
     * OLD Show links into API
     */
    public function show_ec_options() {
		$bigbuttonhtml = html_writer::tag('button','@@CAPTION@@',  
				array('class'=>'mod_lightquiz_bigbutton yui3-button yui3-button-disabled mod_lightquiz_@@SIZECLASS@@_button',
				'id'=>'mod_lightquiz_@@ID@@_button','onclick'=>'M.mod_lightquiz.helper.@@ONCLICK@@'));	
				break;
	
    	$results_callback = 'M.mod_lightquiz.playerhelper.showresponse()';
    	$links = array(
    	'Get Status'=>'EC.getStatus(' . $results_callback . ')',
    	'Play'=>'M.mod_lightquiz.playerhelper.play()',
    	'Get Results'=>'EC.getResults(' . $results_callback . ')',
    	'Get Phonemes Count'=>'EC.getPhonemesCount(' . $results_callback . ')',
    	'Log In'=>'M.mod_lightquiz.playerhelper.login()',
    	'Log Out'=>'M.mod_lightquiz.playerhelper.logout()'
    	);
		
		$ret = "";
		foreach($links as $title=>$action){
			$ret .= html_writer::link('#', $title,array('class'=>'mod_english-link','onclick'=>$action)) . '<br />';
		}
        return $ret;
    }
	
	
	/**
     *  Show the Divs holding player and results box
     */
    public function show_ec_box(){
		$playerdiv = html_writer::tag('div','Lightquiz',array('id'=>'mod_lightquiz_playercontainer', 'class'=>'lightquiz_showdiv'));
		$resultsdiv = html_writer::tag('div','',array('id'=>'mod_lightquiz_resultscontainer', 'class'=>'lightquiz_hidediv'));
		return $playerdiv . $resultsdiv;
    }
	
	
	/**
     * OLD Show divs holding player and resuts box
     */
    public function show_ec_link($mediatitle, $thumburl, $mediaid){
    	
    	$itemlabelurl = "EC.play('" . $mediaid . "')";
    	$itemlabel = 'Click to See Player and Have Fun';//;get_string($ratearea . '_' . $rating, 'block_ratings');
		$itemlink = html_writer::link('#', $itemlabel,array('class'=>'mod_english-link','onclick'=>$itemlabelurl));
		
	
		
		$playerdiv = html_writer::tag('div','playerhere',array('id'=>'mod_lightquiz_playercontainer'));
		$resultsdiv = html_writer::tag('div','resultshere',array('id'=>'mod_lightquiz_resultscontainer'));
		return $itemlink . $playerdiv . $resultsdiv;
    }
  
}//end of class

class mod_lightquiz_report_renderer extends plugin_renderer_base {


	public function render_reportmenu($lightquiz,$cm) {
		
		$allusers = new single_button(
			new moodle_url('/mod/lightquiz/reports.php',array('report'=>'allusers','id'=>$cm->id,'n'=>$lightquiz->id)), 
			get_string('allusersreport','mod_lightquiz'), 'get');
		$allattempts = new single_button(
			new moodle_url('/mod/lightquiz/reports.php',array('report'=>'allattempts','id'=>$cm->id,'n'=>$lightquiz->id)), 
			get_string('allattempts','mod_lightquiz'), 'get');
			
		$ret = html_writer::div($this->render($allusers) .'<br />' . $this->render($allattempts) ,'mod_lightquiz_listbuttons');

		return $ret;
	}

	public function render_delete_allattempts($cm){
		$deleteallbutton = new single_button(
				new moodle_url('/mod/lightquiz/manageattempts.php',array('id'=>$cm->id,'action'=>'confirmdeleteall')), 
				get_string('deleteallattempts','lightquiz'), 'get');
		$ret =  html_writer::div( $this->render($deleteallbutton) ,'mod_lightquiz_actionbuttons');
		return $ret;
	}

	public function render_reporttitle_html($course,$username) {
		$ret = $this->output->heading(format_string($course->fullname),2);
		$ret .= $this->output->heading(get_string('reporttitle','mod_lightquiz',$username),3);
		return $ret;
	}

	public function render_empty_section_html($sectiontitle) {
		global $CFG;
		return $this->output->heading(get_string('nodataavailable','mod_lightquiz'),3);
	}
	
	public function render_exportbuttons_html($cm,$formdata,$showreport){
		//convert formdata to array
		$formdata = (array) $formdata;
		$formdata['id']=$cm->id;
		$formdata['report']=$showreport;
		/*
		$formdata['format']='pdf';
		$pdf = new single_button(
			new moodle_url('/mod/lightquiz/reports.php',$formdata),
			get_string('exportpdf','lightquiz'), 'get');
		*/
		$formdata['format']='csv';
		$excel = new single_button(
			new moodle_url('/mod/lightquiz/reports.php',$formdata), 
			get_string('exportexcel','lightquiz'), 'get');

		return html_writer::div( $this->render($excel),'mod_lightquiz_actionbuttons');
	}
	

	
	public function render_section_csv($sectiontitle, $report, $head, $rows, $fields) {

        // Use the sectiontitle as the file name. Clean it and change any non-filename characters to '_'.
        $name = clean_param($sectiontitle, PARAM_FILE);
        $name = preg_replace("/[^A-Z0-9]+/i", "_", trim($name));
		$quote = '"';
		$delim= ",";//"\t";
		$newline = "\r\n";

		header("Content-Disposition: attachment; filename=$name.csv");
		header("Content-Type: text/comma-separated-values");

		//echo header
		$heading="";	
		foreach($head as $headfield){
			$heading .= $quote . $headfield . $quote . $delim ;
		}
		echo $heading. $newline;
		
		//echo data rows
        foreach ($rows as $row) {
			$datarow = "";
			foreach($fields as $field){
				$datarow .= $quote . $row->{$field} . $quote . $delim ;
			}
			 echo $datarow . $newline;
		}
        exit();
        break;
	}

	public function render_section_html($sectiontitle, $report, $head, $rows, $fields) {
		global $CFG;
		if(empty($rows)){
			return $this->render_empty_section_html($sectiontitle);
		}
		
		//set up our table and head attributes
		$tableattributes = array('class'=>'generaltable lightquiz_table');
		$headrow_attributes = array('class'=>'lightquiz_headrow');
		
		$htmltable = new html_table();
		$htmltable->attributes = $tableattributes;
		
		
		$htr = new html_table_row();
		$htr->attributes = $headrow_attributes;
		foreach($head as $headcell){
			$htr->cells[]=new html_table_cell($headcell);
		}
		$htmltable->data[]=$htr;
		
		foreach($rows as $row){
			$htr = new html_table_row();
			//set up descrption cell
			$cells = array();
			foreach($fields as $field){
				$cell = new html_table_cell($row->{$field});
				$cell->attributes= array('class'=>'lightquiz_cell_' . $report . '_' . $field);
				$htr->cells[] = $cell;
			}

			$htmltable->data[]=$htr;
		}
		$html = $this->output->heading($sectiontitle, 4);
		$html .= html_writer::table($htmltable);
		return $html;
		
	}
	
	function show_reports_footer($lightquiz,$cm,$formdata,$showreport){
		// print's a popup link to your custom page
		$link = new moodle_url('/mod/lightquiz/reports.php',array('report'=>'menu','id'=>$cm->id,'n'=>$lightquiz->id));
		$ret =  html_writer::link($link, get_string('returntoreports','mod_lightquiz'));
		$ret .= $this->render_exportbuttons_html($cm,$formdata,$showreport);
		return $ret;
	}

}//end of class

