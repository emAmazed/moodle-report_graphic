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
 * Graphic report
 *
 * @package    report_graphic
 * @copyright  2014 onwards Simey Lameze <lameze@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/report/graphic/lib/gcharts.php');
require_once($CFG->dirroot . '/report/graphic/lib/highchartsphp/src/Highchart.php');
require_once($CFG->dirroot . '/report/graphic/lib/highchartsphp/src/HighchartJsExpr.php');
require_once($CFG->dirroot . '/report/graphic/lib/highchartsphp/src/HighchartOption.php');
require_once($CFG->dirroot . '/report/graphic/lib/highchartsphp/src/HighchartOptionRenderer.php');

//$chart = new Ghunti\HighchartsPHP\Highchart();
/**
 * Graphic report class.
 *
 * Retrieve log data, organize in the required format and send to google charts API.
 *
 * @package    report_graphic
 * @copyright  2015 onwards Simey Lameze <lameze@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_graphic_highcharts extends Ghunti\HighchartsPHP\Highchart {

    /**
     * @var int|null the course id.
     */
    protected $courseid;
    /**
     * @var int the current year.
     */
    protected $year;
    /**
     * @var \core\log\sql_SELECT_reader instance.
     */
    protected $logreader;
    /**
     * @var  string Log reader table name.
     */
    protected $logtable;

    /**
     * Graphic report constructor.
     *
     * Retrieve events log data to be used by other methods.
     *
     * @param int|null $courseid course id.
     */
    public function __construct($courseid = null) {
        // We may need a lot of memory here
        core_php_time_limit::raise();
        raise_memory_limit(MEMORY_HUGE);
        $this->courseid = $courseid;
        $this->year = date('Y');

        // Get the log manager.
        $logreader = get_log_manager()->get_readers();
        $logreader = reset($logreader);
        $this->logreader = $logreader;

        // Set the log table.
        $this->logtable = $logreader->get_internal_log_table_name();
    }

    /**
     * Get most triggered events by course id.
     *
     * @return string google charts data.
     */
//    public function get_most_triggered_events() {
//        global $DB;
//
//        $sql = "SELECT l.eventname, COUNT(*) as quant
//                  FROM {" . $this->logtable . "} l
//                 WHERE l.courseid = ".$this->courseid."
//                 GROUP BY l.eventname
//                 ORDER BY quant DESC";
//        $result = $DB->get_records_sql($sql);
//
//        // Graphic header, must be always the first element of the array.
//        $events[0] = array(get_string('event', 'report_graphic'), get_string('quantity', 'report_graphic'));
//
//        $i = 1;
//        foreach ($result as $eventdata) {
//            $event = $eventdata->eventname;
//            $events[$i] = array($event::get_name(), (int)$eventdata->quant);
//            $i++;
//        }
//
//        $this->load(array('graphic_type' => 'ColumnChart'));
//        $this->set_options(array('title' => get_string('eventsmosttriggered', 'report_graphic')));
//
//        return $this->generate($events);
//    }

    /**
     * Get users that most triggered events by course id.
     *
     * @return string google charts data.
     */
    public function get_most_active_users_old() {
        global $DB;

        $sql = "SELECT l.relateduserid, u.firstname, u.lastname, COUNT(*) as quant
                  FROM {" . $this->logtable . "} l
            INNER JOIN {user} u ON u.id = l.relateduserid
                 WHERE l.courseid = " . $this->courseid . "
              GROUP BY l.relateduserid, u.firstname, u.lastname
              ORDER BY quant DESC";
        $result = $DB->get_records_sql($sql);

        foreach ($result as $userdata) {
            $username = $userdata->firstname . ' ' . $userdata->lastname;
            $result['columns'][] = array($username, $userdata->quant)
        }

        return $highchart;
    }
    /**
     * Get users that most triggered events by course id.
     *
     * @return string google charts data.
     */
    public function get_most_active_users() {
        global $DB;

        $sql = "SELECT l.relateduserid, u.firstname, u.lastname, COUNT(*) as quant
                  FROM {" . $this->logtable . "} l
            INNER JOIN {user} u ON u.id = l.relateduserid
                 WHERE l.courseid = " . $this->courseid . "
              GROUP BY l.relateduserid, u.firstname, u.lastname
              ORDER BY quant DESC";
        $result = $DB->get_records_sql($sql);

        $highchart = new Ghunti\HighchartsPHP\Highchart();
        $highchart->chart->renderTo = "get_most_active_users";
        $highchart->chart->plotBackgroundColor = null;
        $highchart->chart->plotBorderWidth = null;
        $highchart->chart->plotShadow = false;
        $highchart->title->text = get_string('percentage', 'report_graphic');
        $highchart->credits->enabled = false;
        $highchart->exporting->enabled = true;
        $highchart->plotOptions->pie->allowPointSelect = 1;
        $highchart->plotOptions->pie->cursor = "pointer";
        $highchart->plotOptions->pie->dataLabels->enabled = true;
        $highchart->plotOptions->pie->showInLegend = true;
        $highchart->plotOptions->pie->dataLabels->formatter = new Ghunti\HighchartsPHP\HighchartJsExpr(
            "function() {return '<b>'+ this.point.name +'</b>: '+ this.percentage.toFixed(1) +' %'; }");
        $highchart->series[0] = array('type' => "pie", 'name' => "Quantity");
        foreach ($result as $userdata) {
            $username = $userdata->firstname . ' ' . $userdata->lastname;
            $highchart->series[0]['data'][] = array($username, (int)$userdata->quant);
        }

        return $highchart;
    }

    public function get_activity_users_events() {
        global $DB;

        $sql = "SELECT DISTINCT l.id, l.contextinstanceid, l.relateduserid, u.firstname, u.lastname, COUNT(*) AS total
                FROM {" . $this->logtable . "} l
                INNER JOIN mdl_course c ON c.id = l.courseid
                INNER JOIN mdl_user u ON l.relateduserid = u.id
                WHERE l.courseid = :courseid AND l.contextinstanceid IS NOT NULL
                GROUP BY l.id, l.contextinstanceid, l.relateduserid, u.firstname, u.lastname
                ORDER BY total DESC";
        $result = $DB->get_records_sql($sql, array('courseid' => $this->courseid));
//print_object($result);
        // Format the data to google charts.
        $i = 0;
        //$cmactivity[0] = array('Module', 'Create', 'Read', 'Update','Delete');
        $chart = new Ghunti\HighchartsPHP\Highchart();
        foreach ($result as $cmid => $values) {
            if (!empty($values->contextinstanceid)) {
                $coursemodule = get_coursemodule_from_id('',$values->contextinstanceid, $this->courseid);

                if (!empty($coursemodule)) {
                    $title['titles'][$values->contextinstanceid] = $coursemodule->name .'('.$coursemodule->modname.')';
                   // = array($coursemodule->name .'('.$coursemodule->modname.')');

                    //$cmactivity[$i] = array($title, (int)$values->quant_c, (int)$values->quant_r,(int)$values->quant_u, (int)$values->quant_d);
                    $i++;
                }
            }
        }
        //$chart = new Highchart();
//print_object($title['titles']);
$chart->chart->renderTo = "container_get_activity_users_events";
$chart->chart->type = "bar";
$chart->title->text = "Stacked bar chart"; // TODO: add lang string
//$categories = array(
//    'Apples',
//    'Oranges',
//    'Pears',
//    'Grapes',
//    'Bananas'
//);
//        print_object($categories);
        $chart->xAxis->categories = $title['titles'];
$chart->yAxis->min = 0;
$chart->yAxis->title->text = "Quantity of events by User x Activity";

$chart->tooltip->formatter = new Ghunti\HighchartsPHP\HighchartJsExpr("function() {
    return '' + this.series.name +': '+ this.y +'';}");

$chart->legend->backgroundColor = "#FFFFFF";
$chart->legend->reversed = 1;
$chart->plotOptions->series->stacking = "normal";

$chart->series[] = array(
    'name' => "John",
    'data' => array(
        5,
        3,
        4,
        7,
        2
    )
);

$chart->series[] = array(
    'name' => "Jane",
    'data' => array(
        2,
        2,
        3,
        2,
        1
    )
);

$chart->series[] = array(
    'name' => "Joe",
    'data' => array(
        3,
        4,
        4,
        2,
        5
    )
);


        $data = '<html><head><title>Pie chart</title><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
        $data .= $chart->printScripts();
        $data .= '</head><body><div id="container_get_activity_users_events"></div><script type="text/javascript">';
        $data .= $chart->render("chart1");
        $data .= '</script></body></html>';

        //return $data;
        return $chart;
    }

    /**
     * Get monthly activity (events by month x users).
     *
     * @return string the google charts data.
     */
//    public function get_monthly_user_activity() {
//        global $DB;
//
//        $courseid = $this->courseid;
//        $months = cal_info(0);
//        $year = $this->year;
//        $montharr = array();
//
//        // Build the query to get how many events each user has triggered grouping by month.
//        // This piece of code has few hacks to deal with cross-db issues but certainly can be improved.
//        // Also create required arrays of months and etc.
//        $sql = "SELECT u.id, u.firstname, u.lastname, ";
//        for ($m = 1; $m <= count($months['abbrevmonths']); $m++) {
//
//            // Get and format month name and number.
//            $monthname = $months['months'][$m];
//            $monthabbrev = $months['abbrevmonths'][$m];
//            $month = sprintf("%02d", $m);
//
//            // Get the first and the last day of the month.
//            $ymdfrom = "$year-$month-01";
//            $ymdto = date('Y-m-t', strtotime($ymdfrom));
//
//            // Convert to timestamp.
//            $date = new DateTime($ymdfrom);
//            $datefrom = $date->getTimestamp();
//            $date = new DateTime($ymdto);
//            $dateto = $date->getTimestamp();
//
//            // Get the quantity of triggered events for each month.
//            $sql .= "(SELECT COUNT(*) AS quant
//                        FROM {" . $this->logtable . "} l
//                       WHERE l.courseid = $courseid
//                         AND timecreated >= $datefrom
//                         AND timecreated < $dateto
//                         AND u.id = l.userid
//                     ) AS $monthname";
//
//            // Add comma after the month name.
//            $sql .= ($m < 12 ? ',' : ' ');
//
//            // Create a empty array that will be filled after the results of this query.
//            $montharr[$monthabbrev][0] = $monthabbrev;
//        }
//        $sql .= "FROM {user} u
//                ORDER BY u.id";
//        $result = $DB->get_records_sql($sql);
//
//        $usersarr[0] = 'Month';
//        foreach ($result as $userid => $data) {
//
//            // Faster than use fullname function.
//            if (empty($usersarr[$userid])) {
//                $usersarr[$userid] = $data->firstname . ' ' . $data->lastname;
//            }
//
//            // Fill the array with the quantity of triggered events in the month, by user id.
//            $montharr['Jan'][$userid] = (int)$data->january;
//            $montharr['Feb'][$userid] = (int)$data->february;
//            $montharr['Mar'][$userid] = (int)$data->march;
//            $montharr['Apr'][$userid] = (int)$data->april;
//            $montharr['May'][$userid] = (int)$data->may;
//            $montharr['Jun'][$userid] = (int)$data->june;
//            $montharr['Jul'][$userid] = (int)$data->july;
//            $montharr['Aug'][$userid] = (int)$data->august;
//            $montharr['Sep'][$userid] = (int)$data->september;
//            $montharr['Oct'][$userid] = (int)$data->october;
//            $montharr['Nov'][$userid] = (int)$data->november;
//            $montharr['Dec'][$userid] = (int)$data->december;
//        }
//
//        // The header of the report, must be all users
//        $final = array(0 => $usersarr);
//
//        // Organize the data in the required format of the chart.
//        for ($m = 1; $m <= count($months['abbrevmonths']); $m++) {
//            $monthabbrev = $months['abbrevmonths'][$m];
//            $final[$m] = $montharr[$monthabbrev];
//        }
//
//        $this->load(array('graphic_type' => 'linechart'));
//        $this->set_options(array('title' => get_string('eventsbymonth', 'report_graphic', $year), 'curveType' => 'function'));
//
//        return $this->generate($final);
//    }
//
//    /**
//     * Create a chart of events triggered by courses.
//     *
//     * @return string the google charts data.
//     */
//    public function get_courses_activity() {
//        global $DB;
//
//        $sql = "SELECT l.courseid, c.shortname, COUNT(*) AS quant
//                  FROM {" . $this->logtable . "} l
//            INNER JOIN mdl_course c ON c.id = l.courseid
//                 WHERE l.courseid = c.id
//              GROUP BY l.courseid, c.shortname
//              ORDER BY l.courseid";
//        $result = $DB->get_records_sql($sql);
//
//        // Format the data to google charts.
//        $i = 1;
//        $courseactivity[0] = array(get_string('course'), get_string('percentage', 'report_graphic'));
//        foreach ($result as $courseid => $coursedata) {
//            $courseactivity[$i] = array($coursedata->shortname, (int)$coursedata->quant);
//            $i++;
//        }
//
//        $this->load(array('graphic_type' => 'PieChart'));
//        $this->set_options(array('title' => get_string('coursesactivity', 'report_graphic')));
//
//        return $this->generate($courseactivity);
//    }
//
//    /**
//     * Builds the complete sql with all the joins to get the grade history data.
//     *
//     * @param bool $count setting this to true, returns an sql to get count only instead of the complete data records.
//     *
//     * @return array containing sql to use and an array of params.
//     */
//    public function get_users_grades($selecteduserid = null) {
//        global $DB;
//
//        $courseid = $this->courseid;
//        $months = cal_info(0);
//        $year = $this->year;
//        $montharr = array();
//        $avgarr = array();
//        // Build the query to get how many events each user has triggered grouping by month.
//        // This piece of code has few hacks to deal with cross-db issues but certainly can be improved.
//        // Also create required arrays of months and etc.
//        $sql = "SELECT u.id, u.firstname, u.lastname, ";
//        for ($m = 1; $m <= count($months['abbrevmonths']); $m++) {
//
//            // Get and format month name and number.
//            $monthname = $months['months'][$m];
//            $monthabbrev = $months['abbrevmonths'][$m];
//            $month = sprintf("%02d", $m);
//
//            // Get the first and the last day of the month.
//            $ymdfrom = "$year-$month-01";
//            $ymdto = date('Y-m-t', strtotime($ymdfrom));
//
//            // Convert to timestamp.
//            $date = new DateTime($ymdfrom);
//            $datefrom = $date->getTimestamp();
//            $date = new DateTime($ymdto);
//            $dateto = $date->getTimestamp();
//
//            $sql .= "(SELECT DISTINCT MAX(ggh.finalgrade)
//                    FROM {grade_grades_history} ggh
//                    JOIN {grade_items} gi ON gi.id = ggh.itemid
//                    WHERE gi.courseid = $courseid
//                    AND ggh.finalgrade IS NOT NULL
//                    AND timecreated >= $datefrom
//                    AND timecreated < $dateto
//                    AND u.id = ggh.userid
//                    GROUP BY ggh.userid
//                    ) AS $monthname";
//            // Add comma after the month name.
//            $sql .= ($m < 12 ? ',' : ' ');
//
//            // Create a empty array that will be filled after the results of this query.
//            $montharr[$monthabbrev][0] = $monthabbrev;
//            $avgarr[$monthabbrev][9999] = $monthabbrev;
//        }
//        $sql .= "FROM {user} u
//                ORDER BY u.id";
//        //print_object($sql);
//        $result = $DB->get_records_sql($sql);
//
//        $usersarr[0] = 'Month';
//
//        foreach ($result as $userid => $data) {
//
//            // Faster than use fullname function.
//            if (empty($usersarr[$userid])) {
//                $usersarr[$userid] = $data->firstname . ' ' . $data->lastname;
//            }
//
//            // Fill the array with the quantity of triggered events in the month, by user id.
//            $montharr['Jan'][$userid] = (int)$data->january;
//            $montharr['Feb'][$userid] = (int)$data->february;
//            $montharr['Mar'][$userid] = (int)$data->march;
//            $montharr['Apr'][$userid] = (int)$data->april;
//            $montharr['May'][$userid] = (int)$data->may;
//            $montharr['Jun'][$userid] = (int)$data->june;
//            $montharr['Jul'][$userid] = (int)$data->july;
//            $montharr['Aug'][$userid] = (int)$data->august;
//            $montharr['Sep'][$userid] = (int)$data->september;
//            $montharr['Oct'][$userid] = (int)$data->october;
//            $montharr['Nov'][$userid] = (int)$data->november;
//            $montharr['Dec'][$userid] = (int)$data->december;
//
//            if (!empty($selecteduserid)) {
//                $avgarr['Jan'][9999] += ((int)$data->january/18);
//                $avgarr['Feb'][9999] += ((int)$data->february/18);
//                $avgarr['Mar'][9999] += ((int)$data->march/18);
//                $avgarr['Apr'][9999] += ((int)$data->april/18);
//                $avgarr['May'][9999] += ((int)$data->may/18);
//                $avgarr['Jun'][9999] += ((int)$data->june/18);
//                $avgarr['Jul'][9999] += ((int)$data->july/18);
//                $avgarr['Aug'][9999] += ((int)$data->august/18);
//                $avgarr['Sep'][9999] += ((int)$data->september/18);
//                $avgarr['Oct'][9999] += ((int)$data->october/18);
//                $avgarr['Nov'][9999] += ((int)$data->november/18);
//                $avgarr['Dec'][9999] += ((int)$data->december/18);
//            }
//        }
////print_object($avgarr);
//        // The header of the report, must be all users
//        $final = array(0 => $usersarr);
//
//        // Organize the data in the required format of the chart.
//        for ($m = 1; $m <= count($months['abbrevmonths']); $m++) {
//            $monthabbrev = $months['abbrevmonths'][$m];
//            $final[$m] = $montharr[$monthabbrev];
//        }
//
//        $this->load(array('graphic_type' => 'linechart'));
//        $this->set_options(array('title' => get_string('gradesbymonth', 'report_graphic', $year), 'curveType' => 'function'));
//
//        return $this->generate($final);
//    }
//
//    /**
//     * Builds the complete sql with all the joins to get the grade history data.
//     *
//     * @param bool $count setting this to true, returns an sql to get count only instead of the complete data records.
//     *
//     * @return array containing sql to use and an array of params.
//     */
//    public function get_user_grades($selecteduserid) {
//        global $DB;
//
//        $courseid = $this->courseid;
//        $months = cal_info(0);
//        $year = $this->year;
//        $montharr = array();
//        $avgarr = array();
//        // Build the query to get how many events each user has triggered grouping by month.
//        // This piece of code has few hacks to deal with cross-db issues but certainly can be improved.
//        // Also create required arrays of months and etc.
//        $sql = "SELECT u.id, u.firstname, u.lastname, ";
//        for ($m = 1; $m <= count($months['abbrevmonths']); $m++) {
//
//            // Get and format month name and number.
//            $monthname = $months['months'][$m];
//            $monthabbrev = $months['abbrevmonths'][$m];
//            $month = sprintf("%02d", $m);
//
//            // Get the first and the last day of the month.
//            $ymdfrom = "$year-$month-01";
//            $ymdto = date('Y-m-t', strtotime($ymdfrom));
//
//            // Convert to timestamp.
//            $date = new DateTime($ymdfrom);
//            $datefrom = $date->getTimestamp();
//            $date = new DateTime($ymdto);
//            $dateto = $date->getTimestamp();
//
//            $sql .= "(SELECT DISTINCT MAX(ggh.finalgrade)
//                        FROM {grade_grades_history} ggh
//                        JOIN {grade_items} gi ON gi.id = ggh.itemid
//                       WHERE gi.courseid = $courseid
//                         AND ggh.finalgrade IS NOT NULL
//                         AND timecreated >= $datefrom
//                         AND timecreated < $dateto
//                         AND u.id = ggh.userid
//                    GROUP BY ggh.userid) AS $monthname";
//
//            // Add comma after the month name.
//            $sql .= ($m < 12 ? ',' : ' ');
//
//            // Create a empty array that will be filled after the results of this query.
//            $montharr[$monthabbrev][0] = $monthabbrev;
//            $avgarr[$monthabbrev][9999] = $monthabbrev;
//        }
//        $sql .= "FROM {user} u
//                ORDER BY u.id";
//        //print_object($sql);
//        $result = $DB->get_records_sql($sql);
//
//        $usersarr[0] = 'Month';
//
//        foreach ($result as $userid => $data) {
//
//            // Faster than use fullname function.
//            if (empty($usersarr[$userid])) {
//                $usersarr[$userid] = $data->firstname . ' ' . $data->lastname;
//            }
//
//            // Fill the array with the quantity of triggered events in the month, by user id.
//            $montharr['Jan'][$userid] = (int)$data->january;
//            $montharr['Feb'][$userid] = (int)$data->february;
//            $montharr['Mar'][$userid] = (int)$data->march;
//            $montharr['Apr'][$userid] = (int)$data->april;
//            $montharr['May'][$userid] = (int)$data->may;
//            $montharr['Jun'][$userid] = (int)$data->june;
//            $montharr['Jul'][$userid] = (int)$data->july;
//            $montharr['Aug'][$userid] = (int)$data->august;
//            $montharr['Sep'][$userid] = (int)$data->september;
//            $montharr['Oct'][$userid] = (int)$data->october;
//            $montharr['Nov'][$userid] = (int)$data->november;
//            $montharr['Dec'][$userid] = (int)$data->december;
//
//        }
//        //print_object($avgarr);
//        // The header of the report, must be all users
//        $final = array(0 => $usersarr);
//
//        // Organize the data in the required format of the chart.
//        for ($m = 1; $m <= count($months['abbrevmonths']); $m++) {
//            $monthabbrev = $months['abbrevmonths'][$m];
//            $final[$m] = $montharr[$monthabbrev];
//        }
//
//        $this->load(array('graphic_type' => 'linechart'));
//        $this->set_options(array('title' => get_string('gradesbymonth', 'report_graphic', $year), 'curveType' => 'function'));
//
//        return $this->generate($final);
//    }
//
//    public function get_events_course_module() {
//        global $DB;
//
//        $sql = "SELECT DISTINCT l.contextinstanceid,
//                (SELECT COUNT(*) FROM mdl_logstore_standard_log lc WHERE lc.crud = 'c' and lc.courseid = l.courseid and lc.contextinstanceid = l.contextinstanceid) AS quant_c,
//                (SELECT COUNT(*) FROM mdl_logstore_standard_log lr WHERE lr.crud = 'r' and lr.courseid = l.courseid and lr.contextinstanceid = l.contextinstanceid) AS quant_r,
//                (SELECT COUNT(*) FROM mdl_logstore_standard_log lu WHERE lu.crud = 'u' and lu.courseid = l.courseid and lu.contextinstanceid = l.contextinstanceid) AS quant_u,
//                (SELECT COUNT(*) FROM mdl_logstore_standard_log ld WHERE ld.crud = 'd' and ld.courseid = l.courseid and ld.contextinstanceid = l.contextinstanceid) AS quant_d,
//                COUNT(*) AS total
//                FROM {" . $this->logtable . "} l
//                INNER JOIN mdl_course c ON c.id = l.courseid
//                WHERE l.courseid = :courseid AND l.contextinstanceid IS NOT NULL
//                GROUP BY l.contextinstanceid, quant_c,quant_r,quant_u,quant_d
//                ORDER BY total DESC";
//        $result = $DB->get_records_sql($sql, array('courseid' => $this->courseid));
//
//        // Format the data to google charts.
//        $i = 1;
//        $cmactivity[0] = array('Module', 'Create', 'Read', 'Update','Delete');
//        foreach ($result as $cmid => $values) {
//            if (!empty($cmid)) {
//                $coursemodule = get_coursemodule_from_id('',$cmid, $this->courseid);
//
//                if (!empty($coursemodule)) {
//                    $title = $coursemodule->name .'('.$coursemodule->modname.')';
//                    $cmactivity[$i] = array($title, (int)$values->quant_c, (int)$values->quant_r,(int)$values->quant_u, (int)$values->quant_d);
//                    $i++;
//                }
//            }
//        }
//
//        $this->load(array('graphic_type' => 'ColumnChart'));
//        $this->set_options(array('title' => 'Events by Course Module (CRUD)', 'isStacked' => true));
//        return $this->generate($cmactivity);
//    }
}