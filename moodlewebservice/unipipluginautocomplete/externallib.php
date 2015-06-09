<?php

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
 * External Web Service Template
 *
 * @package    local
 * @subpackage unipipluginautocomplete
 * @copyright  2015 Mariano Basile
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("$CFG->libdir/externallib.php");

require_once('../../config.php');

if(!defined('AJAX_SCRIPT')) {
    define('AJAX_SCRIPT', true);
}

require_once($CFG->libdir . '/datalib.php');
require_once($CFG->libdir . '/accesslib.php');

class local_unipipluginautocomplete_external extends external_api {
    
    public static function search_by_teacher_autocomplete_parameters() {
        return new external_function_parameters(
            array(
                'teachername' => new external_value(PARAM_TEXT, 'String used for find courses. Search based on teacher lastname')
            ) 
        );
    }
    
    public static function search_by_teacher_autocomplete_returns() {
        return new external_value(PARAM_TEXT, 'Courses found with that teacher lastname');
    }

    public static function search_by_teacher_autocomplete($teachername) { //Don't forget to set it as static
        global $CFG, $DB;
        require_once($CFG->dirroot . "/course/lib.php");

        $params = self::validate_parameters(self::search_by_teacher_autocomplete_parameters(), array('teachername'=>$teachername));

        if (trim($teachername) == '') {
                throw new invalid_parameter_exception('Invalid teacher name');
            }

        $sql = "SELECT c.id, c.fullname, u.lastname, u.firstname
                    
         FROM mdl_course c
         JOIN mdl_context ct ON c.id = ct.instanceid
         JOIN mdl_role_assignments ra ON ra.contextid = ct.id
         JOIN mdl_user u ON u.id = ra.userid
         JOIN mdl_role r ON r.id = ra.roleid

         WHERE ct.contextlevel = 50 and u.lastname like '%$teachername%' and r.id = 3
         UNION
         SELECT '0','VISUALIZZA TUTTI','examplelastname','examplefirstname' ";

        $rs = $DB->get_recordset_sql($sql, $params);

        $sql = " SELECT Count(*) AS quantity
                    
         FROM mdl_course c
         JOIN mdl_context ct ON c.id = ct.instanceid
         JOIN mdl_role_assignments ra ON ra.contextid = ct.id
         JOIN mdl_user u ON u.id = ra.userid
         JOIN mdl_role r ON r.id = ra.roleid

         WHERE ct.contextlevel = 50 and u.lastname like '%$teachername%' and r.id = 3 ORDER BY c.fullname ASC";

        $temp = $DB->get_record_sql($sql, $params);

$courses = array();
$c = 1; // counts how many visible courses we've seen
$course_count = 10;

$limitfrom = 0;
$limitto   = $limitfrom + $course_count;


if (!empty($rs)) {
    foreach($rs as $course) {
        // Don't exit this loop till the end
        // we need to count all the visible courses
        // to update $totalcount
            $course->serverAddress = $_SERVER['SERVER_NAME'];

            if($temp->quantity > 10 )
                $course->numberOfRecords = 10;
            else{
                if($temp->quantity == 10)
                $course->numberOfRecords = $temp->quantity;
                else
                $course->numberOfRecords = $temp->quantity+1;   
            }
                

            if($temp->quantity < 10 ) {
                    $courses[$course->id] = $course;
            }else{
                if($c<=9){
                    $courses[$course->id] = $course;
                }else{

                    $sql = "SELECT c.id, c.fullname, u.lastname, u.firstname
                    
                    FROM mdl_course c
                    JOIN mdl_context ct ON c.id = ct.instanceid
                    JOIN mdl_role_assignments ra ON ra.contextid = ct.id
                    JOIN mdl_user u ON u.id = ra.userid
                    JOIN mdl_role r ON r.id = ra.roleid

                    WHERE ct.contextlevel = 50 and u.lastname like '' and r.id = 3
                    UNION
                    SELECT '0','VISUALIZZA TUTTI','examplelastname','examplefirstname' ";

                    $set = $DB->get_record_sql($sql, $params);
                    $courses[0] = $set;

                    if($temp->quantity > 10 )
                    $courses[0]->numberOfRecords = 10;
                    else{
                        if($temp->quantity == 10)
                            $courses[0]->numberOfRecords = $temp->quantity;
                        else 
                            $courses[0]->numberOfRecords = $temp->quantity+1;
                    }
                                
                    break;
                }          
            }
        $c++;       
    }           
    $rs->close();
    $courses['results'] = array_values($courses);
    return json_encode($courses);    
}
}

    public static function search_by_coursename_autocomplete_parameters() {
        return new external_function_parameters(
            array(
                'coursename' => new external_value(PARAM_TEXT, 'String used for find courses. Search based on coursename')
            ) 
        );
    }

    public static function search_by_coursename_autocomplete_returns() {
        return new external_value(PARAM_TEXT, 'Courses found with that coursename');
    }

    public static function search_by_coursename_autocomplete($coursename) { //Don't forget to set it as static
        global $CFG, $DB;
        require_once($CFG->dirroot . "/course/lib.php");

        $params = self::validate_parameters(self::search_by_coursename_autocomplete_parameters(), array('coursename'=>$coursename));

        //$serverAddress = $_SERVER['SERVER_NAME'];
        
        $sql = "SELECT c.id, c.fullname, u.lastname, u.firstname
                    
         FROM mdl_course c
         JOIN mdl_context ct ON c.id = ct.instanceid
         JOIN mdl_role_assignments ra ON ra.contextid = ct.id
         JOIN mdl_user u ON u.id = ra.userid
         JOIN mdl_role r ON r.id = ra.roleid

         WHERE ct.contextlevel = 50 and c.fullname like '%$coursename%' and r.id = 3
         UNION
         SELECT '0','VISUALIZZA TUTTI','examplelastname','examplefirstname' ";

        $coursesWithTeacher = $DB->get_records_sql($sql, $params);

        $sql = "SELECT c.id, c.fullname
                    
                    FROM mdl_course c
                    JOIN mdl_context ct ON c.id = ct.instanceid
        
                    WHERE ct.contextlevel = 50 and c.fullname like '%$coursename%' 
                    
                    and c.id not in (SELECT c.id
                    
                    FROM mdl_course c
                    JOIN mdl_context ct ON c.id = ct.instanceid
                    JOIN mdl_role_assignments ra ON ra.contextid = ct.id
                    JOIN mdl_user u ON u.id = ra.userid
                    JOIN mdl_role r ON r.id = ra.roleid

                    WHERE ct.contextlevel = 50 and c.fullname like '%$coursename%' and r.id = 3 ORDER BY c.fullname ASC)

                    UNION
                    SELECT '0','VISUALIZZA TUTTI' ";

        $coursesWithoutTeacher = $DB->get_records_sql($sql, $params);

        $noTeacherFound = false;
        $courses = array();

        if(empty($coursesWithTeacher)){
            $noTeacherFound = true;
            $courses = $coursesWithoutTeacher;

        }else {

            $sql = "SELECT c.id, c.fullname, u.lastname, u.firstname
                    
                    FROM mdl_course c
                    JOIN mdl_context ct ON c.id = ct.instanceid
                    JOIN mdl_role_assignments ra ON ra.contextid = ct.id
                    JOIN mdl_user u ON u.id = ra.userid
                    JOIN mdl_role r ON r.id = ra.roleid

                    WHERE ct.contextlevel = 50 and c.fullname like '%$coursename%' and r.id = 3";

                    $coursesWithTeacherWithoutShowAll = $DB->get_records_sql($sql, $params);

                    $courses = array_merge($coursesWithTeacherWithoutShowAll,$coursesWithoutTeacher);
        }
            

        $quantity = sizeof($courses);

        $temp = array();
        $c = 1; // counts how many visible courses we've seen
        $course_count = 10;

        $limitfrom = 0;
        $limitto   = $limitfrom + $course_count;

        if (!empty($courses)) {
        foreach($courses as $course) {
        // Don't exit this loop till the end
        // we need to count all the visible courses
        // to update $totalcount
            $course->serverAddress = $_SERVER['SERVER_NAME'];

            if($quantity > 10 )
                $course->numberOfRecords = 10;
            else
            $course->numberOfRecords = $quantity;
            
            if($quantity < 10 ) {
                    $temp[$course->id] = $course;
            }else{
                if($c<=9){
                    $temp[$course->id] = $course;
                }else{

                    $sql = "SELECT c.id, c.fullname, u.lastname, u.firstname
                    
                    FROM mdl_course c
                    JOIN mdl_context ct ON c.id = ct.instanceid
                    JOIN mdl_role_assignments ra ON ra.contextid = ct.id
                    JOIN mdl_user u ON u.id = ra.userid
                    JOIN mdl_role r ON r.id = ra.roleid

                    WHERE ct.contextlevel = 50 and c.fullname like '' and r.id = 3
                    UNION
                    SELECT '0','VISUALIZZA TUTTI','examplelastname','examplefirstname' ";

                    $set = $DB->get_record_sql($sql, $params);
                    $temp[0] = $set;

                    if($quantity > 10 )
                    $temp[0]->numberOfRecords = 10;
                    else
                    $temp[0]->numberOfRecords = $quantity;
                
                    break;
                }          
            }
        $c++;       
    }           
    //$rs->close();
     $temp['results'] = array_values($temp);
            return json_encode($temp);   
}
}
}
/*
        if (!empty($courses)) {
            foreach($courses as $course) {
                // Don't exit this loop till the end
                // we need to count all the visible courses
                // to update $totalcount
                $course->serverAddress = $_SERVER['SERVER_NAME'];
                $course->numberOfRecords = $quantity;
                if ($c >= $limitfrom && $c < $limitto) {
                    $temp[$course->id] = $course;
                }
                $c++;
            }
            //$rs->close();
            $temp['results'] = array_values($temp);
            return json_encode($temp);
        }
    }
}
*/