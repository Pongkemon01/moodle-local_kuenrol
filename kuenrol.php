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

require_once('../../config.php');
require_once("$CFG->dirroot/local/kuenrol/locallib.php");
require_once('kuenrol_form.php');

/********************* Step 1: Preprocessing **********************/

// Want this for subsequent print_error() calls
$sCourseURL = new moodle_url("{$CFG->wwwroot}/course/view.php", array('id' => $COURSE->id));
$sGroupsURL = new moodle_url("{$CFG->wwwroot}/group/index.php", array('id' => $COURSE->id));

// Ensure user privillege
// Fetch the course id from query string
$course_id = required_param('id', PARAM_INT);

// No anonymous access for this page, and this will
// handle bogus course id values as well
require_login($course_id);
// $PAGE, $USER, $COURSE, and other globals now set
// up, check the capabilities (we need Manual Enrollment module)
require_capability('enrol/manual:enrol', $PAGE->context);

// Iterate the list of active enrol plugins looking for
// the manual course plugin.
// The enrollment retrieved here is the one that can be seen by this course.
// Don't confuse with the enrollment plugins enabled in system level.
$nDefaultRoleID = 0;
$xManualEnrollInstance = null;
$aEnrolsEnabled = enrol_get_instances($COURSE->id, true);
foreach( $aEnrolsEnabled as $enrol ) {
    if( $enrol->enrol == 'manual' ) {
        $xManualEnrollInstance = $enrol;
        $nDefaultRoleID = $enrol->roleid;
        break;
    }
}

// Extract course information from tags
if( isset( $COURSE->idnumber ) ) {
	$sCourseTag = $COURSE->idnumber;
} else {
	$sCourseTag = '';
}

if( strlen( $sCourseTag ) > 0 ) {
	$aTag = explode_tag( $sCourseTag );
} else {
	$aTag = array();
}

// Check whether we are at stage 1 or 2
$sCookie = optional_param( 'cookies', '', PARAM_RAW );
$sCourseID = optional_param( 'courseid', '00000000', PARAM_RAW );
$sSem = optional_param( 'sem', '1', PARAM_RAW );
$sYear = optional_param( 'year', '57', PARAM_RAW );

// Set page environment
$sPageHeadTitle = get_string( 'pluginname', local_kuenrol_form1::$pluginname ) . ' : ' . $COURSE->shortname;

$PAGE->set_title($sPageHeadTitle);
$PAGE->set_heading($sPageHeadTitle);
$PAGE->set_pagelayout('incourse');
$PAGE->set_url($CFG->wwwroot . '/local/kuenrol/kuenrol.php?id=' . $COURSE->id);
$PAGE->set_cacheable(false);

/*----------------------------------------------------------------*/

/***************** Step 2: Collect KU Regis data ******************/
if( strlen( $sCookie ) == 0 ) {
	$xForm1 = new local_kuenrol_form1( null, 
										array( 'tag' => $aTag, 'id' => $COURSE->id ) );
 
	// Form processing and displaying is done here
	if ( $xForm1->is_cancelled() ) {
		//Handle form cancel operation, if cancel button is present on form
	    redirect($sCourseURL);
    
	} else if ( $xForm1Data = $xForm1->get_data() ) {
		// In this case you process validated data. $xForm1->get_data()
		// returns data posted in form.

    	// First, check session spoofing
	    require_sesskey();
	    
	    $sCourseID = $xForm1Data->sCourseId;
	    $sYear = $xForm1Data->sYear;
	    $sSem = strval( $xForm1Data->nSem );
    
	    // Cleaning all numeric field because Moodle always trim the leading zero
   		if( strlen( $sCourseID ) < 8 ) {
   			do {
   				$sCourseID = '0'. $sCourseID;
	    	}while( strlen( $sCourseID ) < 8 );
   		}
	    if( strlen( $sYear ) < 2 ) {
  			do {
				$sYear= '0'. $sYear;
		   	}while( strlen( $sYear ) < 2 );
   		}

		// Login to KU Regis
		$sCookie = regis_login( $xForm1Data->sAccount, $xForm1Data->sPassword, $xForm1Data->sCampus );
		if( strlen( $sCookie ) == 0 ) {
			// Cannot login. Display error and redirect
			print_error( 'Fail to login to KU Regis', '', $sCourseURL );
		}
	
		// Get section list in the course
		$aRegisSections = regis_get_sec_list( $sCourseID, $sYear, $sSem, $sCookie );
		if( count( $aRegisSections ) <= 0 ) {
			// No section available
			print_error( 'This course is not available in the selected semester', '', $sCourseURL );
		}
		
		// All are good. Continue to the next step below this if-else
  
	} else {
		// this branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
		// or on the first display of the form.
  		echo $OUTPUT->header();
		//displays the form
		$xForm1->display();
		echo $OUTPUT->footer();
		die;	// Just display the form and don't go any further
	}
} else {
	// We just come back so we should retrieve group list again from cookies data
	// sent via POST
	
    // Cleaning all numeric field because Moodle always trim the leading zero
   	if( strlen( $sCourseID ) < 8 ) {
   		do {
   			$sCourseID = '0'. $sCourseID;
    	}while( strlen( $sCourseID ) < 8 );
   	}
    if( strlen( $sYear ) < 2 ) {
  		do {
			$sYear= '0'. $sYear;
	   	}while( strlen( $sYear ) < 2 );
   	}

	// Get section list in the course
	$aRegisSections = regis_get_sec_list( $sCourseID, $sYear, $sSem, $sCookie );
	if( count( $aRegisSections ) <= 0 ) {
		// No section available
		print_error( 'This course is not available in the selected semester', '', $sCourseURL );
	}
}
/*----------------------------------------------------------------*/

/************** Step 3: Prepare data for next step ****************/
// We require manual enrollment actions
$bCanAdd = false;
$bCanDrop = false;

if( enrol_is_enabled( 'manual' ) ) {
	$bCanDrop = true;
	$bCanAdd = true;
}

// We require ldap authentication
$aEnabledAuths = get_enabled_auth_plugins();
if( in_array( 'ldap', $aEnabledAuths ) ) {
	$bCanCreate = true;
} else {
	$bCanCreate = false;
}

/*----------------------------------------------------------------*/
/**************** Step 4: Collect desired actions *****************/
$xForm2 = new local_kuenrol_form2( null,
									array( 'tag' => $aTag, 'regisgrp' => $aRegisSections,
									'canadd' => $bCanAdd, 'candrop' => $bCanDrop,
									'cancreate' => $bCanCreate,
									'default_roleid' => $nDefaultRoleID,
									'courseid' => $sCourseID, 'sem' => $sSem, 'year' => $sYear,
									'cookies' => $sCookie, 'id' => $COURSE->id ) );
 
// Form processing and displaying is done here
if ( $xForm2->is_cancelled() ) {
	//Handle form cancel operation, if cancel button is present on form
    redirect($sCourseURL);	

} else if ( $xForm2Data = $xForm2->get_data() ) {
	// In this case you process validated data. $xForm2->get_data()
	// returns data posted in form.

    // First, check session spoofing
    require_sesskey();

	if( isset( $xForm2Data->aGroups ) && is_array( $xForm2Data->aGroups ) ) {
		$aGroups = $xForm2Data->aGroups;
	} else {
		$aGroups = array();
	}
	if( isset( $xForm2Data->sDropAction ) ) {
		$sDropAction = trim( $xForm2Data->sDropAction );
	} else {
		$sDropAction = 'nothing';
	}
	$nRoleID = intval( $xForm2Data->nRoleID );
	$bAutoGroup = $xForm2Data->bAutoGroup;
	$bAutoRevoke = $xForm2Data->bAutoRevoke;
	
	// Preparation completed. Continue to process the data
	
} else {
	// this branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
	// or on the first display of the form.
  	echo $OUTPUT->header();
	//displays the form
	$xForm2->display();
	echo $OUTPUT->footer();

	die;	// Just display the form and don't go any further
}
/*----------------------------------------------------------------*/

/********************** Step 5: Processing ************************/

// Process based on section

// This line is to prevent KU Regis bug which requires a section listing
// before allowing to retrieve student data
$aRegisSections = regis_get_sec_list( $sCourseID, $sYear, $sSem, $sCookie );

//file_put_contents( '/tmp/moodletxt', var_export( $aRegisSections, true ) . "\n" );

// Build data for enrollment and group-switch action. Both acctions can accept
// either username (Nontri account) or idnumer as the key.
$aStdIDList = array();
$aStdIDGroupList = array();	
foreach( $aGroups as $sGroupName ) {
	// Split group name into lecture and lab section id.
	$aGroupPair = explode( ':', $sGroupName );

	if( isset( $aGroupPair[0] ) ) {
		$sLectSec = $aGroupPair[0];
	} else {
		$sLectSec = 0;
	}

	if( isset( $aGroupPair[1] ) ) {
		$sLabSec = $aGroupPair[1];
	} else {
		$sLabSec = 0;
	}
	
	// Retrive data from KU Regis in CSV format
	$xCsvStdList = regis_get_students( $sCourseID, $sYear,
									   $sSem, $sLectSec, $sLabSec, $sCookie );
	// Extract KU Regis output to a list of student id
	$aStudents = csv_to_stdudent_list( $xCsvStdList );

	// Process each student
	foreach( $aStudents as $xStudentInfo ) {
		if( !in_array( $xStudentInfo->StudentID, $aStdIDList ) ) {
			$aStdIDList[] = $xStudentInfo->StudentID;
		}
		if( !array_key_exists( $xStudentInfo->StudentID, $aStdIDGroupList ) ) {
			$aStdIDGroupList[$xStudentInfo->StudentID] = array();
		}
		//array_push( $aStdIDGroupList[$sStdID], ( "S" . $sGroupName ) );
		$aStdIDGroupList[$xStudentInfo->StudentID][] = 'S' . $sGroupName;
	}
}

// We don't need KU Regis now. Log out.
regis_logout( $sCookie );

// Perform enrollment level action
if ($xManualEnrollInstance != null && ($nRoleID > 0 || $sDropAction != 'nothing')) {
    enroll_action($aStdIDList, $xManualEnrollInstance, $nRoleID, $sDropAction);
}

group_action($aStdIDGroupList, $bAutoGroup, $bAutoRevoke);

// Typically you finish up by redirecting to somewhere where the user
// can see what they did.
redirect( $sCourseURL );

/*----------------------------------------------------------------*/
  
