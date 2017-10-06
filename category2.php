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
$prune   = optional_param('prune', 0, PARAM_INT);
$name    = optional_param('name', '', PARAM_CLEAN);
$confirm = optional_param('confirm', 0, PARAM_INT);
$groupid = optional_param('groupid', null, PARAM_INT);
$parent_category = optional_param('parent_category', 0, PARAM_INT);

$PAGE->set_url('/mod/communityforum/cateogry.php', array(
        'forum' => $forum,
        'edit'  => $edit,
        'delete'=> $delete,
        'prune' => $prune,
        'name'  => $name,
        'confirm'=>$confirm,
        'groupid'=>$groupid,
        'category'=>$parent_category,
        ));
//these page_params will be passed as hidden variables later in the form.
$page_params = array('forum'=>$forum, 'edit'=>$edit, 'parent_category'=>$parent_category);

$sitecontext = context_system::instance();

if (!isloggedin() or isguestuser()) {

    if (!isloggedin() and !get_local_referer()) {
        // No referer+not logged in - probably coming in via email  See MDL-9052
        require_login();
    }

    if (!empty($forum)) {      // User is starting a new discussion in a forum

        if (! $forum = $DB->get_record('communityforum', array('id' => $forum))) {
            print_error('invalidforumid', 'communityforum');
        }
    } 

    else if (!empty($category)){
        if (! $category = $DB->get_record('communityforum_categories', array('id' => $category))) {
            print_error('invalidcategoryid', 'communityforum');
        }   
    }

    if (! $course = $DB->get_record('course', array('id' => $forum->course))) {
        print_error('invalidcourseid');
    }

    if (!$cm = get_coursemodule_from_instance('communityforum', $forum->id, $course->id)) { // For the logs
        print_error('invalidcoursemodule');
    } else {
        $modcontext = context_module::instance($cm->id);
    }

    $PAGE->set_cm($cm, $course, $forum);
    $PAGE->set_context($modcontext);
    $PAGE->set_title($course->shortname);
    $PAGE->set_heading($course->fullname);
    $referer = get_local_referer(false);

    echo $OUTPUT->header();
    echo $OUTPUT->confirm(get_string('noguestpost', 'communityforum').'<br /><br />'.get_string('liketologin'), get_login_url(), $referer);
    echo $OUTPUT->footer();
    exit;
}

require_login(0, false);   // Script is useless unless they're logged in

if (!empty($forum)) {      // User is starting a new discussion in a forum
    
    if (! $forum = $DB->get_record("communityforum", array("id" => $forum))) {
        print_error('invalidforumid', 'communityforum');
    }
    if (! $course = $DB->get_record("course", array("id" => $forum->course))) {
        print_error('invalidcourseid');
    }
    if (! $cm = get_coursemodule_from_instance("communityforum", $forum->id, $course->id)) {
        print_error("invalidcoursemodule");
    }
    
    // Retrieve the contexts.
    $modcontext    = context_module::instance($cm->id);
    $coursecontext = context_course::instance($course->id);
    if (! communityforum_user_can_create_category($coursecontext)) {
        print_error('nopostforum', 'communityforum');
    }

    $SESSION->fromurl = get_local_referer(false);
    
    // Load up the $category variable.

    $category = new stdClass();
    $category->course        = $course->id;
    $category->forum         = $forum->id;
    $category->parent_category  = $parent_category;
    $category->name       = '';
    $category->userid        = $USER->id;
    $category->description       = '';
    $category->messageformat = editors_get_preferred_format();
    $category->messagetrust  = 0;
    
    // Unsetting this will allow the correct return URL to be calculated later.
    unset($SESSION->fromdiscussion);
    
} else if (!empty($edit)) {  // User is editing their own post
    
    if (! $category = communityforum_get_post_full($edit)) {
        print_error('invalidpostid', 'communityforum');
    }
    if ($category->parent) {
        if (! $parent = communityforum_get_post_full($category->parent)) {
            print_error('invalidparentpostid', 'communityforum');
        }
    }

    if (! $discussion = $DB->get_record("communityforum_discussions", array("id" => $category->discussion))) {
        print_error('notpartofdiscussion', 'communityforum');
    }
    if (! $forum = $DB->get_record("communityforum", array("id" => $discussion->forum))) {
        print_error('invalidforumid', 'communityforum');
    }
    if (! $course = $DB->get_record("course", array("id" => $discussion->course))) {
        print_error('invalidcourseid');
    }
    if (!$cm = get_coursemodule_from_instance("communityforum", $forum->id, $course->id)) {
        print_error('invalidcoursemodule');
    } else {
        $modcontext = context_module::instance($cm->id);
    }

    $PAGE->set_cm($cm, $course, $forum);

    if (!($forum->type == 'news' && !$category->parent && $discussion->timestart > time())) {
        if (((time() - $category->created) > $CFG->maxeditingtime) and
                    !has_capability('mod/communityforum:editanypost', $modcontext)) {
            print_error('maxtimehaspassed', 'communityforum', '', format_time($CFG->maxeditingtime));
        }
    }
    if (($category->userid <> $USER->id) and
                !has_capability('mod/communityforum:editanypost', $modcontext)) {
        print_error('cannoteditposts', 'communityforum');
    }


    // Load up the $category variable.
    $category->edit   = $edit;
    $category->course = $course->id;
    $category->forum  = $forum->id;
    $category->groupid = ($discussion->groupid == -1) ? 0 : $discussion->groupid;

    $category = trusttext_pre_edit($category, 'message', $modcontext);

    // Unsetting this will allow the correct return URL to be calculated later.
    unset($SESSION->fromdiscussion);

}else if (!empty($delete)) {  // User is deleting a post

    if (! $category = communityforum_get_post_full($delete)) {
        print_error('invalidpostid', 'communityforum');
    }
    if (! $discussion = $DB->get_record("communityforum_discussions", array("id" => $category->discussion))) {
        print_error('notpartofdiscussion', 'communityforum');
    }
    if (! $forum = $DB->get_record("communityforum", array("id" => $discussion->forum))) {
        print_error('invalidforumid', 'communityforum');
    }
    if (!$cm = get_coursemodule_from_instance("communityforum", $forum->id, $forum->course)) {
        print_error('invalidcoursemodule');
    }
    if (!$course = $DB->get_record('course', array('id' => $forum->course))) {
        print_error('invalidcourseid');
    }

    require_login($course, false, $cm);
    $modcontext = context_module::instance($cm->id);

    if ( !(($category->userid == $USER->id && has_capability('mod/communityforum:deleteownpost', $modcontext))
                || has_capability('mod/communityforum:deleteanypost', $modcontext)) ) {
        print_error('cannotdeletepost', 'communityforum');
    }


    $replycount = communityforum_count_replies($category);

    if (!empty($confirm) && confirm_sesskey()) {    // User has confirmed the delete
        //check user capability to delete post.
        $timepassed = time() - $category->created;
        if (($timepassed > $CFG->maxeditingtime) && !has_capability('mod/communityforum:deleteanypost', $modcontext)) {
            print_error("cannotdeletepost", "communityforum",
                        communityforum_go_back_to(new moodle_url("/mod/communityforum/discuss.php", array('d' => $category->discussion))));
        }

        if ($category->totalscore) {
            notice(get_string('couldnotdeleteratings', 'rating'),
                   communityforum_go_back_to(new moodle_url("/mod/communityforum/discuss.php", array('d' => $category->discussion))));

        } else if ($replycount && !has_capability('mod/communityforum:deleteanypost', $modcontext)) {
            print_error("couldnotdeletereplies", "communityforum",
                        communityforum_go_back_to(new moodle_url("/mod/communityforum/discuss.php", array('d' => $category->discussion))));

        } else {
            if (! $category->parent) {  // post is a discussion topic as well, so delete discussion
                if ($forum->type == 'single') {
                    notice("Sorry, but you are not allowed to delete that discussion!",
                           communityforum_go_back_to(new moodle_url("/mod/communityforum/discuss.php", array('d' => $category->discussion))));
                }
                communityforum_delete_discussion($discussion, false, $course, $cm, $forum);

                $params = array(
                    'objectid' => $discussion->id,
                    'context' => $modcontext,
                    'other' => array(
                        'forumid' => $forum->id,
                    )
                );

                $event = \mod_communityforum\event\discussion_deleted::create($params);
                $event->add_record_snapshot('communityforum_discussions', $discussion);
                $event->trigger();

                redirect("view.php?f=$discussion->forum");

            } else if (communityforum_delete_post($category, has_capability('mod/communityforum:deleteanypost', $modcontext),
                $course, $cm, $forum)) {

                if ($forum->type == 'single') {
                    // Single discussion forums are an exception. We show
                    // the forum itself since it only has one discussion
                    // thread.
                    $discussionurl = new moodle_url("/mod/communityforum/view.php", array('f' => $forum->id));
                } else {
                    $discussionurl = new moodle_url("/mod/communityforum/discuss.php", array('d' => $discussion->id));
                }

                redirect(communityforum_go_back_to($discussionurl));
            } else {
                print_error('errorwhiledelete', 'communityforum');
            }
        }


    } else { // User just asked to delete something

        communityforum_set_return();
        $PAGE->navbar->add(get_string('delete', 'communityforum'));
        $PAGE->set_title($course->shortname);
        $PAGE->set_heading($course->fullname);

        if ($replycount) {
            if (!has_capability('mod/communityforum:deleteanypost', $modcontext)) {
                print_error("couldnotdeletereplies", "communityforum",
                      communityforum_go_back_to(new moodle_url('/mod/communityforum/discuss.php', array('d' => $category->discussion), 'p'.$category->id)));
            }
            echo $OUTPUT->header();
            echo $OUTPUT->heading(format_string($forum->name), 2);
            echo $OUTPUT->confirm(get_string("deletesureplural", "communityforum", $replycount+1),
                         "post.php?delete=$delete&confirm=$delete",
                         $CFG->wwwroot.'/mod/communityforum/discuss.php?d='.$category->discussion.'#p'.$category->id);

            communityforum_print_post($category, $discussion, $forum, $cm, $course, false, false, false);

            if (empty($category->edit)) {
                $forumtracked = communityforum_tp_is_tracked($forum);
                $categorys = communityforum_get_all_discussion_posts($discussion->id, "created ASC", $forumtracked);
                communityforum_print_posts_nested($course, $cm, $forum, $discussion, $category, false, false, $forumtracked, $categorys);
            }
        } else {
            echo $OUTPUT->header();
            echo $OUTPUT->heading(format_string($forum->name), 2);
            echo $OUTPUT->confirm(get_string("deletesure", "communityforum", $replycount),
                         "post.php?delete=$delete&confirm=$delete",
                         $CFG->wwwroot.'/mod/communityforum/discuss.php?d='.$category->discussion.'#p'.$category->id);
            communityforum_print_post($category, $discussion, $forum, $cm, $course, false, false, false);
        }

    }
    echo $OUTPUT->footer();
    die;


} else if (!empty($prune)) {  // Pruning

    if (!$category = communityforum_get_post_full($prune)) {
        print_error('invalidpostid', 'communityforum');
    }
    if (!$discussion = $DB->get_record("communityforum_discussions", array("id" => $category->discussion))) {
        print_error('notpartofdiscussion', 'communityforum');
    }
    if (!$forum = $DB->get_record("communityforum", array("id" => $discussion->forum))) {
        print_error('invalidforumid', 'communityforum');
    }
    if ($forum->type == 'single') {
        print_error('cannotsplit', 'communityforum');
    }
    if (!$category->parent) {
        print_error('alreadyfirstpost', 'communityforum');
    }
    if (!$cm = get_coursemodule_from_instance("communityforum", $forum->id, $forum->course)) { // For the logs
        print_error('invalidcoursemodule');
    } else {
        $modcontext = context_module::instance($cm->id);
    }
    if (!has_capability('mod/communityforum:splitdiscussions', $modcontext)) {
        print_error('cannotsplit', 'communityforum');
    }

    $PAGE->set_cm($cm);
    $PAGE->set_context($modcontext);

    $prunemform = new mod_communityforum_prune_form(null, array('prune' => $prune, 'confirm' => $prune));


    if ($prunemform->is_cancelled()) {
        redirect(communityforum_go_back_to(new moodle_url("/mod/communityforum/discuss.php", array('d' => $category->discussion))));
    } else if ($fromform = $prunemform->get_data()) {
        // User submits the data.
        $newdiscussion = new stdClass();
        $newdiscussion->course       = $discussion->course;
        $newdiscussion->forum        = $x->forum;
        $newdiscussion->name         = $name;
        $newdiscussion->firstpost    = $category->id;
        $newdiscussion->userid       = $discussion->userid;
        $newdiscussion->groupid      = $discussion->groupid;
        $newdiscussion->assessed     = $discussion->assessed;
        $newdiscussion->usermodified = $category->userid;
        $newdiscussion->timestart    = $discussion->timestart;
        $newdiscussion->timeend      = $discussion->timeend;

        $newid = $DB->insert_record('communityforum_discussions', $newdiscussion);

        $newpost = new stdClass();
        $newpost->id      = $category->id;
        $newpost->parent  = 0;
        $newpost->subject = $name;

        $DB->update_record("communityforum_posts", $newpost);

        communityforum_change_discussionid($category->id, $newid);

        // Update last post in each discussion.
        communityforum_discussion_update_last_post($discussion->id);
        communityforum_discussion_update_last_post($newid);

        // Fire events to reflect the split..
        $params = array(
            'context' => $modcontext,
            'objectid' => $discussion->id,
            'other' => array(
                'forumid' => $forum->id,
            )
        );
        $event = \mod_communityforum\event\discussion_updated::create($params);
        $event->trigger();

        $params = array(
            'context' => $modcontext,
            'objectid' => $newid,
            'other' => array(
                'forumid' => $forum->id,
            )
        );
        $event = \mod_communityforum\event\discussion_created::create($params);
        $event->trigger();

        $params = array(
            'context' => $modcontext,
            'objectid' => $category->id,
            'other' => array(
                'discussionid' => $newid,
                'forumid' => $forum->id,
                'forumtype' => $forum->type,
            )
        );
        $event = \mod_communityforum\event\post_updated::create($params);
        $event->add_record_snapshot('communityforum_discussions', $discussion);
        $event->trigger();

        redirect(communityforum_go_back_to(new moodle_url("/mod/communityforum/discuss.php", array('d' => $newid))));

    } else {
        // Display the prune form.
        $course = $DB->get_record('course', array('id' => $forum->course));
        $PAGE->navbar->add(format_string($category->subject, true), new moodle_url('/mod/communityforum/discuss.php', array('d'=>$discussion->id)));
        $PAGE->navbar->add(get_string("prune", "communityforum"));
        $PAGE->set_title(format_string($discussion->name).": ".format_string($category->subject));
        $PAGE->set_heading($course->fullname);
        echo $OUTPUT->header();
        echo $OUTPUT->heading(format_string($forum->name), 2);
        echo $OUTPUT->heading(get_string('pruneheading', 'communityforum'), 3);

        $prunemform->display();

        communityforum_print_post($category, $discussion, $forum, $cm, $course, false, false, false);
    }

    echo $OUTPUT->footer();
    die;
} else {
    print_error('unknowaction');

}

if (!isset($coursecontext)) {
    // Has not yet been set by post.php.
    $coursecontext = context_course::instance($forum->course);
}


// from now on user must be logged on properly

if (!$cm = get_coursemodule_from_instance('communityforum', $forum->id, $course->id)) { // For the logs
    print_error('invalidcoursemodule');
}
$modcontext = context_module::instance($cm->id);
require_login($course, false, $cm);

if (isguestuser()) {
    // just in case
    print_error('noguest');
}

if (!isset($forum->maxattachments)) {  // TODO - delete this once we add a field to the forum table
    $forum->maxattachments = 3;
}

$thresholdwarning = communityforum_check_throttling($forum, $cm);
$mform_category = new mod_communityforum_category_form('category.php', array('course' => $course,
                                                        'cm' => $cm,
                                                        'coursecontext' => $coursecontext,
                                                        'modcontext' => $modcontext,
                                                        'forum' => $forum,
                                                        'post' => $category,
                                                        'subscribe' => \mod_communityforum\subscriptions::is_subscribed($USER->id, $forum,
                                                                null, $cm),
                                                        'thresholdwarning' => $thresholdwarning,
                                                        'edit' => $edit), 'post', '', array('id' => 'mformforum'));
                                                                                                           


//load data into form NOW!

if ($USER->id != $category->userid) {   // Not the original author, so add a message to the end
    $data = new stdClass();
    $data->date = userdate($category->modified);
    if ($category->messageformat == FORMAT_HTML) {
        $data->name = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$USER->id.'&course='.$category->course.'">'.
                       fullname($USER).'</a>';
        $category->message .= '<p><span class="edited">('.get_string('editedby', 'communityforum', $data).')</span></p>';
    } else {
        $data->name = fullname($USER);
        $category->message .= "\n\n(".get_string('editedby', 'communityforum', $data).')';
    }
    unset($data);
}

$formheading = '';
if (!empty($parent)) {
    $heading = get_string("yourreply", "communityforum");
    $formheading = get_string('reply', 'communityforum');
} else {
    if ($forum->type == 'qanda') {
        $heading = get_string('yournewquestion', 'communityforum');
    } else {
        $heading = get_string('yournewtopic', 'communityforum');
    }
}

$categoryid = empty($category->id) ? null : $category->id;
$draftid_editor = file_get_submitted_draft_itemid('message');
//$currenttext = file_prepare_draft_area($draftid_editor, $modcontext->id, 'mod_communityforum', 'post', $categoryid, mod_communityforum_post_form::editor_options($modcontext, $categoryid), $category->message);

$manageactivities = has_capability('moodle/course:manageactivities', $coursecontext);
if (\mod_communityforum\subscriptions::subscription_disabled($forum) && !$manageactivities) {
    // User does not have permission to subscribe to this discussion at all.
    $discussionsubscribe = false;
} else if (\mod_communityforum\subscriptions::is_forcesubscribed($forum)) {
    // User does not have permission to unsubscribe from this discussion at all.
    $discussionsubscribe = true;
} else {
    if (isset($discussion) && \mod_communityforum\subscriptions::is_subscribed($USER->id, $forum, $discussion->id, $cm)) {
        // User is subscribed to the discussion - continue the subscription.
        $discussionsubscribe = true;
    } else if (!isset($discussion) && \mod_communityforum\subscriptions::is_subscribed($USER->id, $forum, null, $cm)) {
        // Starting a new discussion, and the user is subscribed to the forum - subscribe to the discussion.
        $discussionsubscribe = true;
    } else {
        // User is not subscribed to either forum or discussion. Follow user preference.
        $discussionsubscribe = $USER->autosubscribe;
    }
}

$mform_category->set_data(array(        'general'=>$heading,
                                    'category'=>array(
                                        //'text'=>$currenttext,
                                        'format'=>empty($category->messageformat) ? editors_get_preferred_format() : $category->messageformat,
                                        'itemid'=>$draftid_editor
                                    ),
                                    'userid'=>$category->userid,
                                    'parent'=>$category->parent_category,
                                    'course'=>$course->id) +
                                    $page_params +

                            (isset($category->format)?array(
                                    'format'=>$category->format):
                                array())+

                            (isset($discussion->timestart)?array(
                                    'timestart'=>$discussion->timestart):
                                array())+

                            (isset($discussion->timeend)?array(
                                    'timeend'=>$discussion->timeend):
                                array())+

                            (isset($discussion->pinned) ? array(
                                     'pinned' => $discussion->pinned) :
                                array()) +

                            (isset($category->groupid)?array(
                                    'groupid'=>$category->groupid):
                                array())+

                            (isset($discussion->id)?
                                    array('discussion'=>$discussion->id):
                                    array()));

if ($mform_category->is_cancelled()) {
    if (!isset($discussion->id) || $forum->type === 'qanda') {
        // Q and A forums don't have a discussion page, so treat them like a new thread..
        redirect(new moodle_url('/mod/communityforum/view.php', array('f' => $forum->id)));
    } else {
        redirect(new moodle_url('/mod/communityforum/discuss.php', array('d' => $discussion->id)));
    }
} else if ($fromform = $mform_category->get_data()) {

    if (empty($SESSION->fromurl)) {
        $errordestination = "$CFG->wwwroot/mod/communityforum/view.php?f=$forum->id";
    } else {
        $errordestination = $SESSION->fromurl;
    }

    if ($fromform->edit) {           // Updating a post
        unset($fromform->groupid);
        $fromform->id = $fromform->edit;
        $message = '';

        //fix for bug #4314
        if (!$realpost = $DB->get_record('communityforum_posts', array('id' => $fromform->id))) {
            $realpost = new stdClass();
            $realpost->userid = -1;
        }


        // if user has edit any post capability
        // or has either startnewdiscussion or reply capability and is editting own post
        // then he can proceed
        // MDL-7066
        if ( !(($realpost->userid == $USER->id && (has_capability('mod/communityforum:replypost', $modcontext)
                            || has_capability('mod/communityforum:startdiscussion', $modcontext))) ||
                            has_capability('mod/communityforum:editanypost', $modcontext)) ) {
            print_error('cannotupdatepost', 'communityforum');
        }

        // If the user has access to all groups and they are changing the group, then update the post.
        if (isset($fromform->groupinfo) && has_capability('mod/communityforum:movediscussions', $modcontext)) {
            if (empty($fromform->groupinfo)) {
                $fromform->groupinfo = -1;
            }

            if (!communityforum_user_can_post_discussion($forum, $fromform->groupinfo, null, $cm, $modcontext)) {
                print_error('cannotupdatepost', 'communityforum');
            }

            $DB->set_field('communityforum_discussions' ,'groupid' , $fromform->groupinfo, array('firstpost' => $fromform->id));
        }
        // When editing first post/discussion.
        if (!$fromform->parent) {
            if (has_capability('mod/communityforum:pindiscussions', $modcontext)) {
                // Can change pinned if we have capability.
                $fromform->pinned = !empty($fromform->pinned) ? COMMUNITYFORUM_DISCUSSION_PINNED : COMMUNITYFORUM_DISCUSSION_UNPINNED;
            } else {
                // We don't have the capability to change so keep to previous value.
                unset($fromform->pinned);
            }
        }
        $updatepost = $fromform; //realpost
        $updatepost->forum = $forum->id;
        if (!communityforum_update_post($updatepost, $mform_category)) {
            print_error("couldnotupdate", "communityforum", $errordestination);
        }

        // MDL-11818
        if (($forum->type == 'single') && ($updatepost->parent == '0')){ // updating first post of single discussion type -> updating forum intro
            $forum->intro = $updatepost->message;
            $forum->timemodified = time();
            $DB->update_record("communityforum", $forum);
        }

        if ($realpost->userid == $USER->id) {
            $message .= get_string("postupdated", "communityforum");
        } else {
            $realuser = $DB->get_record('user', array('id' => $realpost->userid));
            $message .= get_string("editedpostupdated", "communityforum", fullname($realuser));
        }

        $subscribemessage = communityforum_post_subscription($fromform, $forum, $discussion);
        if ($forum->type == 'single') {
            // Single discussion forums are an exception. We show
            // the forum itself since it only has one discussion
            // thread.
            $discussionurl = new moodle_url("/mod/communityforum/view.php", array('f' => $forum->id));
        } else {
            $discussionurl = new moodle_url("/mod/communityforum/discuss.php", array('d' => $discussion->id), 'p' . $fromform->id);
        }

        $params = array(
            'context' => $modcontext,
            'objectid' => $fromform->id,
            'other' => array(
                'discussionid' => $discussion->id,
                'forumid' => $forum->id,
                'forumtype' => $forum->type,
            )
        );

        if ($realpost->userid !== $USER->id) {
            $params['relateduserid'] = $realpost->userid;
        }

        $event = \mod_communityforum\event\post_updated::create($params);
        $event->add_record_snapshot('communityforum_discussions', $discussion);
        $event->trigger();

        redirect(
                communityforum_go_back_to($discussionurl),
                $message . $subscribemessage,
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );

    } else if ($fromform->discussion) { // Adding a new post to an existing discussion
        // Before we add this we must check that the user will not exceed the blocking threshold.
        forum_check_blocking_threshold($thresholdwarning);

        unset($fromform->groupid);
        $message = '';
        $addpost = $fromform;
        $addpost->forum=$forum->id;
        if ($fromform->id = forum_add_new_post($addpost, $mform_post)) {
            $subscribemessage = forum_post_subscription($fromform, $forum, $discussion);

            if (!empty($fromform->mailnow)) {
                $message .= get_string("postmailnow", "forum");
            } else {
                $message .= '<p>'.get_string("postaddedsuccess", "forum") . '</p>';
                $message .= '<p>'.get_string("postaddedtimeleft", "forum", format_time($CFG->maxeditingtime)) . '</p>';
            }

            if ($forum->type == 'single') {
                // Single discussion forums are an exception. We show
                // the forum itself since it only has one discussion
                // thread.
                $discussionurl = new moodle_url("/mod/forum/view.php", array('f' => $forum->id), 'p'.$fromform->id);
            } else {
                $discussionurl = new moodle_url("/mod/forum/discuss.php", array('d' => $discussion->id), 'p'.$fromform->id);
            }

            $params = array(
                'context' => $modcontext,
                'objectid' => $fromform->id,
                'other' => array(
                    'discussionid' => $discussion->id,
                    'forumid' => $forum->id,
                    'forumtype' => $forum->type,
                )
            );
            $event = \mod_forum\event\post_created::create($params);
            $event->add_record_snapshot('forum_posts', $fromform);
            $event->add_record_snapshot('forum_discussions', $discussion);
            $event->trigger();

            // Update completion state
            $completion=new completion_info($course);
            if($completion->is_enabled($cm) &&
                ($forum->completionreplies || $forum->completionposts)) {
                $completion->update_state($cm,COMPLETION_COMPLETE);
            }

            redirect(
                    forum_go_back_to($discussionurl),
                    $message . $subscribemessage,
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );

        } else {
            print_error("couldnotadd", "forum", $errordestination);
        }
        exit;
    }

    else { // Adding a new category.
        // The location to redirect to after successfully posting.
        $redirectto = new moodle_url('view.php', array('f' => $fromform->forum));

        $fromform->mailnow = empty($fromform->mailnow) ? 0 : 1;

        $category = $fromform;
        $category->name = $fromform->name;
        }

        // Redirect back to the discussion.
        redirect(
                communityforum_go_back_to($redirectto->out())
                //$message . $subscribemessage,
                //null,
                //\core\output\notification::NOTIFY_SUCCESS
            );
    }




// To get here they need to edit a post, and the $category
// variable will be loaded with all the particulars,
// so bring up the form.

// $course, $forum are defined.  $discussion is for edit and reply only.

if (empty($category->edit)) {
    $category->edit = '';
}

if (empty($discussion->name)) {
    if (empty($discussion)) {
        $discussion = new stdClass();
    }
    $discussion->name = $forum->name;
}
if ($forum->type == 'single') {
    // There is only one discussion thread for this forum type. We should
    // not show the discussion name (same as forum name in this case) in
    // the breadcrumbs.
    $strdiscussionname = '';
} else {
    // Show the discussion name in the breadcrumbs.
    $strdiscussionname = format_string($discussion->name).':';
}

$forcefocus = empty($reply) ? NULL : 'message';

if (!empty($discussion->id)) {
    $PAGE->navbar->add(format_string($toppost->subject, true), "discuss.php?d=$discussion->id");
}

if ($category->parent) {
    $PAGE->navbar->add(get_string('reply', 'communityforum'));
}

if ($edit) {
    $PAGE->navbar->add(get_string('edit', 'communityforum'));
}

//$PAGE->set_title("$course->shortname: $strdiscussionname ".format_string($toppost->subject));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($forum->name), 2);

// checkup
if (!empty($parent) && !communityforum_user_can_see_post($forum, $discussion, $category, null, $cm)) {
    print_error('cannotreply', 'communityforum');
}
if (empty($parent) && empty($edit) && !communityforum_user_can_post_discussion($forum, $groupid, -1, $cm, $modcontext)) {
    print_error('cannotcreatediscussion', 'communityforum');
}

if ($forum->type == 'qanda'
            && !has_capability('mod/communityforum:viewqandawithoutposting', $modcontext)
            && !empty($discussion->id)
            && !communityforum_user_has_posted($forum->id, $discussion->id, $USER->id)) {
    echo $OUTPUT->notification(get_string('qandanotify','communityforum'));
}

// If there is a warning message and we are not editing a post we need to handle the warning.
if (!empty($thresholdwarning) && !$edit) {
    // Here we want to throw an exception if they are no longer allowed to post.
    communityforum_check_blocking_threshold($thresholdwarning);
}

if (!empty($parent)) {
    if (!$discussion = $DB->get_record('communityforum_discussions', array('id' => $parent->discussion))) {
        print_error('notpartofdiscussion', 'communityforum');
    }

    communityforum_print_post($parent, $discussion, $forum, $cm, $course, false, false, false);
    if (empty($category->edit)) {
        if ($forum->type != 'qanda' || communityforum_user_can_see_discussion($forum, $discussion, $modcontext)) {
            $forumtracked = communityforum_tp_is_tracked($forum);
            $categorys = communityforum_get_all_discussion_posts($discussion->id, "created ASC", $forumtracked);
            communityforum_print_posts_threaded($course, $cm, $forum, $discussion, $parent, 0, false, $forumtracked, $categorys);
        }
    }
} else {
    if (!empty($forum->intro)) {
        echo $OUTPUT->box(format_module_intro('communityforum', $forum, $cm->id), 'generalbox', 'intro');

        if (!empty($CFG->enableplagiarism)) {
            require_once($CFG->libdir.'/plagiarismlib.php');
            echo plagiarism_print_disclosure($cm->id);
        }
    }
}

if (!empty($formheading)) {
    echo $OUTPUT->heading($formheading, 2, array('class' => 'accesshide'));
}
$mform_category->display();

echo $OUTPUT->footer();
