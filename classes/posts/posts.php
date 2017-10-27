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
//require_once('lib.php');
//require_once('../../lib/outputrenderers.php');


class Posts {

	function get_last($id_forum){
		global $DB;
		$sql = "select id,category,name,userid,timemodified,forum from mdl_communityforum_discussions where forum=? order by timemodified desc limit 10";

		$result = $DB->get_records_sql($sql, array($id_forum));

		if ($result) {

            return $result;
        }
        else{
            return false;
        }
	}

	function show_last($id_forum){
		global $OUTPUT;
		global $CFG;

		//$OUTPUT->render_from_template($templatename, $context);
		$last_post = $this->get_last($id_forum);
		
		if(!$last_post){
			echo "<h2>No existen post</h2>";
		}
		else{
			foreach ($last_post as $post) {
				echo "<div class='last_post row-fluid'>";
					echo "<div class='span9'>";
									echo "<div class='span2'>";
										echo "<img class='profilepic' style='width:45px;' src='".$CFG->wwwroot.'/user/pix.php?file=/'.$post->userid."'/f1.jpg>";
									echo "</div>";
									echo "<div class='span10'>";
										echo "<a href='$CFG->wwwroot/mod/communityforum/discuss.php?d=$post->id'>".$post->name."</a>";
									echo "</div>";
					echo "</div>";
					echo "<div class='span3'>";
						echo $post->category; 
					echo "</div>";

				echo "</div>";
			}
		}
	}

}