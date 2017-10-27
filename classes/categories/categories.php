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
require_once('lib.php');
//require_once($CFG->libdir . '/formslib.php');
//require_once($CFG->dirroot . '/repository/lib.php');


class Categories {

    /**
    *   retorna las categorias y subcategorias de un foro
    *
    **/
    function get($id_forum, $parent_category=0){
        global $DB;

        $sql="select * from {communityforum_categories} where forum=? and parent_category=?";

        $result = $DB->get_records_sql($sql, array($id_forum,$parent_category));

        return $result;

    }


    function count($id_category){
        
        global $DB;

        //$sql="select count(*) from {communityforum_categories} where forum=? and parent_category=?";

        //$result = $DB->get_records_sql($sql, array($id_forum,$parent_category));

        $result = $DB->count_records("communityforum_categories", ["parent_category" => $id_category]);

        return $result;
        
    }




}
