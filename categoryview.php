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
require_once('lib.php');
require_once('./classes/categories/categories.php');
//require_once($CFG->libdir . '/formslib.php');
//require_once($CFG->dirroot . '/repository/lib.php');

$id = optional_param('id', 0, PARAM_INT);
$id_category = optional_param('parent', 0, PARAM_INT);



if (! $cm = get_coursemodule_from_id('communityforum', $id)) {
            print_error('invalidcoursemodule');
}
if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
    print_error('coursemisconf');
}
if (! $forum = $DB->get_record("communityforum", array("id" => $cm->instance))) {
    print_error('invalidforumid', 'communityforum');
}



$categories = new Categories();
$categorias = $categories->get($forum->id,$id_category);

if(count($categorias) == 0){
	echo "<h2>No existen categorías</h2>";
}



	foreach ($categorias as $categoria) {
		$total_sub_categorias = $categories->count($categoria->id);

		
		if($total_sub_categorias > 0 && $total_sub_categorias != 0){
			echo "<div class='category-communityforum enlace' parent='$categoria->id' estado='cerrado'>";
			echo "<i id='ico".$categoria->id."' class='fa fa-plus-square ico_category' aria-hidden='true'></i>";
	 		echo "<a id='$id' parent='$categoria->id' href='#'>".$categoria->name_category."</a>";
	 		if(is_siteadmin()){
	 			echo "<a style='float:right' href='category.php?id=$id&category=$categoria->id&edit=1'><i class='fa fa-cog' aria-hidden='true'></i></a>";
	 			echo "<a style='float:right' id='delete'><i subcategory=0 parent='$categoria->id' class='fa fa-trash needjs' aria-hidden='true'></i></a>";
	 		}
	 		echo "</div>";
	 		echo "<div id='sub".$categoria->id."' class='subcategoria'></div>";
		}
	 	else{
	 		echo "<div class='category-communityforum' parent='$categoria->id' estado='cerrado'>";
	 		echo "<a href='view.php?id=$id&category=$categoria->id'>".$categoria->name_category."</a>";
	 		if(is_siteadmin()){
	 			echo "<a style='float:right' href='category.php?id=$id&category=$categoria->id&edit=1'><i class='fa fa-cog ico_category' aria-hidden='true'></i></a>";
	 			echo "<a style='float:right' id='delete'><i subcategory=1 parent='$categoria->id' class='fa fa-trash ico_category needjs' aria-hidden='true'></i></a>";
	 		}
	 		echo "</div>";
			echo "<div id='sub".$categoria->id."' class='subcategoria'></div>";	 	}

	 	
	}

	

