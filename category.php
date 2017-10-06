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
 * Edit and save a new post to a discussion
 *
 * @package   mod_communityforum
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('lib.php');
require_once($CFG->libdir.'/completionlib.php');

$forum   = optional_param('forum', 0, PARAM_INT);
$edit    = optional_param('edit', 0, PARAM_INT);
$delete  = optional_param('delete', 0, PARAM_INT);
$parent_category = optional_param('parent_category', 0, PARAM_INT);
$forum = $DB->get_record('communityforum', array('id' => $forum));

$PAGE->set_url('/mod/communityforum/cateogry.php', array(
        'forum' => $forum->id,
        'edit'  => $edit,
        'delete'=> $delete,
        'category'=>$parent_category,
        ));

//$cm = get_coursemodule_from_instance('communityforum', $forum->id);
//$course = $DB->get_record('course', array('id' => $forum->course));
//$modcontext = context_module::instance($cm->id);
//$PAGE->set_cm($cm, $course, $forum);
//$PAGE->set_context($modcontext);
$PAGE->set_heading($forum->name);
echo $OUTPUT->header();

//these page_params will be passed as hidden variables later in the form.


//Instantiate simplehtml_form 
$mform = new mod_communityforum_category_form('category.php', array('forum' => $forum->id,
                                                        'edit' => $edit,
                                                        'delete' => $delete,
                                                        ));
 
//Form processing and displaying is done here
if ($mform->is_cancelled()) {
    //Handle form cancel operation, if cancel button is present on form
} else if ($fromform = $mform->get_data()) {
  //$page_params = array('forum'=>$forum->id, 'edit'=>$edit, 'parent_category'=>$parent_category, 'delete'=>$delete);
  $return = communityforum_add_category($fromform);
  if($return){
    redirect(new moodle_url('/mod/communityforum/view.php', array('f' => $forum->id)));
  }
  else{

  }   

} else {
  $mform->display();
}