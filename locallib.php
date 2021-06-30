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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	 See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Enrollment refresh is use to synchronize student enrollment with registration office.
 *
 * @package		local_kuenrol
 * @author		Akrapong Patchararungruang
 * @copyright	2015 Kasetsart University
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @changelog	17 June 2016 : Move all functions retrieving data from KU Regis
 *				into a class. Also, support for Sakonnakon is added.
 */


defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/accesslib.php");
require_once("$CFG->libdir/datalib.php");
require_once("$CFG->libdir/enrollib.php");
require_once("$CFG->libdir/grouplib.php");
require_once("$CFG->dirroot/group/lib.php");
require_once("$CFG->dirroot/user/lib.php");

require_once('kuregis.php');

/* Generate log entry */
function ku_log( $string )
{
		file_put_contents("Kuenrol.log",  date('l j F Y H:i:s') . " : " . $string . "\n", FILE_APPEND );
		file_put_contents("/dev/stderr",  date('l j F Y H:i:s') . " : " . $string . "\n", FILE_APPEND );
}
function debug_log( $string )
{
		file_put_contents("Debug.log",  date('l j F Y H:i:s') . " : " . $string . "\n", FILE_APPEND );
}
/*------------------------------------------------------------*/

function csv_to_array( $string )
{
	$lines = explode(PHP_EOL, $string);
	$array = array();
	foreach ($lines as $line)
	{
		if( strlen( $line ) > 0 )
		{
			$array[] = str_getcsv($line);
		}
	}

	return( $array );
}
/*------------------------------------------------------------*/

/**
 * explode_tag : extract tag setting into an array
 * @param string $sTag
 * @return array $aTag
 **/
function explode_tag( $sTag ) {
	$aTag = array();
	
	// Elimitate space in strTag
	$sTrimTag = preg_replace('/\s+/', '', $sTag);
	
	// Tag string should have size more than 2 (for key = value )
	if( strlen( $sTrimTag ) <= 2 )
		return $aTag;
		
	// Explode each key=value pair
	$aKeyVal = explode( ';', $sTrimTag );
	// Parsing each key=value pair
	foreach( $aKeyVal as $sKeyVal ) {
		$aKeyValParts = explode( '=', $sKeyVal );
		
		// Whether the tag string has the correct format
		if( count( $aKeyValParts ) != 2 ) {
			continue;
		}

		$sKey = $aKeyValParts[0];
		$sVal = $aKeyValParts[1];
		// Store the result
		if( strlen( $sVal ) > 0 ) {
			$aTag[$sKey] = explode( ',', $sVal );
		} else {
			$aTag[$sKey] = array();
		}
	}
	
	return $aTag;
}
/*------------------------------------------------------------*/

/**
 * implode_tag : compact tag array into a string
 * @param array $aTag
 * @return string $sTag
 **/
function implode_tag( $aTag ) {
	$sTag = "";

	// No tag, return an empty string
	if( count( $aTag ) == 0 )
		return $sTag;
	
	// Process each tag
	foreach( $aTag as $sKey => $aValue ) {
		// If the tag string already store some values then add ';'
		if( strlen( $sTag ) > 0 ) {
			$sTag = $sTag . ';';
		}
		
		// Process value list
		$sVal = "";
		foreach( $aValue as $sItem ) {
			// Insert value delimiter if some values exist
			if( strlen( $sVal ) > 0 ) {
				$sVal = $sVal . ',';
			}
			$sVal = $sVal . $sItem;
		}
		$sTag = $sTag . $sKey . '=' . $sVal;
	}
	
	return $sTag;
}
/*------------------------------------------------------------*/

/**
 * Checking if the provided user name is (likely) a student.
 * - Students should not be able to export any grade book.
 * @param integer $nUserId
 * @return bool. (True if the user is likely a student. False otherwise.)
 */
function is_student( $nUserId ) {
	global $COURSE;
	
	$xCourseContext = context_course::instance( $COURSE->id );
	
	return( !has_capability( 'moodle/grade:export', $xCourseContext, $nUserId ) );
}
/*------------------------------------------------------------*/

/**
 * Returns a Nontri account of the student with the providing ID
 *
 * @param string $sStudentID - A valid student ID for KU in the form of 
 *			YYCLMMRRRR where each character should be a digit.
 * @return string|bool - A nontri account of the parameter is valid. false otherwise.
 **/
function ku_gen_username( $sStudentID ) {
	/* The 4th digit from the left of the student ID can be used to identify 
	student level. Letter '0' - '3' means undergraduate, letter '4' - '6' means
	master level, and letter '7' - '9' means doctoral level. */
	
	// Check student ID format
	if( strlen( $sStudentID ) != 10 ) {
		return false;
	}
	if( !ctype_digit( $sStudentID ) ) {
		return false;
	}
	
	$nKeyDigit = intval( $sStudentID[3] ); // Get the key digit
	if( $nKeyDigit <= 3 ) {
		return( 'b' . $sStudentID );
	} else {
		return( 'g' . $sStudentID );
	}
}
/*------------------------------------------------------------*/

/**
 * This function translates CSV data into an array of objects representing
 * students who currently enrolled to the class. Its operation is very
 * hard coded according to KU Regis format.

 * @param string $sCsvStream - The whole CSV file from KU Regis in a string
 * @param string $sSecName - Name of the current section in process.
 * @return array of Objects index by student ID
 **/

function csv_to_student_list( $sCsvStream, $sSecName )
{
	$aasStdRawList = csv_to_array( $sCsvStream );
	$axStdList = array();
	foreach( $aasStdRawList as $asStdRawData ) {
		/* KU Regis format
		   0 => index number
		   1 => course number
		   2 => student id
		   3 => student fullname (in Thai)
		   4 => student major id
		   5 => registration type ("C" or "A" for Credit or Audit)
		   6 => student status ("W" = withdrew, "" = enrolled)
		*/
		if( strlen( $asStdRawData[6] ) == 0 ) {
			$xStudent = new stdClass();
			$xStudent->nId = false; // Dummy data for future
			
			$xStudent->sStudentID = $asStdRawData[2];	// Get student id
			$xStudent->sUserName = ku_gen_username( $asStdRawData[2] );
			
			$nSpacePos = mb_strpos( $asStdRawData[3], ' ', 0, 'UTF-8');
			$xStudent->sFirstName = mb_substr( $asStdRawData[3], 0, $nSpacePos, 'UTF-8' ); // Store first name
			$xStudent->sLastName = mb_substr( $asStdRawData[3], $nSpacePos + 1, null, 'UTF-8' ); // Store last name
			
			$xStudent->asGroupList = array();
			$xStudent->asGroupList[] = $sSecName;
			
			$axStdList[$xStudent->sStudentID] = $xStudent;
		}
	}
	return( $axStdList );
}
/*------------------------------------------------------------*/

/**
 * Create an account for a student with the given information.
 * The authentication method is set to LDAP by default
 *
 * @param Object $xStrudentInfo
 * @return false|int id of newly created user. false if error
 **/
function create_moodle_user( $xStudentInfo ) {
	global $CFG;
	
	// If the username is invalid
	if( false === $xStudentInfo->sUserName ) {
		return false;
	}

ku_log( "Create user : ". $xStudentInfo->sUserName );
	
	$xUser = new stdClass();
	
	// Setting fields from $xStudentInfo
	$xUser->username = $xStudentInfo->sUserName;
	$xUser->firstname = $xStudentInfo->sFirstName;
	$xUser->lastname = $xStudentInfo->sLastName;
	$xUser->email = $xStudentInfo->sUserName . '@ku.ac.th';
	$xUser->idnumber = $xStudentInfo->sStudentID;
	
	// Setting remaining fields with default values
	$xUser->confirmed	 = 1;
	$xUser->mnethostid	 = $CFG->mnet_localhost_id;;
	$xUser->timemodified = time();
	$xUser->timecreated	 = time();
	$xUser->suspended = 0;
	$xUser->auth = 'oauth2'; // ldap
	$xUser->lang = '';
	$xUser->password = AUTH_PASSWORD_NOT_CACHED;
	
	return( user_create_user( $xUser, false ) );
}
/*------------------------------------------------------------*/

/**
 * Returns a subset of users
 *
 * @global object
 * @uses DEBUG_DEVELOPER
 * @uses SQL_PARAMS_NAMED
 * @param bool $get If false then only a count of the records is returned
 * @param string $search A simple string to search for
 * @param string $fields A comma separated list of fields to be returned from the chosen table.
 * @param string $sort A SQL snippet for the sorting criteria to use
 * @return array|int|bool  {@link $USER} records unless get is false in which case the integer count of the records found is returned.
 *						  False is returned if an error is encountered.
 */
function ku_get_users($get=true, $search='', $fields='*', $sort='username ASC' ) {
	global $DB, $CFG;
 
	$select = " id <> :guestid AND deleted = 0";
	$params = array('guestid'=>$CFG->siteguest);
   
	if (!empty($search)){
		$search = trim($search);
		$select .= " AND (".$DB->sql_like('idnumber', ':search1', false)." OR ".$DB->sql_like('email', ':search2', false)." OR ".$DB->sql_like('username', ':search3', false).")";
		$params['search1'] = "%$search%";
		$params['search2'] = "%$search%";
		$params['search3'] = "%$search";
	}

	if ($get) {
		return $DB->get_records_select('user', $select, $params, $sort, $fields, '', '2');
	} else {
		return $DB->count_records_select('user', $select, $params);
	}
}
/*------------------------------------------------------------*/

/**
 * Fill moodle userid field for each student.
 * If any username within the list does not exist, that name is skipped or created.
 * @param array $axStudents
 * @param boolean $bAutoCreate
 * @return nothing
 */
function find_students_userid( &$axStudents, $bAutoCreate = false) {
	foreach( $axStudents as $sStudentID=>&$xStudentInfo ) {
		/* get_users searches the database for:
			- idnumber LIKE %$sUserName%
			- email LIKE %$sUserName%
			- useranme like %$sUserName
		Therefore, the pattern covers all possible Nontri accounts with only
		student ID as the key. Therefore, $sUserName canbe a full Nontri account
		or a student ID. */
		$xUserRecords = ku_get_users( true, $sStudentID, 'id, username' );
		
		// If username does not exists then a new user record should be created!!!
		if( $xUserRecords === false ) {
			if( $bAutoCreate ) {
				$xStudentInfo->nId = create_moodle_user( $xStudentInfo );
			}
		} else {
			$xUserRecordData = array_values( $xUserRecords );
			if( count( $xUserRecordData ) < 1 ) {
				if( $bAutoCreate ) {
					$xStudentInfo->nId = create_moodle_user( $xStudentInfo );
				}
			} else {
				$xStudentInfo->nId = $xUserRecordData[0]->id;
				$xStudentInfo->sUserName = $xUserRecordData[0]->username;
			}
		}
	}
}
/*------------------------------------------------------------*/

/**
 * Retrieve the names of unselected groups in the course. All students belong to
 * these groups will be untouched.
 * @param array $aGroups in the form of Lect:Lab without 'S' prefix (Becareful)
 * @param $bIncludeOld to include students from old course plan.
 * @return array of id of the groups that are not in the parameter.
 */
function get_unlist_groups( $aGroups, $sCampus, $bIncludeOld ) {
	global $COURSE;

	// Generate group name with 'S' prefix
	$aListedGroups = array();
	foreach( $aGroups as $sGroupName ) {
		$aListedGroups[] = 'S' . $sCampus . $sGroupName;
		if( $bIncludeOld ) {
			$aListedGroups[] = 'S' . $sCampus . '_o' . $sGroupName;
		}
	}
	
	$xCourseContext = context_course::instance( $COURSE->id );

	// Fristly, we get all groups in the course and mark them as unlisted.
	// Then, we substract two arrays
	
	// Get all groups in the course
	$aGroupObjsInCourse = groups_get_all_groups( $COURSE->id );
	// Generate array of group ids
	$aUnlistedGroupIDs = array();
	foreach( $aGroupObjsInCourse as $xGroupObj ) {
		if( ! in_array( $xGroupObj->name, $aListedGroups ) ) {
			$aUnlistedGroupIDs[] = $xGroupObj->id;
		}
	}
	
	// return subtracted arrays
	
	return( $aUnlistedGroupIDs );
}
/*------------------------------------------------------------*/

/**
 * Perform enrollment action (enrol new users and suspend/withdraw missing users).
 * @param array $axUsers - The array of student information indexed by student id.
 * @param array $aUnlistedGroupIDs - The array of group ids which are excluded from processing.
 * @param class $xManuallEnrolInstance - The instance of 'manual' enrollment plugin in class-context.
 * @param class $xSelflEnrollInstance - The instance of 'self' enrollment plugin in class-context.
 * @param integer $nRoleId
 * @param string $sMissingAct - action for users missing from the imported data.
 * @return none.
 */
function enroll_action( $axUsers, $aUnlistedGroupIDs,  $xManualEnrollInstance, $xSelfEnrollInstance, $nRoleId, $sMissingAct ) {
	global $COURSE;

ku_log( "Enrollment action");

	$xCourseContext = context_course::instance( $COURSE->id );
	$xSystemManualEnrol = enrol_get_plugin( 'manual' );
	$xSystemSelfEnrol = enrol_get_plugin( 'self' );
	
	// Enroll new users
	if ( ( $nRoleId > 0 ) && ( $xManualEnrollInstance != null ) ) {
		foreach( $axUsers as $sStudentID=>$xStudentInfo ) {
			if( false === $xStudentInfo->nId ) {
				continue;	// Skip non existing user
			}
			if( !is_enrolled( $xCourseContext, $xStudentInfo->nId ) ) {
ku_log( "Enrol " . $xStudentInfo->sUserName );
				$xSystemManualEnrol->enrol_user( $xManualEnrollInstance, $xStudentInfo->nId, $nRoleId, time() );
			}
		}
	}

	// Withdraw/Suspend unlisted users
	if( $sMissingAct != 'nothing' ) {
		$xEnrolUsers = get_enrolled_users( $xCourseContext, '', 0, 'u.id, u.username' );

		// Build userid of the members of unlisted group
		$anExcludedUserID = array();
		foreach( $aUnlistedGroupIDs as $nUnlistedID ) {
			$axGroupMembers = groups_get_members( $nUnlistedID, 'u.id, u.username' );
			foreach( $axGroupMembers as $xGroupMember ) {
				if( !in_array( $xGroupMember->id, $anExcludedUserID ) ) {
					$anExcludedUserID[] = $xGroupMember->id;
				}
			}
		}
		
		foreach( $xEnrolUsers as $xEnrolPerson ) {
			// If the interesting student is not a student. Then skip
			if( ! is_student( $xEnrolPerson->id ) ) {
				continue;
			}

			// If the interesting student is member of an unselected group. Then skip.
			if( in_array( $xEnrolPerson->id, $anExcludedUserID ) ) {
				continue;
			}
			
			// Process suspension or withdrawing only to students.
			// $axUsers is indexed by student id
			if( !array_key_exists( substr( $xEnrolPerson->username, 1 ), $axUsers ) ) {
				if( $sMissingAct == 'suspend' ) {
ku_log( "Suspend user " . $xEnrolPerson->username );
					if( $xManualEnrollInstance != null ) {
						$xSystemManualEnrol->update_user_enrol( $xManualEnrollInstance, $xEnrolPerson->id, 1 );
					}
					if( $xSelfEnrollInstance != null ) {
						$xSystemSelfEnrol->update_user_enrol( $xSelfEnrollInstance, $xEnrolPerson->id, 1 );
					}
				} else {
ku_log( "Withdraw user " . $xEnrolPerson->username );
					if( $xManualEnrollInstance != null ) {
						$xSystemManualEnrol->unenrol_user( $xManualEnrollInstance, $xEnrolPerson->id );
					}
					if( $xSelfEnrollInstance != null ) {
						$xSystemSelfEnrol->unenrol_user( $xSelfEnrollInstance, $xEnrolPerson->id );
					}
				}
			}
		}
	}
}
/*------------------------------------------------------------*/

/**
 * Perform group-change action.
 * @param array $axUsers - The array of student information indexed by student id.
 * @param array $aUnlistedGroupIDs - The array of group ids which are excluded from processing.
 * @param integer $autogroupcreate - Whether new group should be created if required?
 * @param integer $autogroupwithdraw - Whether users should be withdrawn from the group not specified in imported data?
 * @return none.
 */
function group_action( $axUsers, $aUnlistedGroupIDs, $bAutoGroupCreate, $bAutoGroupWithdraw ) {
	global $COURSE;

ku_log( "Group action" );

	$xCourseContext = context_course::instance( $COURSE->id );

	foreach( $axUsers as $sStudentID=>$xStudentInfo ) {
		if( false === $xStudentInfo->nId ) {
			// Skip missing user. All users must already enrolled here.
			continue;
		}
		if( !is_enrolled( $xCourseContext, $xStudentInfo->nId ) ) {
			// skip unenrolled users as enrol_action should finish all enrollment
			continue;
		}

		// Add to group
		foreach( $xStudentInfo->asGroupList as $sGroupName ) {
			// Prepare course group
			$nGid = groups_get_group_by_name( $COURSE->id, $sGroupName );
			if( false ===  $nGid ) {
				// If group not exists, should we create it?
				if ( $bAutoGroupCreate ) {
					// Create a group
ku_log( "Create new group with name " . $sGroupName );
					$newgroupdata = new stdClass();
					$newgroupdata->name = $sGroupName;
					$newgroupdata->courseid = $COURSE->id;
					$newgroupdata->description = '';
					$nGid = groups_create_group($newgroupdata);
					if (!$nGid) {
						// Fail to create group
						continue; // Skip this group entry.
					}
				} else {
					// We don't have the specified group and teacher does not
					// want to create it. We cannot do anything now. Just skip
					// this group entry.
					continue;
				}
			}

			// Add user to group
			if ( !groups_is_member( $nGid, $xStudentInfo->nId ) ) {
ku_log( "Invoke user " . $xStudentInfo->sUserName . " into " . $sGroupName );
				groups_add_member( $nGid, $xStudentInfo->nId );
			}
		}

		// Withdraw from unlisted groups if required
		if( $bAutoGroupWithdraw && is_student( $xStudentInfo->nId ) ) {
			// Get current groups that the user already in
			$axEnrolledGroups = groups_get_all_groups( $COURSE->id, $xStudentInfo->nId );

			// Iterate
			foreach( $axEnrolledGroups as $xEnrolledGroup ) {
				if( ( !in_array( $xEnrolledGroup->id, $aUnlistedGroupIDs) ) && 
					( !in_array( $xEnrolledGroup->name, $xStudentInfo->asGroupList ) ) ) {
					$bStatus = groups_remove_member( $xEnrolledGroup->id, $xStudentInfo->nId );
if( $bStatus ) {
	ku_log( "Revoke user " . $xStudentInfo->sUserName . " from " . $xEnrolledGroup->name . " (Success)" );
} else {
	ku_log( "Revoke user " . $xStudentInfo->sUserName . " from " . $xEnrolledGroup->name . " (FAIL!!)" );
}
				}
			}
		}
	}
}
/*------------------------------------------------------------*/

/**
 * Perform retrieving all students of a specific course id.
 * @param object $xKURegis pointed to active Regis platform
 * @param string $courseid
 * @param int $year - last 2 digits of B.E.
 * @param int $semester - 0 = Summer, 1 = First, 2 = Second, 3 = Third
 * @param array $aGroups for each group to include
 * @param string $sCampus - Campus suffix (B = Bangkhen, etc...)
 * @param array $axStudents to append the data to.
 * @return array of the information of all student within the course.
 */
function get_students( $xKURegis, $sCourseID, $sYear, $sSem, $aGroups, $sCampus, $axStudents ) {
	
	// Get section list in the course
	$xKURegis->set_active_course( $sCourseID, $sYear, $sSem );
	$aRegisSections = $xKURegis->get_sec_list();

	//file_put_contents( '/tmp/moodletxt', var_export( $aRegisSections, true ) . "\n" );
	ku_log( "=== Start on " . $COURSE->shortname . " ===" );

	// Build data for enrollment and group-switch action. Both acctions can accept
	// either username (Nontri account) or idnumer as the key.
	//$aStdIDList = array();
	//$aStdIDGroupList = array();
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
		$xCsvStdList = $xKURegis->get_students( $sLectSec, $sLabSec );
		// Extract KU Regis output of a sections to a list of student id
		// Check whether this sCourseID indicates the course in old course plan
		// and set the group suffix according to its value
		if( $sCourseID{0} == '9' ) {
			$axStudentInSec = csv_to_student_list( $xCsvStdList, 'S' . $sCampus . '_o' . $sGroupName );
		} else {
			$axStudentInSec = csv_to_student_list( $xCsvStdList, 'S' . $sCampus . $sGroupName );
		}
	
		// Merge student within a section into the whole course student list.
		foreach( $axStudentInSec as $sStudentID=>$xStudentData ) {
			if( array_key_exists( $sStudentID, $axStudents ) ) {
				// The student already exist then add his/her section into the list
				$axStudents[ $sStudentID ]->asGroupList[] = array_merge( 
					$axStudents[ $sStudentID ]->asGroupList, $xStudentData->asGroupList );
			} else {
				// The student does not exist yet so we add the new record
				// A new student is always create by "new stdClass()" so we don't
				// need to worry about deep copy here
				$axStudents[ $sStudentID ] = $xStudentData;
			}
		}
	}
	
	return( $axStudents );
}


/*------------------------------------------------------------*/

//$tag = 'id=01204224;year=57;sem=2;sec=0:11,0:12';
//$expTag = explode_tag( $tag );
//$impTag = implode_tag( $expTag );
//var_dump( $impTag );
