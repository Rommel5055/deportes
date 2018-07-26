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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.


/**
 * @package    local
 * @subpackage deportes
 * @copyright  2017	Mark Michaelsen (mmichaelsen678@gmail.com)
 * @copyright  2018	Javier Gonzalez (jgonzalez.vargas@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function deportes_tabs() {
	$tabs = array();
	// Create sync
	$tabs[] = new tabobject(
			"attendance",
			new moodle_url("/local/deportes/attendance.php"),
			get_string("attendance", "local_deportes")
			);
	// Records.
	$tabs[] = new tabobject(
			"schedule",
			new moodle_url("/local/deportes/schedule.php"),
			get_string("schedule", "local_deportes")
			);
	// History
	$tabs[] = new tabobject(
			"reserve",
			new moodle_url("/local/deportes/reserve.php"),
			get_string("reserve", "local_deportes")
			);
	return $tabs;
}

function deportes_modal_rules($startmonth, $startday, $endmonth, $endday, $totalattendance) {
	global $CFG;
	return '<div id="myModal" class="modal fade" role="dialog" style="width: 70%; margin-left: -35%; z-index: -10;">
  				<div class="modal-dialog">
				    <div class="modal-content">
      					<div class="modal-header">
        					<button type="button" class="close" data-dismiss="modal">&times;</button>
        					<h4 class="modal-title">'.get_string("rules_title", "local_deportes").'</h4>
      					</div>
      					<div class="modal-body">
        					<p>'.get_string("rules_content1", "local_deportes").$totalattendance.get_string("rules_b", "local_deportes").get_string(date('M', mktime(0, 0, 0, $startmonth, 10)), "local_deportes").' '.$startday.
        					get_string("rules_content2", "local_deportes").get_string(date('M', mktime(0, 0, 0, $endmonth, 10)), "local_deportes").' '.$endday.
        					get_string("rules_content3", "local_deportes").'</p>
      					</div>
      					<div class="modal-footer">
	        				<button type="button" class="btn btn-default" data-dismiss="modal">'.get_string("close", "local_deportes").'</button>
    	  				</div>
    				</div>
  				</div>
			</div>';
}

function deportes_modal_help() {
	return '<div id="helpModal" class="modal fade" role="dialog" style="width: 70%; margin-left: -35%; z-index: -10;">
  				<div class="modal-dialog">
				    <div class="modal-content">
      					<div class="modal-header">
        					<button type="button" class="close" data-dismiss="modal">&times;</button>
        					<h4 class="modal-title">'.get_string("graphinfo", "local_deportes").'</h4>
      					</div>
      					<div class="modal-body">
        					<p>'.get_string("graphinfo_content", "local_deportes").'</p>
      					</div>
      					<div class="modal-footer">
	        				<button type="button" class="btn btn-default" data-dismiss="modal">'.get_string("close", "local_deportes").'</button>
    	  				</div>
    				</div>
  				</div>
			</div>';
}

function replace_extension($filename, $new_extension) {
	$info = pathinfo($filename);
	return $info['filename'] . '.' . $new_extension;
}
/*
function deportes_get_schedule($orderby, $type){
	global $DB;
	
	$query = "SELECT s.id,
	c.name,
	c.backgroundcolor,
	s.day,
	CONCAT(m.starttime,' - ',m.endtime) AS starttime 
	FROM {sports_classes} as c
	INNER JOIN {sports_schedule} AS s ON (c.id = s.idsports)
	INNER JOIN {sports_modules} AS m ON (s.idmodules = m.id)
	WHERE m.type = ?
	$orderby, s.day, c.name
	";
	$getschedule = $DB->get_records_sql($query, array($type));
	return $getschedule;
}
function deportes_arraymakingiftherearestillsportsleft($auxiliaryarrayn1){
	$newmodulearray = array("","","","","","");
	$actualday = 1;
	foreach ($auxiliaryarrayn1 as $key => $auxarray){
		if ($auxarray->day == $actualday){
			$newmodulearray[$actualday] = "<span class = '$auxarray->name'>".$auxarray->name."</span>";
			unset($auxiliaryarrayn1[$key]);
			$actualday++;
		}
		else if ($auxarray->day > $actualday){
			$actualday = $auxarray->day;
			$newmodulearray[$actualday] = "<span class = '$auxarray->name'>".$auxarray->name."</span>";
			unset($auxiliaryarrayn1[$key]);
			$actualday++;
		}
	}
	$array = array(
			0 => $auxiliaryarrayn1,
			1 => $newmodulearray
	);
	return $array;
}
function deportes_arrayforschedule($getschedule, $nofsports){
	$counterofsports=0;
	$array = array();
	$modulearray = array("","","","","","");
	$getschedule = array_values($getschedule);
	foreach ($getschedule as $currentsport){
		//gets the module of the sport in the array. The algorithm will work with each module, one at a time
		$module = $currentsport->starttime;
		$auxiliaryarrayn1 = array();
		$key=0;
		foreach($getschedule as $schedulekey => $schedule){

			if ($schedule->starttime == $module ){
				$auxiliaryarrayn1[$key] = $schedule;
				unset($getschedule[$schedulekey]);
				$key++;
				//it makes an array with all the sports in that module, and it erreases them from the array returned form the DB
			}
		}
		if(!empty($auxiliaryarrayn1)){
			//for some reason it still iterates for each element in the DB returned array, despite having elements erreased. However,
			//they don't enter the previous foreach, so the $auxiliaryarrayn1 comes empty these times
			$modulearrayn1 = array("","","","","",""); //this will later be a row in the schedule. First element is module, 
			//the rest is each day. It will only have one sport per day. If there is more than one for the same module and day
			//then a new array will be created
			$actualday = 1; //since monday is the first day of the week despite the sorting of the schedule, we start with monday
			$modulearrayn1[0] = $module;
			foreach ($auxiliaryarrayn1 as $schedulekey => $auxarray){
				if ($auxarray->day == $actualday){
					//if the current day is the same as the one in the sport, then we add the sport to the array wich will later go into
					//the schedule, and delete it from our auxiliary array for the current module
					$modulearrayn1[$actualday] = "<span class = '$auxarray->name'>".$auxarray->name."</span>";
					unset($auxiliaryarrayn1[$schedulekey]);
					$actualday++; //we inmidiatly go to the next day, despite the possibility of having for sports for this same day. These
					//sports will remain in the array
				}
				else if ($auxarray->day > $actualday){
					//this condition would mean there is a day in the week with no sports, so we skip it. Other than that, it's the same as before
					$actualday = $auxarray->day;
					$modulearrayn1[$actualday] = "<span class = '$auxarray->name'>".$auxarray->name."</span>";
					
					unset($auxiliaryarrayn1[$schedulekey]);
					$actualday++;
				}
			}
		
			$array[count($array)] = $modulearrayn1; //the array created in the loop is added to the array 
													//which will later be added to the schedule
		}
		while(count($auxiliaryarrayn1) > 0){
			//After adding the sport to the modulearray is deleted from the auxiliatyarrayn1 and the next day is checked. So, if the 
			//is not empty then there still are some sports for that module, and they have to be worked
			$newarray = deportes_arraymakingiftherearestillsportsleft($auxiliaryarrayn1);//this function does the same as before, with the difference it
			//keeps the first element of the array empty, as we don't need to have the same module hours in the schedule several times
			//it returns an array of arrays, the element 0 is what's left of auxiliaryarrayn1 and the element 1 is the equivalent of
			//modulearray, which will later be added to the schedule
			$array[count($array)] = $newarray[1];//the equivalent of modulearray is addded to the array wich will later be added to the schedule
			$auxiliaryarrayn1 = $newarray[0];//uodates the auxiliaryarrayn1, so it's checked again and the process repeats if it's not empty yet
		}
	}
	var_dump($array);
	return $array; //this is the array of arrays which will be added to the schedule. each array in the array is a row in the schedule
}
function deportes_getsports($type){
	global $DB;
	$getsports = $DB->get_records('sports_classes', array ("type" => $type));
	$arraywithsports = array();
	$key = 0;
	foreach($getsports as $sports){
		$arraywithsports[$key] = $sports;
		$key++;
	}
	return $arraywithsports;
}
function deportes_getcolorsarray(){
	return array(
				"0" => get_string("selectacolor", "local_deportes"),
				"DC143C" => "Rojo Profundo",
				"FF0000" => "Rojo",
				"8B0000" => "Rojo Oscuro",
				"FF69B4" => "Rosado Calido",
				"FF1493" => "Rosado Profundo",
				"C71585" => "Violeta Medio",
				"DB7093" => "Violeta Rojizo Palido",
				"FF4500" => "Rojo Anaranjado",
				"FF8C00" => "Narajo Oscuro",
				"BDB76B" => "Khaki Oscuro",
				"EE82EE" => "Violeta",
				"FF00FF" => "Magenta",
				"9370DB" => "Purpura Medio",
				"8A2BE2" => "Violeta Azulado",
				"9400D3" => "Violeta Oscuro",
				"8B008B" => "Magenta Oscuro",
				"7B68EE" => "Azul Pizarra Medio",
				"483D8B" => "Azul Pizarra Oscuro",
				"3CB371" => "Verde Mar Medio",
				"228B22" => "Verde Bosque",
				"008000" => "Verde",
				"006400" => "Verde Oscuro",
				"9ACD32" => "Amarillo Verde",
				"6B8E23" => "Oliva Monotono",
				"808000" => "Oliva",
				"556B2F" => "Oliva Oscuro",
				"008B8B" => "Cian Oscuro",
				"48D1CC" => "Turquesa Medio",
				"4682B4" => "Azul Metalico",
				"00BFFF" => "Azul Cielo Profundo",
				"00008B" => "Azul Oscuro",
				"F4A460" => "Cafe Arena",
				"DAA520" => "Dorado",
				"B8860B" => "Dorado Oscuro",
				"696969" => "Gris Oscuro",
				"778899" => "Gris Pizarra Claro",
				"2F4F4F" => "Gris Pizarra Oscuro",
				"000000" => "Negro"
		);
}*/
	
function deportes_minimumpermonth($firstmonth, $lastmonth, $total, $monthlyattendance){
	$minimumpermonth =  array();
	$remaining = $total - array_sum($monthlyattendance);
	$lastm = $lastmonth;
	while($lastm >= $firstmonth){
		if ($remaining > 8){
			$minimumpermonth[$lastm] = 8;
			$remaining -= 8;
		}
		else if($remaining > 0){
			$minimumpermonth[$lastm] = $remaining;
			$remaining = 0;
		}
		else{
			$remaining = 0;
			$minimumpermonth[$lastm] = $remaining;
		}
		$lastm--;
	}
	return $minimumpermonth; 
}

function deportes_setimageurl($imageid, $contextid){
	$fs = get_file_storage();
	
	$files = $fs->get_area_files($contextid, 'local_deportes', 'file', $imageid);
	//var_dump($files);
	//var_dump($imageid);
	if ($files = $fs->get_area_files($contextid, 'local_deportes', 'file', $imageid)) {
		foreach ($files as $file) {
			$fileurl = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());
			//var_dump($fileurl);
			$imageurl = $fileurl->get_port() ? $fileurl->get_scheme() . '://' . $fileurl->get_host() . $fileurl->get_path() . ':' . $fileurl->get_port() : $fileurl->get_scheme() . '://' . $fileurl->get_host() . $fileurl->get_path();
			//var_dump($imageurl);
			return $imageurl;
		}
	}
}

function deportes_uploadschedule($file, $path, $newfile, $contextsystem, $timecreated, $userid){
	global $DB, $OUTPUT, $USER;
	$schedulefile = $path ."/schedule/".$newfile->name;
	
	var_dump($file);
	var_dump($schedulefile);
	
	$originalfilename = $file->get_filename();
	$file->copy_content_to($schedulefile);
		$fs = get_file_storage();

		$file_record = array(
				'contextid' => $contextsystem->id,
				'component' => 'local_deportes',
				'filearea' => 'draft',
				'itemid' => 0,
				'filepath' => '/',
				'filename' => $newfile->name,
				'timecreated' => time(),
				'timemodified' => time(),
				'userid' => $USER->id,
				'author' => $USER->firstname." ".$USER->lastname,
				'license' => 'allrightsreserved'
		);

		// If the file already exists we delete it
		if ($fs->file_exists($contextsystem->id, 'local_deportes', 'draft', 0, '/', $newfile->name)) {
			$previousfile = $fs->get_file($contextsystem->id, 'local_deportes', 'draft', 0, '/', $newfile->name);
			$previousfile->delete();
		}

		// Info for the new file
		$fileinfo = $fs->create_file_from_pathname($file_record, $schedulefile);

		$newfile->uploaddate = $timecreated;
		$newfile->iduser = $userid;
		$DB->insert_record('deportes_files', $newfile);

		return $OUTPUT->notification(get_string("filename", "local_deportes").$originalfilename."<br>".get_string("uploadsuccessful", "local_paperattendance"), "notifysuccess");

}
