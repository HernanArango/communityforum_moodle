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



require_once('../../config.php');
require_once("./classes/likes/likes.php");


//$id_user = optional_param('userid', 0, PARAM_INT);
$id_post = optional_param('postid', 0, PARAM_INT);
$like_or_dislike = optional_param('like', 0, PARAM_INT);

$like = new Likes();

$like->insert($id_post,$USER->id,$like_or_dislike);




