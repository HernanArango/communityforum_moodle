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

$id   = optional_param('id', 0, PARAM_INT);
$edit    = optional_param('edit', 0, PARAM_INT);
$delete  = optional_param('delete', 0, PARAM_INT);
$category = optional_param('category', 0, PARAM_INT);


if (!$cm = get_coursemodule_from_id('communityforum', $id)) { // For the logs
        print_error('invalidcoursemodule');
} 
if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error('invalidcourseid');
}
if (! $forum = $DB->get_record('communityforum', array('id' => $cm->instance))) {
            print_error('invalidforumid', 'communityforum');
}
else {
        $modcontext = context_module::instance($cm->id);
}

$PAGE->set_url('/mod/communityforum/cateogry.php', array(
        'forum' => $forum->id,
        'edit'  => $edit,
        'delete'=> $delete,
        'category'=>$category,
        ));


$PAGE->set_cm($cm, $course, $forum);
$PAGE->set_context($modcontext);
$PAGE->set_title($forum->name);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();


$mform = new mod_communityforum_category_form('category.php', array('forum' => $forum->id,
                                                        'edit' => $edit,
                                                        'delete' => $delete,
                                                        'category' => $category,
                                                        ));
 

//
if ($mform->is_cancelled()) {
  redirect(new moodle_url('/mod/communityforum/view.php', array('id' => $cm->id)));  
} 

else if($delete){

    $return = communityforum_delete_category($category);
    
    if($return){
      redirect(new moodle_url('/mod/communityforum/view.php', array('id' => $cm->id)));
    }else{
      print_error('error_delete_cateogry', 'communityforum');
      $url = new moodle_url($CFG->wwwroot . '/mod/communityforum/view.php', array('id' => $cm->id));
      $button = new single_button($url,"Volver");
      echo html_writer::tag('div', $OUTPUT->render($button), array('style' => 'text-align:center'));  
    } 
}

else if ($fromform = $mform->get_data()) {

  $edit=$fromform->edit;

  if($edit){
    $return = communityforum_update_category($fromform);

    if($return){

      redirect(new moodle_url('/mod/communityforum/view.php', array('id' => $cm->id)));
    }
    else{
      print_error('error_edit_cateogry', 'communityforum');
      $url = new moodle_url($CFG->wwwroot . '/mod/communityforum/view.php', array('id' => $cm->id));
      $button = new single_button($url,"Volver");
      echo html_writer::tag('div', $OUTPUT->render($button), array('style' => 'text-align:center'));  
    }   
  }
  
  else{
    $return = communityforum_add_category($fromform);
    if($return){
      redirect(new moodle_url('/mod/communityforum/view.php', array('id' => $cm->id)));
    }
    else{
      print_error('add_category_correct', 'communityforum');
      $url = new moodle_url($CFG->wwwroot . '/mod/communityforum/view.php', array('id' => $cm->id));
      $button = new single_button($url,"Continuar");
      echo html_writer::tag('div', $OUTPUT->render($button), array('style' => 'text-align:center'));  
    }   
  }
} 
else {
  $mform->display();
}

echo $OUTPUT->footer($course);