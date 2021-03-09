<?php
// This file is a part of Kasetsart Moodle Kit - https://github.com/Pongkemon01/moodle-local_kuenrol
//
// Kasetsart Moodle Kit is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Kasetsart Moodle Kit is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Enrollment refresh is use to synchronize student enrollment with registration office.
 *
 * @package     local_kuenrol
 * @author      Akrapong Patchararungruang
 * @copyright   2015 Kasetsart University
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");
require_once("$CFG->libdir/accesslib.php");

/**
 * Form1 definition for the plugin
 *
 */
class local_kuenrol_form1 extends moodleform {

    /**
     * @access private
     * @pluginname string
     */
    public static $pluginname = 'local_kuenrol';

    /**
     * Define the form's contents
     *
     */
    public function definition() {
        global $PAGE;

		$xForm = $this->_form;
		// Retrieve course tag from passed-in data
		$aTag = $this->_customdata['tag'];

		//$xForm->addElement( 'header', 'title', get_string( 'pluginname', self::$pluginname ) );
		$xForm->addElement( 'header', 'header', get_string( 'f1_header', self::$pluginname ) );
		
		// Form body
		
		// Login information
		$xForm->addElement( 'text', 'sAccount', get_string( 'f1_account', self::$pluginname ) );
		$xForm->setType( 'sAccount', PARAM_RAW_TRIMMED );
        $xForm->addRule('sAccount', null, 'required', null, 'client');

		$xForm->addElement( 'password', 'sPassword', get_string( 'f1_password', self::$pluginname ) );
        $xForm->addRule('sPassword', null, 'required', null, 'client');

		$aCampus = array(
			'B' => get_string( 'f1_bangken', self::$pluginname ),
			'K' => get_string( 'f1_kumpangsaen', self::$pluginname ),
			'R' => get_string( 'f1_sriracha', self::$pluginname ),
			'S' => get_string( 'f1_sakon', self::$pluginname ),
		);
		$xForm->addElement( 'select', 'sCampus', get_string( 'f1_campus', self::$pluginname ), $aCampus );
		$xForm->setDefault( 'sCampus', 'B' );

		$xForm->addElement( 'html', '<hr>' );
		
		// Course information
		$xForm->addElement( 'text', 'sCourseId', get_string( 'f1_courseid', self::$pluginname ) );
		$xForm->setType( 'sCourseId', PARAM_INT );
        $xForm->addRule( 'sCourseId', null, 'required', null, 'client');
        $xForm->addRule( 'sCourseId', null, 'maxlength', '8', 'client');
		if( array_key_exists( 'id', $aTag ) ) {
			$xForm->setDefault( 'sCourseId', $aTag['id'][0] );
		}

		$xForm->addElement( 'text', 'sYear', get_string( 'f1_year', self::$pluginname ) );
		$xForm->setType( 'sYear', PARAM_INT );
        $xForm->addRule('sYear', null, 'required', null, 'client');
        $xForm->addRule('sYear', null, 'minlength', '2', 'client');
        $xForm->addRule('sYear', null, 'maxlength', '2', 'client');
		if( array_key_exists( 'year', $aTag ) ) {
			$xForm->setDefault( 'sYear', $aTag['year'][0] );
		}

		/* Reassign sem code to:
			0 => Summer
			1 => First
			2 => Second
			3 => Third
			4 => Summer(Forestry and Vet)
			5 => Summer 2
		*/
		$aSemester = array (
			0 => get_string( 'f1_sem_summer', self::$pluginname ),
			1 => get_string( 'f1_sem_first', self::$pluginname ),
			2 => get_string( 'f1_sem_second', self::$pluginname ),
			3 => get_string( 'f1_sem_third', self::$pluginname ),
			4 => get_string( 'f1_sem_summer_fv', self::$pluginname ),
			5 => get_string( 'f1_sem_summer2', self::$pluginname ),
		);
		$xForm->addElement( 'select', 'nSem', get_string( 'f1_semester', self::$pluginname ), $aSemester );
		if( array_key_exists( 'sem', $aTag ) ) {
			$xForm->setDefault( 'nSem', intval( $aTag['sem'][0] ) );
		} else {
			$xForm->setDefault( 'nSem', 1 );
		}

		// hidden elements		
        $xForm->addElement('hidden', 'id');
        $xForm->setType('id', PARAM_INT);
        $xForm->setDefault( 'id', $this->_customdata['id'] );

		// Foot buttons
		$aButtons = array();
		$aButtons[] = &$xForm->createElement( 'submit', 'nextbutton', get_string( 'btn_next', self::$pluginname ) );
		$aButtons[] = &$xForm->createElement( 'cancel', 'cancelbutton', get_string( 'btn_cancel', self::$pluginname ) );
		$xForm->addGroup( $aButtons, 'footbtn', '', array( ' ' ), false );
	} // definition
	
} // Class

/**
 * Form2 definition for the plugin
 *
 */
class local_kuenrol_form2 extends moodleform {

    /**
     * @access private
     * @pluginname string
     */
    public static $pluginname = 'local_kuenrol';

    /**
     * Define the form's contents
     *
     */
    public function definition() {
        global $PAGE;

		$xForm = $this->_form;
		// Retrieve course tag from passed-in data
		$aTag = $this->_customdata['tag'];
		// Retrieve group listed from KU Regis
		$aRegisGrp = $this->_customdata['regisgrp'];

		// Form body
		$xForm->addElement( 'header', 'unused', get_string( 'f2_header', self::$pluginname ) );

		$aSecList = array();
		$aSecSelectList = array();
		foreach( $aRegisGrp as $sGroup ) {
			$aSecList[$sGroup] = $sGroup;
			if( array_key_exists( 'sec', $aTag ) ) {
				if( in_array( $sGroup, $aTag['sec'], true ) ) {
					$aSecSelectList[] = $sGroup;
				}
			}
		}
		$xSelect = $xForm->addElement( 'select', 'aGroups', get_string( 'f2_grplist', self::$pluginname ), $aSecList );
		$xSelect->setMultiple( true );
		$xSelect->setSelected( $aSecSelectList );
		
		$xForm->addElement( 'header', 'unused', get_string( 'f2_choices', self::$pluginname ) );
		
        // The role id drop down list. The get_assignable_roles returns an assoc. array
        // with integer keys (role id) and role name values, so it looks like a sparse
        // array. The php array functions tend to reorder the keys to remove the perceived
        // gaps, so have to merge manually with the 0 option.
        $roles = HTML_QuickForm::arrayMerge(array(0 => get_string('f2_noroleid', self::$pluginname)),
                                            get_assignable_roles($PAGE->context, ROLENAME_BOTH));
        $xForm->addElement('select', 'nRoleID', get_string('f2_roleid', self::$pluginname), $roles);
		if( !( $this->_customdata['canadd'] ) ) {
	        $xForm->setDefault('nRoleID', 0 ); // Cannot enrol so choosing "No Enrollments"
			$xForm->updateElementAttr( 'nRoleID', 'disabled' );
		} else {
	        $xForm->setDefault( 'nRoleID', $this->_customdata['default_roleid'] );
	    }

		$aDropAction = array (
			'nothing' => get_string( 'f2_missing_none', self::$pluginname ),
			'suspend' => get_string( 'f2_missing_susp', self::$pluginname ),
			'remove' => get_string( 'f2_missing_del', self::$pluginname ),
		);
		$xForm->addElement( 'select', 'sDropAction', get_string( 'f2_missingaction', self::$pluginname ), $aDropAction );
		$xForm->setDefault( 'sDropAction', 'remove' );
		if( !( $this->_customdata['candrop'] ) ) {
			$xForm->updateElementAttr( 'sDropAction', 'disabled' );
		}
				
		$xForm->addElement( 'advcheckbox', 'bAutoGroup', get_string( 'f2_distribute', self::$pluginname ),
							'', array( 'group' => 1 ), array( false, true ) );
		$xForm->setDefault( 'bAutoGroup', true );

		$xForm->addElement( 'advcheckbox', 'bAutoRevoke', get_string( 'f2_revoke', self::$pluginname ),
							'', array( 'group' => 2 ), array( false, true ) );
		$xForm->setDefault( 'bAutoRevoke', true );
		
		$xForm->addElement( 'advcheckbox', 'bIncludeOld', 'Include users from previous course plan',
							'', array( 'group' => 3 ), array( false, true ) );
		$xForm->setDefault( 'bIncludeOld', true );

		// hidden fields
        $xForm->addElement('hidden', 'id');
        $xForm->setType('id', PARAM_INT);
        $xForm->setDefault( 'id', $this->_customdata['id'] );

        $xForm->addElement('hidden', 'cookies');
        $xForm->setType('cookies', PARAM_RAW);
        $xForm->setDefault( 'cookies', $this->_customdata['cookies'] );

        $xForm->addElement('hidden', 'campus');
        $xForm->setType('campus', PARAM_RAW);
        $xForm->setDefault( 'campus', $this->_customdata['campus'] );

        $xForm->addElement('hidden', 'courseid');
        $xForm->setType('courseid', PARAM_RAW);
        $xForm->setDefault( 'courseid', $this->_customdata['courseid'] );

        $xForm->addElement('hidden', 'sem');
        $xForm->setType('sem', PARAM_RAW);
        $xForm->setDefault( 'sem', $this->_customdata['sem'] );

        $xForm->addElement('hidden', 'year');
        $xForm->setType('year', PARAM_RAW);
        $xForm->setDefault( 'year', $this->_customdata['year'] );
		
		// Foot buttons
		$aButtons = array();
		$aButtons[] = &$xForm->createElement( 'submit', 'startbutton', get_string( 'btn_start', self::$pluginname ) );
		$aButtons[] = &$xForm->createElement( 'cancel', 'cancelbutton', get_string( 'btn_cancel', self::$pluginname ) );
		$xForm->addGroup( $aButtons, 'footbtn', '', array( ' ' ), false );
	} // definition
		
} // Class
		
