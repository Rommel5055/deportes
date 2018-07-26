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
/*
* @package    local
* @subpackage deportes
* @copyright  2017 Javier Gonzalez <javiergonzalez@alumnos.uai.cl>
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

defined("MOODLE_INTERNAL") || die();
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . "/config.php");
require_once(dirname(dirname(__FILE__)) . "/locallib.php");
require_once($CFG->libdir . "/formslib.php");

class deportes_filepicker extends moodleform{
	public function definition(){
		global $DB, $CFG;
		$mform = $this->_form;
		$arraysportstype = array(
				0 => get_string("selectsport", "local_deportes"),
				1 => "Outdoors",
				2 => "Fitness"
		);
		$mform->addElement("filepicker", "userfile", get_string("selectfile", "local_deportes"), null, 
				array("maxbytes" => 5000000, "accepted_types" => ["*"]));
		//$mform->addElement('filemanager', 'userfile', get_string("selectfile", "local_deportes"), null,
			//		array("maxbytes" => 5000000, "accepted_types" => ["jpg"]));
		$mform->setType("userfile", PARAM_FILE);
		$path = $CFG -> dataroot. "/temp/local/deportes";
		
		/*$mform->get_new_filename("userfile");
		$mform->setType("filename", PARAM_TEXT);
		*/
		
		$mform->addElement("select", "type", get_string("sport_type", "local_deportes"), $arraysportstype);
		$mform->setType("type", PARAM_INT);
		$mform->addHelpButton("type", "sport_type", "local_deportes");
		
		
		$mform->addElement("hidden", "action", "addfile");
		$mform->setType("action", PARAM_TEXT);
		$this->add_action_buttons(true, get_string("uploadfile", "local_deportes"));
	}
	public function validation($data, $files){
		$errors = array();
		
		$userfile = $data["userfile"];
		$type = $data["type"];
		//$filename = $data["filename"];
		
		if (empty($userfile)){
			$errors["userfile"] = get_string("must_uploadfile", "local_deportes");
		}
		/*
		$explodename = explode(".",$filename);
		$countnamefile= count($explodename);
		$extension = $explodename[$countnamefile-1];
		*/
		
		if($type != 1 && $type != 2){
			$errors["type"] = get_string("must_selecttype");
		}
		return $errors;
	}
}