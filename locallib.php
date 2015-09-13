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

require_once("$CFG->libdir/accesslib.php");
require_once("$CFG->libdir/datalib.php");
require_once("$CFG->libdir/enrollib.php");
require_once("$CFG->libdir/grouplib.php");
require_once("$CFG->dirroot/group/lib.php");
require_once("$CFG->dirroot/user/lib.php");

/* Generate log entry */
function ku_log( $string )
{
        file_put_contents("Kuenrol.log",  date('l j F Y H:i:s') . " : " . $string . "\n", FILE_APPEND );
}
/*------------------------------------------------------------*/

/* Function to convert TIS620 (ISO-8859-11) to UTF8
Since, PHP does not natively support TIS620, we manually create it */
function iso8859_11toUTF8( $string )
{
	if ( ! preg_match("/[\241-\377]/", $string) )
		return $string;

	$iso8859_11 = array(
			"\xa1" => "\xe0\xb8\x81", 
			"\xa2" => "\xe0\xb8\x82",
			"\xa3" => "\xe0\xb8\x83",
			"\xa4" => "\xe0\xb8\x84",
			"\xa5" => "\xe0\xb8\x85",
			"\xa6" => "\xe0\xb8\x86",
			"\xa7" => "\xe0\xb8\x87",
			"\xa8" => "\xe0\xb8\x88",
			"\xa9" => "\xe0\xb8\x89",
			"\xaa" => "\xe0\xb8\x8a",
			"\xab" => "\xe0\xb8\x8b",
			"\xac" => "\xe0\xb8\x8c",
			"\xad" => "\xe0\xb8\x8d",
			"\xae" => "\xe0\xb8\x8e",
			"\xaf" => "\xe0\xb8\x8f",
			"\xb0" => "\xe0\xb8\x90",
			"\xb1" => "\xe0\xb8\x91",
			"\xb2" => "\xe0\xb8\x92",
			"\xb3" => "\xe0\xb8\x93",
			"\xb4" => "\xe0\xb8\x94",
			"\xb5" => "\xe0\xb8\x95",
			"\xb6" => "\xe0\xb8\x96",
			"\xb7" => "\xe0\xb8\x97",
			"\xb8" => "\xe0\xb8\x98",
			"\xb9" => "\xe0\xb8\x99",
			"\xba" => "\xe0\xb8\x9a",
			"\xbb" => "\xe0\xb8\x9b",
			"\xbc" => "\xe0\xb8\x9c",
			"\xbd" => "\xe0\xb8\x9d",
			"\xbe" => "\xe0\xb8\x9e",
			"\xbf" => "\xe0\xb8\x9f",
			"\xc0" => "\xe0\xb8\xa0",
			"\xc1" => "\xe0\xb8\xa1",
			"\xc2" => "\xe0\xb8\xa2",
			"\xc3" => "\xe0\xb8\xa3",
			"\xc4" => "\xe0\xb8\xa4",
			"\xc5" => "\xe0\xb8\xa5",
			"\xc6" => "\xe0\xb8\xa6",
			"\xc7" => "\xe0\xb8\xa7",
			"\xc8" => "\xe0\xb8\xa8",
			"\xc9" => "\xe0\xb8\xa9",
			"\xca" => "\xe0\xb8\xaa",
			"\xcb" => "\xe0\xb8\xab",
			"\xcc" => "\xe0\xb8\xac",
			"\xcd" => "\xe0\xb8\xad",
			"\xce" => "\xe0\xb8\xae",
			"\xcf" => "\xe0\xb8\xaf",
			"\xd0" => "\xe0\xb8\xb0",
			"\xd1" => "\xe0\xb8\xb1",
			"\xd2" => "\xe0\xb8\xb2",
			"\xd3" => "\xe0\xb8\xb3",
			"\xd4" => "\xe0\xb8\xb4",
			"\xd5" => "\xe0\xb8\xb5",
			"\xd6" => "\xe0\xb8\xb6",
			"\xd7" => "\xe0\xb8\xb7",
			"\xd8" => "\xe0\xb8\xb8",
			"\xd9" => "\xe0\xb8\xb9",
			"\xda" => "\xe0\xb8\xba",
			"\xdf" => "\xe0\xb8\xbf",
			"\xe0" => "\xe0\xb9\x80",
			"\xe1" => "\xe0\xb9\x81",
			"\xe2" => "\xe0\xb9\x82",
			"\xe3" => "\xe0\xb9\x83",
			"\xe4" => "\xe0\xb9\x84",
			"\xe5" => "\xe0\xb9\x85",
			"\xe6" => "\xe0\xb9\x86",
			"\xe7" => "\xe0\xb9\x87",
			"\xe8" => "\xe0\xb9\x88",
			"\xe9" => "\xe0\xb9\x89",
			"\xea" => "\xe0\xb9\x8a",
			"\xeb" => "\xe0\xb9\x8b",
			"\xec" => "\xe0\xb9\x8c",
			"\xed" => "\xe0\xb9\x8d",
			"\xee" => "\xe0\xb9\x8e",
			"\xef" => "\xe0\xb9\x8f",
			"\xf0" => "\xe0\xb9\x90",
			"\xf1" => "\xe0\xb9\x91",
			"\xf2" => "\xe0\xb9\x92",
			"\xf3" => "\xe0\xb9\x93",
			"\xf4" => "\xe0\xb9\x94",
			"\xf5" => "\xe0\xb9\x95",
			"\xf6" => "\xe0\xb9\x96",
			"\xf7" => "\xe0\xb9\x97",
			"\xf8" => "\xe0\xb9\x98",
			"\xf9" => "\xe0\xb9\x99",
			"\xfa" => "\xe0\xb9\x9a",
			"\xfb" => "\xe0\xb9\x9b"
		);

	$string=strtr($string,$iso8859_11);
	return $string;
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

/* 
CAUTION: We require to use RegisGetSecList of the same course in the same
semester and academic year before using this function. Otherwise, the KU Regis
will not generate student-listing file.

Parameter hints:
$year				: last 2 digits of B.E.
$semester			: 1 = First, 2 = Second, 3 = Third, 4 = Summer, 5 = Summer2
$grp_lect/$grp_lab	: 0 if not available
*/
function regis_get_students( $courseid, $year, $semester, $grp_lect, $grp_lab, $cookies )
{
	$req_opt = sprintf( 'Sm=%s&Yr=%s&TCs_Code=%s&TLec=%s&TLab=%s', 
						 $semester, $year, $courseid, $grp_lect, $grp_lab );
	$trig_url = 'https://regis.ku.ac.th/class_cscode.php?' . $req_opt;

    $csv_url = sprintf( 'https://regis.ku.ac.th/grade/download_file/class_%s_%s%s.txt',
    				 	$courseid, $year, $semester);


	/* Initialize curl */
	$ch = curl_init();

	/* Set cookie */
	curl_setopt( $ch, CURLOPT_COOKIE, $cookies );

	/* Disable verifying server SSL certificate */
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

	/* Get all html result into a variable for parsing cookies */
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

	/*==== First step: Trig the server to generate csv data ====*/
	/* Set URL */
	curl_setopt( $ch, CURLOPT_URL, $trig_url );
	curl_setopt( $ch, CURLOPT_REFERER, 'https://regis.ku.ac.th/query_cscode.php' );
	
	/* Execute URL request */
	$result = curl_exec( $ch );

	/*==== Second step: Load the generated csv data ====*/
	/* Set URL */
	curl_setopt( $ch, CURLOPT_URL, $csv_url );
	curl_setopt( $ch, CURLOPT_REFERER, 'https://regis.ku.ac.th/query_cscode.php' );
	
	/* Execute URL request. */
	$result = curl_exec( $ch );

	/* Close curl */
	curl_close( $ch );

	/* KU-Regis gives ISO8859-11-encodeded text, so convert it to utf8 */
	$result_utf8 = iso8859_11toUTF8( $result );
	
	/* Check for <title>404 Not Found</title> for unknown course */
	if( false !== strpos( $result_utf8, '<title>404 Not Found</title>' ) ) {
		return( '' );
	} else {
		return( $result_utf8 );
	}
}
/*------------------------------------------------------------*/

/* 
Parameter hints:
$year				: last 2 digits of B.E.
$semester			: 1 = First, 2 = Second, 3 = Third, 4 = Summer
*/
function regis_get_sec_list( $courseid, $year, $semester, $cookies )
{
	/* ---- Step 1: Retrieve section list as an HTML document from Regis --- */
    $aData = array(	'qCs_Code' => $courseid,
					'qYr' => $year,
					'qSm'=> $semester );

	/* Initialize curl */
	$ch = curl_init();
	
	/* Set URL */
	curl_setopt( $ch, CURLOPT_URL, 'https://regis.ku.ac.th/query_cscode.php' );
	curl_setopt( $ch, CURLOPT_REFERER, 'https://regis.ku.ac.th/registration_report_menu.php' );

	/* Set query data */
	curl_setopt( $ch, CURLOPT_POST, true );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $aData );

	/* Set cookie */
	curl_setopt( $ch, CURLOPT_COOKIE, $cookies );

	/* Disable verifying server SSL certificate */
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

	/* Get all html result into a variable for parsing cookies */
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );


	/* Execute URL request */
	$result = curl_exec( $ch );

	/* Close curl */
	curl_close( $ch );
	
	/* If http request fail, return empty group array */
	
	if( false === $result ) {
		return( array() );
	}
	
	/* KU-Regis gives ISO8859-11-encodeded text, so convert it to utf8 */
	$result_utf8 = iso8859_11toUTF8( $result );
	/* Check for <title>404 Not Found</title> for unknown course */
	if( false !== strpos( $result_utf8, '<title>404 Not Found</title>' ) ) {
		return( array() );
	}
	
	/* --- Step 2: Parse retrieved HTML document for the section list --- */

	/* Store HTML document in DOM */
	$dom = new DOMDocument;
	@$dom->loadHTML( $result_utf8 ); /* Suppress ill-formed html warning */

	$tables = $dom->getElementsByTagName( 'table' );

	/* This part is a dirty hard-code. We assume that there must be
	3 <table>-tags. The last <table> must be the section list */
	$sec_table = $tables->item( 2 );
	$sec_rows = $sec_table->childNodes;
	
	/* Section list starts from row 1 (counting from 0 ) */
	$sec_array = array();
	for( $i = 1; $i < $sec_rows->length; $i++ )
	{
		if( $sec_rows->item( $i )->hasChildNodes() )
		{
			$sec_items = $sec_rows->item( $i )->childNodes;
			// Lecture:Lab
			$sec_data = $sec_items->item( 3 )->nodeValue . ':' . $sec_items->item( 4 )->nodeValue;
			$sec_array[] = $sec_data;
		}
	}

	return( $sec_array );
}
/*------------------------------------------------------------*/

/**
 * regis_login : Login to KU Regis system
 * @param string $username
 * @param string $password
 * @param string $campus
 * @return string - The cookie string used for further requests. (empty string if fail)
 **/
function regis_login( $username, $password, $campus )
{
	$data = array(	'UserName' => $username, 
					'Password' => $password,
					'Campus' => $campus );

	/* Initialize curl */
	$ch = curl_init();

	/* Set URL */
	curl_setopt( $ch, CURLOPT_URL, 'https://regis.ku.ac.th/login.php' );

	/* Set login data */
	curl_setopt( $ch, CURLOPT_POST, true );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );

	/* Disable verifying server SSL certificate */
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

	/* Get all html result into a variable for parsing cookies */
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_HEADER, true );

	/* Execute URL request */
	$result = curl_exec( $ch );
	
	/* Close curl */
	curl_close( $ch );

	/* If login fail, return empty cookie */
	if( false === $result ) {
		return '';
	}

	/* get cookie
	   multi-cookie variant contributed by @Combuster in comments
	   Server may create duplicated cookie entries, this step overwrites
	   the values of duplicated cookies with the latest ones */
	preg_match_all( '/^Set-Cookie:\s*([^;\s]*)/mi', $result, $matches );
	$cookies = array();
	foreach( $matches[1] as $item )
	{
    	parse_str( $item, $cookie );
		$cookies = array_merge( $cookies, $cookie );
	}

	/* Create cookie string for next curl. All cookies are set to their
	   latest value */
	$cookies_text = '';
	foreach($cookies as $key=>$value)
	{
		if( strlen( $cookies_text ) > 0 )
		{
			$cookies_text = $cookies_text . ';';
		}
		$cookies_text = $cookies_text . $key . '=' . $value;
	}
	
	return( $cookies_text );
}
/*------------------------------------------------------------*/

function regis_logout( $cookies )
{
	/* Initialize curl */
	$ch = curl_init();

	/* Set URL */
	curl_setopt( $ch, CURLOPT_URL, 'https://regis.ku.ac.th/logout.php' );

	/* Set cookie */
	curl_setopt( $ch, CURLOPT_COOKIE, $cookies );

	/* Disable verifying server SSL certificate */
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

	/* Get all html result into a variable for parsing cookies */
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_HEADER, true );

	/* Execute URL request */
	$result = curl_exec( $ch );
	
	return( $result );
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
 * - Students should not be able to edit any grade book.
 * @param integer $nUserId
 * @return bool. (True if the user is likely a student. False otherwise.)
 */
function is_student( $nUserId ) {
	global $COURSE;
	
    $xCourseContext = context_course::instance( $COURSE->id );
    
    return( !has_capability( 'moodle/grade:edit', $xCourseContext, $nUserId ) );
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

function csv_to_stdudent_list( $sCsvStream, $sSecName )
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
		   5 => student status ("W" = withdrew, "" = enrolled)
		*/
		if( strlen( $asStdRawData[5] ) == 0 ) {
			$xStudent = new stdClass();
			$xStudent->nId = false;	// Dummy data for future
			
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
	$xUser->confirmed    = 1;
	$xUser->timemodified = time();
	$xUser->timecreated  = time();
	$xUser->suspended = 0;
	$xUser->auth = 'ldap';
	$xUser->lang = '';
	$xUser->password = AUTH_PASSWORD_NOT_CACHED;
	
	return( user_create_user($xUser, false, false) );
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
 *                        False is returned if an error is encountered.
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
function find_students_userid( $axStudents, $bAutoCreate = false) {
	foreach( $axStudents as $sStudentID=>$xStudentInfo ) {
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
 * @return array of id of the groups that are not in the parameter.
 */
function get_unlist_groups( $aGroups ) {
    global $COURSE;

	// Generate group name with 'S' prefix
	$aListedGroups = array();
	foreach( $aGroups as $sGroupName ) {
		$aListedGroups[] = 'S' . $sGroupName;
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
 * @param class $xManualEnrolInstance - The instance of 'manual' enrollment plugin in class-context.
 * @param integer $nRoleId
 * @param string $sMissingAct - action for users missing from the imported data.
 * @return none.
 */
function enroll_action( $axUsers, $aUnlistedGroupIDs,  $xManualEnrolInstance, $nRoleId, $sMissingAct ) {
    global $COURSE;

ku_log( "Enrollment action");

    $xCourseContext = context_course::instance( $COURSE->id );
    $xSystemManualEnrol = enrol_get_plugin( 'manual' );
	
    // Enroll new users
    if ( $nRoleId > 0 ) {
        foreach( $axUsers as $sStudentID=>$xStudentInfo ) {
        	if( false === $xStudentInfo->nId ) {
        		continue;	// Skip non existing user
        	}
            if( !is_enrolled( $xCourseContext, $xStudentInfo->nId ) ) {
ku_log( "Enrol " . $xStudentInfo->sUserName );
                $xSystemManualEnrol->enrol_user( $xManualEnrolInstance, $xStudentInfo->nId, $nRoleId );
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
                    $xSystemManualEnrol->update_user_enrol( $xManualEnrolInstance, $xEnrolPerson->id, 1 );
                } else {
ku_log( "Withdraw user " . $xEnrolPerson->username );
                    $xSystemManualEnrol->unenrol_user( $xManualEnrolInstance, $xEnrolPerson->id );
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
ku_log( "Revoke user " . $xStudentInfo.sUserName . " from " . $xEnrolledGroup->name );
                    groups_remove_member( $xEnrolledGroup->id, $xStudentInfo->nId );
                }
            }
        }
    }
}
/*------------------------------------------------------------*/


//$tag = 'id=01204224;year=57;sem=2;sec=0:11,0:12';
//$expTag = explode_tag( $tag );
//$impTag = implode_tag( $expTag );
//var_dump( $impTag );
