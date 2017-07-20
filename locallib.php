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
function deportes_get_schedule($orderby, $type){
	global $DB;
	
	$query = "SELECT s.id,
	c.name,
	s.day,
	CONCAT(m.starttime,' - ',m.endtime) AS starttime 
	FROM {sports_classes} as c
	INNER JOIN {sports_schedule} AS s ON (c.id = s.idsports)
	INNER JOIN {sports_modules} AS m ON (s.idmodules = m.id)
	WHERE m.type = ?
	$orderby, s.day
	";
	$getschedule = $DB->get_records_sql($query, array($type));
	return $getschedule;
}
function deportes_arraymakingiftherearestillsportsleft($auxiliaryarrayn1){
	$newmodulearray = array("","","","","","");
	$actualday = 1;
	foreach ($auxiliaryarrayn1 as $key => $auxarray){
		if ($auxarray->day == $actualday){
			$newmodulearray[$actualday] = $auxarray->name;
			unset($auxiliaryarrayn1[$key]);
			$actualday++;
		}
		else if ($auxarray->day > $actualday){
			$actualday = $auxarray->day;
			$newmodulearray[$actualday] = $auxarray->name;
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
					$modulearrayn1[$actualday] = $auxarray->name;
					unset($auxiliaryarrayn1[$schedulekey]);
					$actualday++; //we inmidiatly go to the next day, despite the possibility of having for sports for this same day. These
					//sports will remain in the array
				}
				else if ($auxarray->day > $actualday){
					//this condition would mean there is a day in the week with no sports, so we skip it. Other than that, it's the same as before
					$actualday = $auxarray->day;
					$modulearrayn1[$actualday] = $auxarray->name;
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
	return $array; //this is the array of arrays which will be added to the schedule. each array in the array is a row in the schedule
}