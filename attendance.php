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
* @copyright  2017	Mihail Pozarski (mpozarski944@gmail.com)
* @copyright  2018	Javier Gonzalez (jgonzalez.vargas@gmail.com)
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

require_once(dirname(dirname(dirname(__FILE__))) . "/config.php");
require_once($CFG->dirroot."/local/deportes/locallib.php");
require_once($CFG->libdir . "/tablelib.php");
global $CFG, $DB, $OUTPUT, $PAGE, $USER;

//$PAGE->requires->js( new moodle_url('https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js') );

// User must be logged in.
require_login();
if (isguestuser()) {
	die();
}


$page = optional_param('page', 0, PARAM_INT);
$perpage = 15;
$email = explode('@',$USER->email);
$context = context_system::instance();

if(($email[1] == $CFG->deportes_emailextension) || is_siteadmin() || has_capability("local/deportes:edit", $context)){
	
	$url = new moodle_url("/local/deportes/attendance.php");
	$PAGE->navbar->add(get_string("nav_title", "local_deportes"));
	$PAGE->navbar->add(get_string("attendance", "local_deportes"), $url);
	$PAGE->set_context($context);
	$PAGE->set_url($url);
	$PAGE->set_pagelayout("standard");
	$PAGE->set_title(get_string("attendance", "local_deportes"));
	$PAGE->set_heading(get_string("page_heading", "local_deportes"));
	
	$curl = curl_init();
	$url = $CFG->deportes_urlasistenciasalumno;
	$token = $CFG->deportes_token;
	$fields = array(
			"token" => $token,
			"email" => $USER->email
	);
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($curl, CURLOPT_POST, TRUE);
	curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($fields));
	curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
	$result = json_decode(curl_exec($curl));
	curl_close($curl);

	$startdate = strtotime($result->asistencias->fechaInicio);
	$lastdate = strtotime($result->asistencias->fechaTermino);
	$startday = date('j', $startdate);
	$endday = date('j', $lastdate);
	$firstmonth = date('n', $startdate);
	$lastmonth = date('n', $lastdate);
	$total = $CFG->deportes_tottalattendance;
	
	$modal = deportes_modal_rules(date('m', $startdate), $startday, date('m', $lastdate), $endday, $total);
	$helpmodal = deportes_modal_help();
	$button = html_writer::nonempty_tag("button", html_writer::tag('h4', get_string("rules","local_deportes")), array( "id"=>"button", "class" => "btn-info", "style" => "float: right;", "data-toggle" => "modal", "data-target" => "#myModal"));
	$helpbutton = html_writer::nonempty_tag("button", get_string("help", "local_deportes"), array("id" => "helpButton", "class" => "btn-info", "data-toggle" => "modal", "data-target" => "#helpModal"));
	
	//pdf reader
	$fs = get_file_storage();
	if ($fs->file_exists($context->id,"local_deportes", "draft", 0, "/", "rules.pdf")) {
	} 
	$rulesurl = moodle_url::make_pluginfile_url($context->id, "local_deportes", "draft", 0, "/", "rules.pdf");
	$viewerpdf = html_writer::nonempty_tag("embed", " ", array(
			"src" => $rulesurl,
			"style" => "height:75vh; width:60vw"
	));
	
	
	
	
	
	if(count($result->asistencias->asistencias)>0){
		$table = new html_table("p");
		
		$table->head = array(
				get_string("number", "local_deportes"),
				get_string("month", "local_deportes"),
				get_string("week", "local_deportes"),
				get_string("date", "local_deportes"),
				get_string("sport", "local_deportes"),
				get_string("t_start", "local_deportes"),
				get_string("t_end", "local_deportes"),
				get_string("attendance", "local_deportes")
		);
		
		$table->size = array(
				"5%",
				"7%",
				"7%",
				"15%",
				"20%",
				"15%",
				"15%",
				"16%"
		);
		
		$data = $result->asistencias->asistencias;
		$attendancechart = array();
		$sports = array();
		$sportschart = array();
		$repeated = 0;
		$totalattendance = $result->asistenciasValidas;
		$monthlyattendance = array(
				1 => 0,
				2 => 0,
				3 => 0,
				4 => 0,
				5 => 0,
				6 => 0,
				7 => 0,
				8 => 0,
				9 => 0,
				10 => 0,
				11 => 0,
				12 => 0,
				13 => 0
		);
		
		$height = "24vh";
		$calendarheight = 220;
		$calendarwidth = 720;
		
		$today = date("m", time());
		$todayday = date("j", time());
		$counter = $page * $perpage + 1;
		$date = 0;
		$lastattendance = 0;
		
		$months = array();
		for($monthnumber = $firstmonth; $monthnumber <= $lastmonth; $monthnumber++) {
			$months[] = $monthnumber;
		}
		
		foreach($data as $attendance) {
			//$attendancechartinfo = array();
			
				$date = date('Y-m-d',strtotime($attendance->HoraInicio . ' +1 day'));
				$lastattendance = $attendance->Asistencia;
				$attendancechartinfo = array(
						$date,
						$attendance->Asistencia
				);
				
				$month = date("n",strtotime($attendance->HoraInicio));
				$day = date("j",strtotime($attendance->HoraInicio));

				if((($month > $firstmonth) || ($month == $firstmonth && $day >= $startday))
						&&(($month < $lastmonth) || ($month == $lastmonth && $day <= $endday))){
					$monthlyattendance[(int)$month] += $attendance->Asistencia;
				}
				if (end($attendancechart)[0] == $date){
					if(end($attendancechart)[1] + $attendance->Asistencia > 1){
						$attendancechart[sizeof($attendancechart)-1][1] = 1;
					}else if(end($attendancechart)[1] + $attendance->Asistencia < -1){
						$attendancechart[sizeof($attendancechart)-1][1] = -1;
					}else{
						$attendancechart[sizeof($attendancechart)-1][1] = end($attendancechart)[1] + $attendance->Asistencia;
						
					}
				}
				else{
					$attendancechart[] = $attendancechartinfo;
				}
			
			$repeated = 0;
			
			if($attendance->Asistencia > 0) { 
				if(isset($sports[$attendance->Deporte])) {
					$sports[$attendance->Deporte] += $attendance->Asistencia;
				}
				else {
					$sports[$attendance->Deporte] = $attendance->Asistencia;
				}
			}
			
			$attendanceinfo = array(
					$counter,
					get_string(date('M', mktime(0, 0, 0, $attendance->Mes, 10)), "local_deportes"),
					$attendance->Semana,
					date("d-m-Y",strtotime($attendance->HoraInicio)),
					$attendance->Deporte,
					date("H:i",strtotime($attendance->HoraInicio)),
					date("H:i",strtotime($attendance->HoraTermino)),
					$attendance->Asistencia
			);
			$counter++;
			
			$table->data[] = $attendanceinfo;		
		}
		
		$actualmonth = (int)$today;
		$actualmonth = ($actualmonth == 1) ? $actualmonth + 12 : $actualmonth;
		$elapsedmonths = $actualmonth - $firstmonth;
		$monthsremaining = ($lastmonth - $firstmonth + 1) - $elapsedmonths;
		$minimumpermonth = array();
		
		/*
		// Fills the minimumpermonth array with the attendance already done
		$completedmonth = 0;
		$elapsedmonths = ($elapsedmonths < 0) ? $elapsedmonths + 12 : $elapsedmonths;
		while($elapsedmonths > 0 && $firstmonth + $completedmonth <= $lastmonth) {
			$minimumpermonth[$firstmonth + $completedmonth] = $monthlyattendance[$firstmonth + $completedmonth];
			$elapsedmonths--;
			$completedmonth++;
		}
		 */
		$minimumpermonth = deportes_minimumpermonth($firstmonth, $lastmonth, $total, $monthlyattendance);
		
		// Fills the remaining spaces in the array and checks if the student failed sports by lack of attendance
		$counter = 0;
		$failed = false;
		if(($today >= $lastmonth && $todayday > $endday) && $totalattendance < $total) {
			$failed = true;
		}
		/*
		var_dump("jejeje");
		while(array_sum($minimumpermonth) < $total || ($monthsremaining) >= $counter) {
			if($totalattendance >= $total) {
				break;
			}
			var_dump("jajjaa");
			var_dump($minimumpermonth);
			if((!isset($minimumpermonth[$lastmonth - $counter]) || array_sum($minimumpermonth) < $total)) {
				var_dump(!isset($minimumpermonth[$lastmonth - $counter]));
				
				var_dump($lastmonth);
				var_dump($counter);
				$difference = max($total - array_sum($minimumpermonth), 0) + $minimumpermonth[$lastmonth - $counter];
				$minimumpermonth[$lastmonth - $counter] = ($difference > 8) ? 8 : $difference;
			}
				
			$counter++;
		}
		*/
		
		$minimumrequired = (isset($minimumpermonth[$actualmonth])) ? $minimumpermonth[$actualmonth] : 0;
		$monthlycolor = ($monthlyattendance[$actualmonth] > $minimumrequired) ? "#00cc00" : "#e62e00";
		
		foreach($sports as $sportname => $quantity) {
			$sportschart[] = array(
					$sportname,
					$quantity
			);
		}
		
		$situation = ($totalattendance >= $total) ? get_string("passed", "local_deportes") : ($failed ? get_string("failed", "local_deportes") : get_string("pending", "local_deportes"));
		$color = ($failed) ? "red" : "orange";
		$color = ($totalattendance >= $total) ? "#00cc00" : $color;
		
		$headingtable = new html_table("p");
		$headingtable->data[] = array(
				html_writer::tag('h3', get_string('totalattendance','local_deportes').": ".$totalattendance."/".$total),
				html_writer::tag('h3', get_string('situation','local_deportes').": ".$situation, array('style' => 'color:'.$color)),
				html_writer::tag('h3', get_string('monthattendance','local_deportes').": ".$monthlyattendance[$actualmonth], array('style' => 'color:'.$monthlycolor)),
				html_writer::tag('h3', get_string('minimumattendance','local_deportes').": ".$minimumrequired)
				
		);
		
		$monthlytable = new html_table("p");
		$monthlytablearray = array();
		
		for($month = $firstmonth; $month <= $lastmonth; $month++) {
			$recommended = ceil($total / ($lastmonth - $month + 1));
			$total -= $recommended;
			
			$monthlycolor = ($monthlyattendance[$month] >= $minimumpermonth[$month]) ? "#00cc00" : "#e62e00";
			$monthlycolor = ($month >= $actualmonth && $actualmonth >= $firstmonth) ? "orange" : $monthlycolor ;
				
			$monthlytablearray[] = html_writer::tag('h3', get_string(date('M', mktime(0, 0, 0, $month, 10)), "local_deportes").": ".$monthlyattendance[$month], array('style' => 'color:'.$monthlycolor)).
					html_writer::tag('b', get_string("recommended", "local_deportes").": ".$recommended);
		}
		$monthlytable->data[] = $monthlytablearray;
		
	}
	echo $OUTPUT->header();
	echo $OUTPUT->heading("DeportesUAI");
	
	echo $OUTPUT->tabtree(deportes_tabs(), "attendance");
	
	echo html_writer::div($button, "topbarmenu");
	
	if(!(count($result->asistencias->asistencias)>0)){
		echo html_writer::div(get_string("noattendance","local_deportes"),"alert alert-info", array("role"=>"alert"));
	}else{
		echo html_writer::table($headingtable);
		
		echo html_writer::tag('div','', array('id' => 'calendar_basic', 'style' => "overflow-x: hidden; overflow-y: hidden; height:$calendarheight; width: $calendarwidth"));
		echo html_writer::div($helpbutton, "topbarmenu");
		
		echo html_writer::table($monthlytable);// attr position fix o overflow(x,y) hidden
		
		echo html_writer::tag('div','', array('id' => 'sports_chart'));
		
		echo html_writer::table($table);
	}
	echo $OUTPUT->footer();
	
	echo html_writer::div($modal, "modaldiv");
	echo html_writer::div($helpmodal, "modaldiv");
	?>
	<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
	<script type="text/javascript">
	google.charts.load("current", {packages:["calendar", "corechart", "bar"], language: "es-cl"});
	google.charts.setOnLoadCallback(drawChart);
	google.charts.setOnLoadCallback(drawMaterial);
	
	function drawChart() {
	
		var dataarray = <?php echo  json_encode($attendancechart);?>;
		var arraylength = dataarray.length;
		var startdate = 0;
		
		for (var i = 0; i < arraylength; i++) {
			startdate = dataarray[i][0]
			dataarray[i][0] = new Date(startdate);
		}
		
		var dataTable = new google.visualization.DataTable();
		dataTable.addColumn({ type: 'date', id: 'Date' });
		dataTable.addColumn({ type: 'number', id: 'Won/Loss' });
		dataTable.addRows(dataarray);
		
		var chart = new google.visualization.Calendar(document.getElementById('calendar_basic'));
		
		var options = {
			title: "<?php echo get_string('attendance','local_deportes')?>",
			width: '920',
			height: "<?php echo $calendarheight; ?>",
			colorAxis:{
				minValue:-1,
				maxValue:1
			},
			calendar: {
			      dayOfWeekRightSpace: 10,
			      daysOfWeek: '<?php echo get_string('calendarchartweek', 'local_deportes');?>',
			    }
		};
		
		chart.draw(dataTable, options);
	}

	function drawMaterial() {
		var sportsData = <?php echo json_encode($sportschart); ?>
		
      	var data = new google.visualization.DataTable();
      	data.addColumn('string', "<?php echo get_string('sport', 'local_deportes'); ?>");
      	data.addColumn('number', "<?php echo get_string('attendance', 'local_deportes'); ?>");

		data.addRows(sportsData);
		
		var options = {
				height: '300',
				backgroundColor: 'transparent',
		        chart: {
		          title: "<?php echo get_string('sportschart_title', 'local_deportes'); ?>",
		          subtitle: "<?php echo get_string('sportschart_subtitle', 'local_deportes'); ?>"
		        },
		        bar: {groupWidth: '20'},
		        hAxis: {
	          		title: "<?php echo get_string('sport', 'local_deportes'); ?>",
	          		viewWindow: {
	            		min: [7, 30, 0],
	            		max: [17, 30, 0]
	          		}
	        	},
	        	vAxis: {
	          		title: "<?php echo get_string('attendance', 'local_deportes'); ?>"
	        	}
		      };

      var materialChart = new google.charts.Bar(document.getElementById('sports_chart'));
      materialChart.draw(data, options);
    }
	</script>
	<script type="text/javascript">
	$( document ).on( "click", ".modal-backdrop", function() {
		jQuery('.modal').modal('hide');
	});
	$( document ).on( "click", "#button", function() {
		jQuery('#myModal').css('z-index', '').modal('show');
	});
	$(document).on("click", "#helpButton", function() {
		jQuery("#helpModal").css('z-index', '').modal("show");
	});
	</script>
	
	<script type="text/javascript">
	$(document).ready(function () {
		$("rect").attr("fill", "transparent");
	});
</script>
<?php 
} else {
	print_error(get_string("notallowed", "local_deportes"));
}