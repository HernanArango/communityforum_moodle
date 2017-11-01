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



class Likes {

    
    function insert($post_id, $user_id,$like_or_dislike){
        global $DB;

        
        $record = new stdClass();
        $record->userid = $user_id;
        $record->postid = $post_id;
        $record->likes = $like_or_dislike;
        

        $lastinsertid = $DB->insert_record('communityforum_post_likes', $record);

        

        return $lastinsertid;

    }


    function get($post_id, $user_id){
        global $DB;

        $result = $DB->get_record("communityforum_post_likes", ["postid" => $post_id,"userid" => $user_id]);

        return $result;

    }






}
