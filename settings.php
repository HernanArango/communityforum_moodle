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
 * @package   mod_forum
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once($CFG->dirroot.'/mod/communityforum/lib.php');

    $settings->add(new admin_setting_configselect('communityforum_displaymode', get_string('displaymode', 'communityforum'),
                       get_string('configdisplaymode', 'communityforum'), COMMUNITYFORUM_MODE_NESTED, communityforum_get_layout_modes()));

    // Less non-HTML characters than this is short
    $settings->add(new admin_setting_configtext('communityforum_shortpost', get_string('shortpost', 'communityforum'),
                       get_string('configshortpost', 'communityforum'), 300, PARAM_INT));

    // More non-HTML characters than this is long
    $settings->add(new admin_setting_configtext('communityforum_longpost', get_string('longpost', 'communityforum'),
                       get_string('configlongpost', 'communityforum'), 600, PARAM_INT));

    // Number of discussions on a page
    $settings->add(new admin_setting_configtext('communityforum_manydiscussions', get_string('manydiscussions', 'communityforum'),
                       get_string('configmanydiscussions', 'communityforum'), 100, PARAM_INT));

    if (isset($CFG->maxbytes)) {
        $maxbytes = 0;
        if (isset($CFG->forum_maxbytes)) {
            $maxbytes = $CFG->forum_maxbytes;
        }
        $settings->add(new admin_setting_configselect('communityforum_maxbytes', get_string('maxattachmentsize', 'communityforum'),
                           get_string('configmaxbytes', 'communityforum'), 512000, get_max_upload_sizes($CFG->maxbytes, 0, 0, $maxbytes)));
    }

    // Default number of attachments allowed per post in all forums
    $settings->add(new admin_setting_configtext('communityforum_maxattachments', get_string('maxattachments', 'communityforum'),
                       get_string('configmaxattachments', 'communityforum'), 9, PARAM_INT));

    // Default Read Tracking setting.
    $options = array();
    $options[COMMUNITYFORUM_TRACKING_OPTIONAL] = get_string('trackingoptional', 'communityforum');
    $options[COMMUNITYFORUM_TRACKING_OFF] = get_string('trackingoff', 'communityforum');
    $options[COMMUNITYFORUM_TRACKING_FORCED] = get_string('trackingon', 'communityforum');
    $settings->add(new admin_setting_configselect('communityforum_trackingtype', get_string('trackingtype', 'communityforum'),
                       get_string('configtrackingtype', 'communityforum'), COMMUNITYFORUM_TRACKING_OPTIONAL, $options));

    // Default whether user needs to mark a post as read
    $settings->add(new admin_setting_configcheckbox('communityforum_trackreadposts', get_string('trackforum', 'communityforum'),
                       get_string('configtrackreadposts', 'communityforum'), 1));

    // Default whether user needs to mark a post as read.
    $settings->add(new admin_setting_configcheckbox('communityforum_allowforcedreadtracking', get_string('forcedreadtracking', 'communityforum'),
                       get_string('forcedreadtracking_desc', 'communityforum'), 0));

    // Default number of days that a post is considered old
    $settings->add(new admin_setting_configtext('communityforum_oldpostdays', get_string('oldpostdays', 'communityforum'),
                       get_string('configoldpostdays', 'communityforum'), 14, PARAM_INT));

    // Default whether user needs to mark a post as read
    $settings->add(new admin_setting_configcheckbox('communityforum_usermarksread', get_string('usermarksread', 'communityforum'),
                       get_string('configusermarksread', 'communityforum'), 0));

    $options = array();
    for ($i = 0; $i < 24; $i++) {
        $options[$i] = sprintf("%02d",$i);
    }
    // Default time (hour) to execute 'clean_read_records' cron
    $settings->add(new admin_setting_configselect('communityforum_cleanreadtime', get_string('cleanreadtime', 'communityforum'),
                       get_string('configcleanreadtime', 'communityforum'), 2, $options));

    // Default time (hour) to send digest email
    $settings->add(new admin_setting_configselect('digestmailtime', get_string('digestmailtime', 'communityforum'),
                       get_string('configdigestmailtime', 'communityforum'), 17, $options));

    if (empty($CFG->enablerssfeeds)) {
        $options = array(0 => get_string('rssglobaldisabled', 'admin'));
        $str = get_string('configenablerssfeeds', 'communityforum').'<br />'.get_string('configenablerssfeedsdisabled2', 'admin');

    } else {
        $options = array(0=>get_string('no'), 1=>get_string('yes'));
        $str = get_string('configenablerssfeeds', 'communityforum');
    }
    $settings->add(new admin_setting_configselect('communityforum_enablerssfeeds', get_string('enablerssfeeds', 'admin'),
                       $str, 0, $options));

    if (!empty($CFG->enablerssfeeds)) {
        $options = array(
            0 => get_string('none'),
            1 => get_string('discussions', 'communityforum'),
            2 => get_string('posts', 'communityforum')
        );
        $settings->add(new admin_setting_configselect('communityforum_rsstype', get_string('rsstypedefault', 'communityforum'),
                get_string('configrsstypedefault', 'communityforum'), 0, $options));

        $options = array(
            0  => '0',
            1  => '1',
            2  => '2',
            3  => '3',
            4  => '4',
            5  => '5',
            10 => '10',
            15 => '15',
            20 => '20',
            25 => '25',
            30 => '30',
            40 => '40',
            50 => '50'
        );
        $settings->add(new admin_setting_configselect('communityforum_rssarticles', get_string('rssarticles', 'communityforum'),
                get_string('configrssarticlesdefault', 'communityforum'), 0, $options));
    }

    $settings->add(new admin_setting_configcheckbox('communityforum_enabletimedposts', get_string('timedposts', 'communityforum'),
                       get_string('configenabletimedposts', 'communityforum'), 1));
}

