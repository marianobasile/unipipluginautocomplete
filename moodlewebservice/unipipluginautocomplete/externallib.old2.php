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


        $sql = " SELECT c.id, c.fullname, u.lastname, u.firstname
                    
         FROM mdl_course c
         JOIN mdl_context ct ON c.id = ct.instanceid
         JOIN mdl_role_assignments ra ON ra.contextid = ct.id
         JOIN mdl_user u ON u.id = ra.userid
         JOIN mdl_role r ON r.id = ra.roleid

         WHERE ct.contextlevel = 50 and u.lastname like '%$teachername%' and r.id = 3 ORDER BY c.fullname ASC";

        $rs = $DB->get_recordset_sql($sql, $params);

        $courses = array();
        $c = 0; // counts how many visible courses we've seen
        $course_count = 10;

        $limitfrom = 0;
        $limitto   = $limitfrom + $course_count;


        if (!empty($rs)) {
            foreach($rs as $course) {
                // Don't exit this loop till the end
                // we need to count all the visible courses
                // to update $totalcount
                $course->serverAddress = $_SERVER['SERVER_NAME'];
                if ($c >= $limitfrom && $c < $limitto) {
                    $courses[$course->id] = $course;
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

        $sql = " SELECT c.id, c.fullname, u.lastname, u.firstname
                    
         FROM mdl_course c
         JOIN mdl_context ct ON c.id = ct.instanceid
         JOIN mdl_role_assignments ra ON ra.contextid = ct.id
         JOIN mdl_user u ON u.id = ra.userid
         JOIN mdl_role r ON r.id = ra.roleid

         WHERE ct.contextlevel = 50 and c.fullname like '%$coursename%' and r.id = 3 ORDER BY c.fullname ASC";

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

                    ORDER BY c.fullname ASC";

        $coursesWithoutTeacher = $DB->get_records_sql($sql, $params);

        $noTeacherFound = false;
        $courses = array();

        if(empty($coursesWithTeacher)){
            $noTeacherFound = true;
            $courses = $coursesWithoutTeacher;
        }else
            $courses = array_merge($coursesWithTeacher,$coursesWithoutTeacher);


        $temp = array();
        $c = 0; // counts how many visible courses we've seen
        $course_count = 10;

        $limitfrom = 0;
        $limitto   = $limitfrom + $course_count;


        if (!empty($courses)) {
            foreach($courses as $course) {
                // Don't exit this loop till the end
                // we need to count all the visible courses
                // to update $totalcount
                $course->serverAddress = $_SERVER['SERVER_NAME'];
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