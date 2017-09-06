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
 * Definition of log events
 *
 * @package    mod_communityforum
 * @category   log
 * @copyright  2010 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $DB; // TODO: this is a hack, we should really do something with the SQL in SQL tables.

$logs = array(
    array('module' => 'communityforum', 'action' => 'add', 'mtable' => 'communityforum', 'field' => 'name'),
    array('module' => 'communityforum', 'action' => 'update', 'mtable' => 'communityforum', 'field' => 'name'),
    array('module' => 'communityforum', 'action' => 'add discussion', 'mtable' => 'communityforum_discussions', 'field' => 'name'),
    array('module' => 'communityforum', 'action' => 'add post', 'mtable' => 'communityforum_posts', 'field' => 'subject'),
    array('module' => 'communityforum', 'action' => 'update post', 'mtable' => 'communityforum_posts', 'field' => 'subject'),
    array('module' => 'communityforum', 'action' => 'user report', 'mtable' => 'user',
          'field'  => $DB->sql_concat('firstname', "' '", 'lastname')),
    array('module' => 'communityforum', 'action' => 'move discussion', 'mtable' => 'communityforum_discussions', 'field' => 'name'),
    array('module' => 'communityforum', 'action' => 'view subscribers', 'mtable' => 'communityforum', 'field' => 'name'),
    array('module' => 'communityforum', 'action' => 'view discussion', 'mtable' => 'communityforum_discussions', 'field' => 'name'),
    array('module' => 'communityforum', 'action' => 'view communityforum', 'mtable' => 'communityforum', 'field' => 'name'),
    array('module' => 'communityforum', 'action' => 'subscribe', 'mtable' => 'communityforum', 'field' => 'name'),
    array('module' => 'communityforum', 'action' => 'unsubscribe', 'mtable' => 'communityforum', 'field' => 'name'),
    array('module' => 'communityforum', 'action' => 'pin discussion', 'mtable' => 'communityforum_discussions', 'field' => 'name'),
    array('module' => 'communityforum', 'action' => 'unpin discussion', 'mtable' => 'communityforum_discussions', 'field' => 'name'),
);