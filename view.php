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
 * @package   mod_communityforum
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

    require_once('../../config.php');
    require_once('lib.php');
    require_once('./classes/categories/categories.php');
    require_once($CFG->libdir.'/completionlib.php');

    $id          = optional_param('id', 0, PARAM_INT);       // Course Module ID
    $f           = optional_param('f', 0, PARAM_INT);        // communityforum ID
    $mode        = optional_param('mode', 0, PARAM_INT);     // Display mode (for single forum)
    $showall     = optional_param('showall', '', PARAM_INT); // show all discussions on one page
    $changegroup = optional_param('group', -1, PARAM_INT);   // choose the current group
    $page        = optional_param('page', 0, PARAM_INT);     // which page to show
    $search      = optional_param('search', '', PARAM_CLEAN);// search string
    $category      = optional_param('category', 0, PARAM_INT);// search string

    $params = array();
    if ($id) {
        $params['id'] = $id;
    } else {
        $params['f'] = $f;
    }
    if ($page) {
        $params['page'] = $page;
    }
    if ($search) {
        $params['search'] = $search;
    }
    
    $PAGE->set_url('/mod/communityforum/view.php', $params);

    if ($id) {
        if (! $cm = get_coursemodule_from_id('communityforum', $id)) {
            print_error('invalidcoursemodule');
        }
        if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
            print_error('coursemisconf');
        }
        if (! $forum = $DB->get_record("communityforum", array("id" => $cm->instance))) {
            print_error('invalidforumid', 'communityforum');
        }
        if ($forum->type == 'single') {
            $PAGE->set_pagetype('mod-communityforum-discuss');
        }
        // move require_course_login here to use forced language for course
        // fix for MDL-6926
        require_course_login($course, true, $cm);
        $strforums = get_string("modulenameplural", "communityforum");
        $strforum = get_string("modulename", "communityforum");
    } else if ($f) {

        if (! $forum = $DB->get_record("communityforum", array("id" => $f))) {
            print_error('invalidforumid', 'communityforum');
        }
        if (! $course = $DB->get_record("course", array("id" => $forum->course))) {
            print_error('coursemisconf');
        }

        if (!$cm = get_coursemodule_from_instance("communityforum", $forum->id, $course->id)) {
            print_error('missingparameter');
        }
        // move require_course_login here to use forced language for course
        // fix for MDL-6926
        require_course_login($course, true, $cm);
        $strforums = get_string("modulenameplural", "communityforum");
        $strforum = get_string("modulename", "communityforum");
    } else {
        print_error('missingparameter');
    }

    if (!$PAGE->button) {
        $PAGE->set_button(communityforum_search_form($course, $search));
    }

    $context = context_module::instance($cm->id);
    $PAGE->set_context($context);

    if (!empty($CFG->enablerssfeeds) && !empty($CFG->forum_enablerssfeeds) && $forum->rsstype && $forum->rssarticles) {
        require_once("$CFG->libdir/rsslib.php");

        $rsstitle = format_string($course->shortname, true, array('context' => context_course::instance($course->id))) . ': ' . format_string($forum->name);
        rss_add_http_header($context, 'mod_communityforum', $forum, $rsstitle);
    }

/// Print header.

    $PAGE->set_title($forum->name);
    $PAGE->add_body_class('communityforumtype-'.$forum->type);
    $PAGE->set_heading($course->fullname);

/// Some capability checks.
    if (empty($cm->visible) and !has_capability('moodle/course:viewhiddenactivities', $context)) {
        notice(get_string("activityiscurrentlyhidden"));
    }

    if (!has_capability('mod/communityforum:viewdiscussion', $context)) {
        notice(get_string('noviewdiscussionspermission', 'communityforum'));
    }

    // Mark viewed and trigger the course_module_viewed event.
    communityforum_view($forum, $course, $cm, $context);

    echo $OUTPUT->header();

    echo $OUTPUT->heading(format_string($forum->name), 2);
    if (!empty($forum->intro) && $forum->type != 'single' && $forum->type != 'teacher') {
        echo $OUTPUT->box(format_module_intro('communityforum', $forum, $cm->id), 'generalbox', 'intro');
    }

/// find out current groups mode
    groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/communityforum/view.php?id=' . $cm->id);

    $SESSION->fromdiscussion = qualified_me();   // Return here if we post or set subscription etc


/// Print settings and things across the top

    // If it's a simple single discussion forum, we need to print the display
    // mode control.
    if ($forum->type == 'single') {
        $discussion = NULL;
        $discussions = $DB->get_records('communityforum_discussions', array('communityforum'=>$forum->id), 'timemodified ASC');
        if (!empty($discussions)) {
            $discussion = array_pop($discussions);
        }
        if ($discussion) {
            if ($mode) {
                set_user_preference("communityforum_displaymode", $mode);
            }
            $displaymode = get_user_preferences("communityforum_displaymode", $CFG->forum_displaymode);
            forum_print_mode_form($forum->id, $displaymode, $forum->type);
        }
    }
    if (!empty($forum->blockafter) && !empty($forum->blockperiod)) {
        $a = new stdClass();
        $a->blockafter = $forum->blockafter;
        $a->blockperiod = get_string('secondstotime'.$forum->blockperiod);
        echo $OUTPUT->notification(get_string('thisforumisthrottled', 'communityforum', $a));
    }

    if ($forum->type == 'qanda' && !has_capability('moodle/course:manageactivities', $context)) {
        echo $OUTPUT->notification(get_string('qandanotify','communityforum'));
    }
/*
    switch ($forum->type) {
        case 'single':
            if (!empty($discussions) && count($discussions) > 1) {
                echo $OUTPUT->notification(get_string('warnformorepost', 'communityforum'));
            }
            if (! $post = communityforum_get_post_full($discussion->firstpost)) {
                print_error('cannotfindfirstpost', 'communityforum');
            }
            if ($mode) {
                set_user_preference("communityforum_displaymode", $mode);
            }

            $canreply    = communityforum_user_can_post($forum, $discussion, $USER, $cm, $course, $context);
            $canrate     = has_capability('mod/communityforum:rate', $context);
            $displaymode = get_user_preferences("communityforum_displaymode", $CFG->forum_displaymode);

            echo '&nbsp;'; // this should fix the floating in FF
            communityforum_print_discussion($course, $cm, $forum, $discussion, $post, $displaymode, $canreply, $canrate);
            break;

        case 'eachuser':
            echo '<p class="mdl-align">';
            if (communityforum_user_can_post_discussion($forum, null, -1, $cm)) {
                print_string("allowsdiscussions", "communityforum");
            } else {
                echo '&nbsp;';
            }
            echo '</p>';
            if (!empty($showall)) {
                communityforum_print_latest_discussions($course, $forum, 0, 'header', '', -1, -1, -1, 0, $cm);
            } else {
                communityforum_print_latest_discussions($course, $forum, -1, 'header', '', -1, -1, $page, $CFG->forum_manydiscussions, $cm);
            }
            break;

        case 'teacher':
            if (!empty($showall)) {
                communityforum_print_latest_discussions($course, $forum, 0, 'header', '', -1, -1, -1, 0, $cm);
            } else {
                communityforum_print_latest_discussions($course, $forum, -1, 'header', '', -1, -1, $page, $CFG->forum_manydiscussions, $cm);
            }
            break;

        case 'blog':
            echo '<br />';
            if (!empty($showall)) {
                communityforum_print_latest_discussions($course, $forum, 0, 'plain', 'd.pinned DESC, p.created DESC', -1, -1, -1, 0, $cm);
            } else {
                communityforum_print_latest_discussions($course, $forum, -1, 'plain', 'd.pinned DESC, p.created DESC', -1, -1, $page,
                    $CFG->forum_manydiscussions, $cm);
            }
            break;

        default:
            echo '<br />';
            if (!empty($showall)) {
                communityforum_print_latest_discussions($course, $forum, 0, 'header', '', -1, -1, -1, 0, $cm);
            } else {
                communityforum_print_latest_discussions($course, $forum, -1, 'header', '', -1, -1, $page, $CFG->forum_manydiscussions, $cm);
            }


            break;
    }
*/


/*------------------------------------CATEGORIAS--------------------------------*/
    
    if(!$category){

        $PAGE->requires->js_call_amd('mod_communityforum/categories','init',array($id));
        $PAGE->requires->js_call_amd('mod_communityforum/categories','loadCategories',array($id));
        echo "<div class='row-fluid'>";
        echo "<div id='categories' class='span6'></div>";
        echo "<div id='recent_post' class='span6'>
        
<img src='http://localhost/moodle/mod/communityforum/pix/post.png'>
</div>";
        echo "</div>";
        $url = new moodle_url($CFG->wwwroot . '/mod/communityforum/category.php', array('id' => $id));
        
        if(is_siteadmin()){
        
            $button = new single_button($url,"Nueva Categoria");
            echo html_writer::tag('div', $OUTPUT->render($button), array('style' => 'text-align:center'));  
        }
    }
    else{

        echo '<br />';
            if (!empty($showall)) {
                communityforum_print_latest_discussions($course, $forum, $category, 0, 'header', '', -1, -1, -1, 0, $cm);
            } else {
                communityforum_print_latest_discussions($course, $forum, $category,-1, 'header', '', -1, -1, $page, $CFG->forum_manydiscussions, $cm);
            }
        echo '<br>';
    }
    
    echo $OUTPUT->footer($course);
