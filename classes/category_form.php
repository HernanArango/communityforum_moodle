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
 * File containing the form definition to post in the forum.
 *
 * @package   mod_forum
 * @copyright Jamie Pratt <me@jamiep.org>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/repository/lib.php');


/**
 * Class to post in a forum.
 *
 * @package   mod_forum
 * @copyright Jamie Pratt <me@jamiep.org>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_communityforum_category_form extends moodleform {

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {
        global $CFG, $OUTPUT, $DB;

        $mform =& $this->_form;
        $forum = $this->_customdata['forum'];
        $category = $this->_customdata['category'];
        $edit = $this->_customdata['edit'];
        $delete = $this->_customdata['delete'];

    if(!$edit){
        $mform->addElement('text', 'name', get_string('name_category', 'communityforum'), 'size="48"');
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');


        $mform->addElement('textarea', 'introduction', get_string('description_category', 'communityforum'), 'wrap="virtual" rows="5" cols="80"');

        $mform->addElement('select', 'id_parent_category', get_string('parent_category', 'communityforum'), communityforum_get_categories($forum,$category));

        $mform->addElement('hidden', 'edit');
        $mform->setType('edit', PARAM_INT);

        $mform->addElement('hidden', 'delete');
        $mform->setType('delete', PARAM_INT);

        $mform->addElement('hidden', 'forum');
        $mform->setType('forum', PARAM_INT);
        $mform->setDefault('forum', $forum);

        $submit_string = "Guardar Categoria";
        $this->add_action_buttons(true, $submit_string);
        
    }
    else{

        $category_to_edit = $DB->get_record('communityforum_categories', array('id' => $category));

        $mform->addElement('text', 'name', get_string('name_category', 'communityforum'), 'size="48"');
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->setDefault('name', $category_to_edit->name_category);

        $mform->addElement('textarea', 'introduction', get_string('description_category', 'communityforum'), 'wrap="virtual" rows="5" cols="80"');
        $mform->setDefault('introduction', $category_to_edit->description);
        $mform->addElement('select', 'id_parent_category', get_string('parent_category', 'communityforum'), communityforum_get_categories($forum,$category));
        
        $mform->setDefault('id_parent_category', $category_to_edit->parent_category);


        $mform->addElement('hidden', 'edit');
        $mform->setType('edit', PARAM_INT);
        $mform->setDefault('edit', 1);

        $mform->addElement('hidden', 'delete');
        $mform->setType('delete', PARAM_INT);

        $mform->addElement('hidden', 'forum');
        $mform->setType('forum', PARAM_INT);
        $mform->setDefault('forum', $forum);

        $mform->addElement('hidden', 'category');
        $mform->setType('category', PARAM_INT);
        $mform->setDefault('category', $category);

        $submit_string = "Actualizar Categoria";
        $this->add_action_buttons(true, $submit_string);
    }

    
        //-------------------------------------------------------------------------------
        // buttons
        
        
        /*
        $mform->addElement('hidden', 'course');
        $mform->setType('course', PARAM_INT);

        $mform->addElement('hidden', 'forum');
        $mform->setType('forum', PARAM_INT);

        $mform->addElement('hidden', 'edit');
        $mform->setType('edit', PARAM_INT);
    }
*/
    /**
     * Form validation
     *
     * @param array $data data from the form.
     * @param array $files files uploaded.
     * @return array of errors.
     */
    /*
    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (($data['timeend']!=0) && ($data['timestart']!=0) && $data['timeend'] <= $data['timestart']) {
            $errors['timeend'] = get_string('timestartenderror', 'forum');
        }
        if (empty($data['message']['text'])) {
            $errors['message'] = get_string('erroremptymessage', 'forum');
        }
        if (empty($data['subject'])) {
            $errors['subject'] = get_string('erroremptysubject', 'forum');
        }
        return $errors;
        */
    }
}