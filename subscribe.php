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
 * Subscribe to or unsubscribe from a forum or manage forum subscription mode
 *
 * This script can be used by either individual users to subscribe to or
 * unsubscribe from a forum (no 'mode' param provided), or by forum managers
 * to control the subscription mode (by 'mode' param).
 * This script can be called from a link in email so the sesskey is not
 * required parameter. However, if sesskey is missing, the user has to go
 * through a confirmation page that redirects the user back with the
 * sesskey.
 *
 * @package   mod_communityforum
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once($CFG->dirroot.'/mod/communityforum/lib.php');

$id             = required_param('id', PARAM_INT);             // The forum to set subscription on.
$mode           = optional_param('mode', null, PARAM_INT);     // The forum's subscription mode.
$user           = optional_param('user', 0, PARAM_INT);        // The userid of the user to subscribe, defaults to $USER.
$discussionid   = optional_param('d', null, PARAM_INT);        // The discussionid to subscribe.
$sesskey        = optional_param('sesskey', null, PARAM_RAW);
$returnurl      = optional_param('returnurl', null, PARAM_RAW);

$url = new moodle_url('/mod/communityforum/subscribe.php', array('id'=>$id));
if (!is_null($mode)) {
    $url->param('mode', $mode);
}
if ($user !== 0) {
    $url->param('user', $user);
}
if (!is_null($sesskey)) {
    $url->param('sesskey', $sesskey);
}
if (!is_null($discussionid)) {

    $url->param('d', $discussionid);
    if (!$discussion = $DB->get_record('communityforum_discussions', array('id' => $discussionid, 'forum' => $id))) {
        print_error('invaliddiscussionid', 'communityforum');
    }
}
$PAGE->set_url($url);

$forum   = $DB->get_record('communityforum', array('id' => $id), '*', MUST_EXIST);
$course  = $DB->get_record('course', array('id' => $forum->course), '*', MUST_EXIST);
$cm      = get_coursemodule_from_instance('communityforum', $forum->id, $course->id, false, MUST_EXIST);
$context = context_module::instance($cm->id);

if ($user) {
    require_sesskey();
    if (!has_capability('mod/communityforum:managesubscriptions', $context)) {
        print_error('nopermissiontosubscribe', 'communityforum');
    }
    $user = $DB->get_record('user', array('id' => $user), '*', MUST_EXIST);
} else {
    $user = $USER;
}

if (isset($cm->groupmode) && empty($course->groupmodeforce)) {
    $groupmode = $cm->groupmode;
} else {
    $groupmode = $course->groupmode;
}

$issubscribed = \mod_communityforum\subscriptions::is_subscribed($user->id, $forum, $discussionid, $cm);

// For a user to subscribe when a groupmode is set, they must have access to at least one group.
if ($groupmode && !$issubscribed && !has_capability('moodle/site:accessallgroups', $context)) {
    if (!groups_get_all_groups($course->id, $USER->id)) {
        print_error('cannotsubscribe', 'communityforum');
    }
}

require_login($course, false, $cm);

if (is_null($mode) and !is_enrolled($context, $USER, '', true)) {   // Guests and visitors can't subscribe - only enrolled
    $PAGE->set_title($course->shortname);
    $PAGE->set_heading($course->fullname);
    if (isguestuser()) {
        echo $OUTPUT->header();
        echo $OUTPUT->confirm(get_string('subscribeenrolledonly', 'communityforum').'<br /><br />'.get_string('liketologin'),
                     get_login_url(), new moodle_url('/mod/communityforum/view.php', array('f'=>$id)));
        echo $OUTPUT->footer();
        exit;
    } else {
        // There should not be any links leading to this place, just redirect.
        redirect(
                new moodle_url('/mod/communityforum/view.php', array('f'=>$id)),
                get_string('subscribeenrolledonly', 'communityforum'),
                null,
                \core\output\notification::NOTIFY_ERROR
            );
    }
}

$returnto = optional_param('backtoindex',0,PARAM_INT)
    ? "index.php?id=".$course->id
    : "view.php?f=$id";

if ($returnurl) {
    $returnto = $returnurl;
}

if (!is_null($mode) and has_capability('mod/communityforum:managesubscriptions', $context)) {
    require_sesskey();
    switch ($mode) {
        case COMMUNITYFORUM_CHOOSESUBSCRIBE : // 0
            \mod_communityforum\subscriptions::set_subscription_mode($forum->id, COMMUNITYFORUM_CHOOSESUBSCRIBE);
            redirect(
                    $returnto,
                    get_string('everyonecannowchoose', 'communityforum'),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );
            break;
        case COMMUNITYFORUM_FORCESUBSCRIBE : // 1
            \mod_communityforum\subscriptions::set_subscription_mode($forum->id, COMMUNITYFORUM_FORCESUBSCRIBE);
            redirect(
                    $returnto,
                    get_string('everyoneisnowsubscribed', 'communityforum'),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );
            break;
        case COMMUNITYFORUM_INITIALSUBSCRIBE : // 2
            if ($forum->forcesubscribe <> COMMUNITYFORUM_INITIALSUBSCRIBE) {
                $users = \mod_communityforum\subscriptions::get_potential_subscribers($context, 0, 'u.id, u.email', '');
                foreach ($users as $user) {
                    \mod_communityforum\subscriptions::subscribe_user($user->id, $forum, $context);
                }
            }
            \mod_communityforum\subscriptions::set_subscription_mode($forum->id, COMMUNITYFORUM_INITIALSUBSCRIBE);
            redirect(
                    $returnto,
                    get_string('everyoneisnowsubscribed', 'communityforum'),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );
            break;
        case COMMUNITYFORUM_DISALLOWSUBSCRIBE : // 3
            \mod_communityforum\subscriptions::set_subscription_mode($forum->id, COMMUNITYFORUM_DISALLOWSUBSCRIBE);
            redirect(
                    $returnto,
                    get_string('noonecansubscribenow', 'communityforum'),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );
            break;
        default:
            print_error(get_string('invalidforcesubscribe', 'communityforum'));
    }
}

if (\mod_communityforum\subscriptions::is_forcesubscribed($forum)) {
    redirect(
            $returnto,
            get_string('everyoneisnowsubscribed', 'communityforum'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
}

$info = new stdClass();
$info->name  = fullname($user);
$info->forum = format_string($forum->name);

if ($issubscribed) {
    if (is_null($sesskey)) {
        // We came here via link in email.
        $PAGE->set_title($course->shortname);
        $PAGE->set_heading($course->fullname);
        echo $OUTPUT->header();

        $viewurl = new moodle_url('/mod/communityforum/view.php', array('f' => $id));
        if ($discussionid) {
            $a = new stdClass();
            $a->forum = format_string($forum->name);
            $a->discussion = format_string($discussion->name);
            echo $OUTPUT->confirm(get_string('confirmunsubscribediscussion', 'communityforum', $a),
                    $PAGE->url, $viewurl);
        } else {
            echo $OUTPUT->confirm(get_string('confirmunsubscribe', 'communityforum', format_string($forum->name)),
                    $PAGE->url, $viewurl);
        }
        echo $OUTPUT->footer();
        exit;
    }
    require_sesskey();
    if ($discussionid === null) {
        if (\mod_communityforum\subscriptions::unsubscribe_user($user->id, $forum, $context, true)) {
            redirect(
                    $returnto,
                    get_string('nownotsubscribed', 'communityforum', $info),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );
        } else {
            print_error('cannotunsubscribe', 'communityforum', get_local_referer(false));
        }
    } else {
        if (\mod_communityforum\subscriptions::unsubscribe_user_from_discussion($user->id, $discussion, $context)) {
            $info->discussion = $discussion->name;
            redirect(
                    $returnto,
                    get_string('discussionnownotsubscribed', 'communityforum', $info),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );
        } else {
            print_error('cannotunsubscribe', 'communityforum', get_local_referer(false));
        }
    }

} else {  // subscribe
    if (\mod_communityforum\subscriptions::subscription_disabled($forum) && !has_capability('mod/communityforum:managesubscriptions', $context)) {
        print_error('disallowsubscribe', 'communityforum', get_local_referer(false));
    }
    if (!has_capability('mod/communityforum:viewdiscussion', $context)) {
        print_error('noviewdiscussionspermission', 'communityforum', get_local_referer(false));
    }
    if (is_null($sesskey)) {
        // We came here via link in email.
        $PAGE->set_title($course->shortname);
        $PAGE->set_heading($course->fullname);
        echo $OUTPUT->header();

        $viewurl = new moodle_url('/mod/communityforum/view.php', array('f' => $id));
        if ($discussionid) {
            $a = new stdClass();
            $a->forum = format_string($forum->name);
            $a->discussion = format_string($discussion->name);
            echo $OUTPUT->confirm(get_string('confirmsubscribediscussion', 'communityforum', $a),
                    $PAGE->url, $viewurl);
        } else {
            echo $OUTPUT->confirm(get_string('confirmsubscribe', 'communityforum', format_string($forum->name)),
                    $PAGE->url, $viewurl);
        }
        echo $OUTPUT->footer();
        exit;
    }
    require_sesskey();
    if ($discussionid == null) {
        \mod_communityforum\subscriptions::subscribe_user($user->id, $forum, $context, true);
        redirect(
                $returnto,
                get_string('nowsubscribed', 'communityforum', $info),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
    } else {
        $info->discussion = $discussion->name;
        \mod_communityforum\subscriptions::subscribe_user_to_discussion($user->id, $discussion, $context);
        redirect(
                $returnto,
                get_string('discussionnowsubscribed', 'communityforum', $info),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
    }
}
