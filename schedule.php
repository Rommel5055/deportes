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
 *
*
* @package    local
* @subpackage deportes
* @copyright  2017	Mark Michaelsen (mmichaelsen678@gmail.com)
* @copyright  2017	Javier Gonzalez (javiergonzalez@alumnos.uai.cl)
* @copyright  2017  Jorge Cabané (jcabane@alumnos.uai.cl) 
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

require_once(dirname(dirname(dirname(__FILE__))) . "/config.php");
require_once($CFG->dirroot."/local/deportes/locallib.php");
require_once ($CFG->libdir . '/tablelib.php');
global $CFG, $DB, $OUTPUT, $PAGE;

// User must be logged in.
require_login();
if (isguestuser()) {
	die();
}

$context = context_system::instance();

$url = new moodle_url("/local/deportes/schedule.php");
$PAGE->navbar->add(get_string("nav_title", "local_deportes"));
$PAGE->navbar->add(get_string("schedule", "local_deportes"), $url);
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout("standard");
$PAGE->set_title(get_string("page_title", "local_deportes"));
$PAGE->set_heading(get_string("page_heading", "local_deportes"));
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin ( 'ui' );
$PAGE->requires->jquery_plugin ( 'ui-css' );

echo $OUTPUT->header();
echo $OUTPUT->heading("DeportesUAI");
echo $OUTPUT->tabtree(deportes_tabs(), "schedule");

$table = new flexible_table("Sports");
$table->define_baseurl($url);
$table->define_headers(array(
		"Hora",
		"Lunes",
		"Martes",
		"Miercoles",
		"Jueves",
		"Viernes"
));
$table->define_columns(array(
		"module",
		"lunes",
		"martes",
		"miercoles",
		"jueves",
		"viernes"
));
$table->sortable(true, "module");
$table->no_sorting("lunes");
$table->no_sorting("martes");
$table->no_sorting("miercoles");
$table->no_sorting("jueves");
$table->no_sorting("viernes");
$table->pageable(true);
$table->setup();
$orderby = "ORDER BY module";
if ($table->get_sql_sort()){
	$orderby = 'ORDER BY '. $table->get_sql_sort();
}
$query = " SELECT id, 
		name,
		day,
		module
		FROM {sports}
		WHERE type = 1
		$orderby";
$nofsports = count($DB->get_records_sql($query, array("")));
$getschedule = $DB->get_records_sql($query, array(""));
$i=0;
$module;
$array = array();
$modulearray = array("","","","","","");
for ($i = 0; $i < $nofsports; $i++){
	$module = array_values($getschedule)[$i]->module;
	$modulearray[0] = $module;
	if ($modulearray[array_values($getschedule)[$i]->day] != ""){
		$temporaryarray = array();
		var_dump($temporaryarray);
		$temporaryarray[] = $modulearray[array_values($getschedule)[$i]->day];
		$temporaryarray[count($modulearray[array_values($getschedule)[$i]->day])] = array_values($getschedule)[$i]->name;
		var_dump($temporaryarray);
		$modulearray[array_values($getschedule)[$i]->day] = $temporaryarray;
	}
	else {
		$modulearray[array_values($getschedule)[$i]->day] = array_values($getschedule)[$i]->name;
	}
	if ($i+1 == $nofsports){
		$array[count($array)] = $modulearray;
	}
	else if (array_values($getschedule)[$i+1]->module != $module){
		$array[count($array)] = $modulearray;
		$modulearray = array("","","","","","");
	}
}
var_dump($array);
foreach($array as $modulararray){
	$table->add_data(array(
			"<span>".$modulararray[0]."</span>",
			"<span class='deporte'>".$modulararray[1]."</span>",
			"<span class='deporte'>".$modulararray[2]."</span>",
			"<span class='deporte'>".$modulararray[3]."</span>",
			"<span class='deporte'>".$modulararray[4]."</span>",
			"<span class='deporte'>".$modulararray[5]."</span>"
	));
}
echo "<html>";
echo "<head>Horario Fitness</head>";
echo "<body>";
echo "<div id='fitness'>";
if ($nofsports>0){
	$table->finish_html();
}
else{
	print "Table is empty";
}
echo "</div>";
echo "<form action='' id='papa'>";
echo "<input type = 'checkbox' name = 'fitness' value = 'Body Pump'>Body Pump <br>";
echo "<input type = 'checkbox' name = 'fitness' value = 'Body Attack'>Body Attack <br>";
echo "<input type = 'checkbox' name = 'fitness' value = 'Fitball'>Fitball <br>";
echo "<input type = 'checkbox' name = 'fitness' value = 'Baile Entretenido'>Baile Entretenido <br>";
echo "<input type = 'checkbox' name = 'fitness' value = 'RPM'>RPM <br>";
echo "<input type = 'checkbox' name = 'fitness' value = 'Yoga'>Yoga <br>";
echo "<input type = 'checkbox' name = 'fitness' value = 'Dance Pad'>Dance Pad <br>";
echo "<input type = 'checkbox' name = 'fitness' value = 'Body Combat'>Body Combat <br>";
echo "<input type = 'checkbox' name = 'fitness' value = 'Body Step'>Body Step <br>";
echo "<input type = 'checkbox' name = 'fitness' value = 'Power Jump'>Power Jump <br>";
echo "<input type = 'checkbox' name = 'fitness' value = 'Body Balance'>Body Balance <br>";
echo "</form>";
echo "<button type = 'submit' form = 'fitness' value = 'Submit'>Submit</button>";
echo "</body>";

echo $OUTPUT->footer();



?>

<script>



$(':checkbox').change(function() {

    var td =$("span[class='deporte']");
	td.hide();
	
	$.each($(':checkbox'), function( index, value ) {
		var valor = $(this).val();
        if (this.checked) {
			$.each(td, function( index, value ) {
  				if($(this).text() === valor ){
					$(this).show();
                } 
			});	
        }
	});
});


</script>

