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

require_once("$CFG->dirroot/local/kuenrol/locallib.php");
abstract class KuRegis
{
	/* Properties */
	protected	$sCookies = '';
	protected	$sCourseID = '';
	protected	$sSemesterCode = '';
	protected	$sAcademicYear = '';
	
	/* Abstract methods */
	abstract protected function login(  $username, $password, $campus );
	abstract protected function logout();
	abstract protected function get_sec_list();
	abstract protected function get_students( $grp_lect, $grp_lab );
	
	/* General methods */
	function __construct( $sPreCookies = '' )
	{
		$this->sCookies = $sPreCookies;
	}

	/*
	 * set_active_course : Set parameters of the working course
	 * @param string $courseid
	 * @param int $year - last 2 digits of B.E.
	 * @param int $semester - 0 = Summer, 1 = First, 2 = Second, 3 = Third
	**/
	public function set_active_course( $courseid, $year, $semester )
	{
		$this->sCourseID = $courseid;
		$this->sAcademicYear = $year;
		$this->sSemesterCode = $semester;
	}

	public function get_cookies() 
	{
		return( $this->sCookies );
	}

	/* Function to convert TIS620 (ISO-8859-11) to UTF8
	Since, PHP does not natively support TIS620, we manually create it */
	public function iso8859_11toUTF8( $string )
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

}
/*------------------------------------------------------------*/

/* Class for Regis database in Bangken and Kampaengsan */

class CentralRegis extends KuRegis
{
	/**************** Protected local methods ***************/
	
	/********************* Public methods *******************/	
	/**
	 * login : Login to KU Regis system
	 * @param string $username
	 * @param string $password
	 * @param string $campus
	 * @return string - The cookie string used for further requests. (empty string if fail)
	 **/
	public function login( $username, $password, $campus )
	{
		$data = array(	'UserName' => $username, 
						'Password' => $password,
						'Campus' => $campus );
						
		/* Clear old cookies */
		$this->sCookies = '';

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
		$aRawCookies = array();
		foreach( $matches[1] as $item )
		{
			parse_str( $item, $cookie );
			$aRawCookies = array_merge( $aRawCookies, $cookie );
		}

		/* Create cookie string for next curl. All cookies are set to their
		   latest value */
		foreach($aRawCookies as $key=>$value)
		{
			if( strlen( $this->sCookies ) > 0 )
			{
				$this->sCookies = $this->sCookies . ';';
			}
			$this->sCookies = $this->sCookies . $key . '=' . $value;
		}
	
		return( $this->sCookies );
	}
	/*------------------------------------------------------------*/
	
	function logout()
	{
		/* We did not login to Regis before */
		if( strlen( $this->sCookies ) == 0 )
		{
			return( true );
		}
		
		/* Initialize curl */
		$ch = curl_init();

		/* Set URL */
		curl_setopt( $ch, CURLOPT_URL, 'https://regis.ku.ac.th/logout.php' );

		/* Set cookie */
		curl_setopt( $ch, CURLOPT_COOKIE, $this->sCookies );

		/* Disable verifying server SSL certificate */
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

		/* Get all html result into a variable for parsing cookies */
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_HEADER, true );

		/* Execute URL request */
		$result = curl_exec( $ch );
	
		/* Clear previous cookies */
		$sCookies = '';
		
		return( $result );
	}
	/*------------------------------------------------------------*/

	/**
	 * get_sec_list : Retrieve section list fro the Regis system
	 * @return array - Array of section number of the specified course in the specified year and semester
	**/
	public function get_sec_list( )
	{
	
		/* We did not login to Regis before */
		if( strlen( $this->sCookies ) == 0 )
		{
			return( array() );
		}
		
		/* We must set the parameters of the active course first */
		if( ( strlen( $this->sCourseID ) == 0 ) || 
			( strlen( $this->sAcademicYear ) == 0 ) ||
		    ( strlen( $this->sSemesterCode ) == 0 ) )
		{
			return( array() );
		}
		
		/* ---- Step 1: Retrieve section list as an HTML document from Regis --- */
		$aData = array( 'qCs_Code' => $this->sCourseID,
						'qYr' => $this->sAcademicYear,
						'qSm'=> $this->sSemesterCode );

		/* Initialize curl */
		$ch = curl_init();
	
		/* Set URL */
		curl_setopt( $ch, CURLOPT_URL, 'https://regis.ku.ac.th/query_cscode.php' );
		curl_setopt( $ch, CURLOPT_REFERER, 'https://regis.ku.ac.th/registration_report_menu.php' );

		/* Set query data */
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $aData );

		/* Set cookie */
		curl_setopt( $ch, CURLOPT_COOKIE, $this->sCookies );

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
		$result_utf8 = parent::iso8859_11toUTF8( $result );
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
	
		/* The $sec_table must be a valid DOM object, aka. a valid set of table rows. */
		if( !is_object( $sec_table ) ) {
			return( array() );
		}
	
		$sec_rows = $sec_table->childNodes;
		/* Section list starts from row 1 (counting from 0 ) */
		$sec_array = array();
		for( $i = 2; $i < $sec_rows->length; $i++ )
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
	 * get_students : Retrieve students in a sepecified section
	 * @param int $grp_lect - Lecture section (0 if no lecture for this course)
	 * @param int $grp_lab - Lab section (0 if no lab for this course)
	 * @return string - CSV-formatted string according to KU Regis

	KU Regis format
	   0 => index number
	   1 => course number
	   2 => student id
	   3 => student fullname (in Thai)
	   4 => student major id
	   5 => student status ("W" = withdrew, "" = enrolled)

	CAUTION: We require to use RegisGetSecList of the same course in the same
	semester and academic year before using this function. Otherwise, the KU Regis
	will not generate student-listing file.
	**/
	function get_students( $grp_lect, $grp_lab )
	{
		/* We did not login to Regis before */
		if( strlen( $this->sCookies ) == 0 )
		{
			return( '' );
		}

		/* We must set the parameters of the active course first */
		if( ( strlen( $this->sCourseID ) == 0 ) || 
			( strlen( $this->sAcademicYear ) == 0 ) ||
		    ( strlen( $this->sSemesterCode ) == 0 ) )
		{
			return( '' );
		}
		
		$req_opt = sprintf( 'Sm=%s&Yr=%s&TCs_Code=%s&TLec=%s&TLab=%s', 
							 $this->sSemesterCode, $this->sAcademicYear, $this->sCourseID, $grp_lect, $grp_lab );
		$trig_url = 'https://regis.ku.ac.th/class_cscode.php?' . $req_opt;

		$csv_url = sprintf( 'https://regis.ku.ac.th/grade/download_file/class_%s_%s%s.txt',
							$this->sCourseID, $this->sAcademicYear, $this->sSemesterCode);
		/* Initialize curl */
		$ch = curl_init();

		/* Set cookie */
		curl_setopt( $ch, CURLOPT_COOKIE, $this->sCookies );

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
		$result_utf8 = parent::iso8859_11toUTF8( $result );
	
		/* Check for <title>404 Not Found</title> for unknown course */
		if( false !== strpos( $result_utf8, '<title>404 Not Found</title>' ) ) {
			return( '' );
		} else {
			return( $result_utf8 );
		}
	}
	/*------------------------------------------------------------*/
}
/*------------------------------------------------------------*/

/* Class for Regis database in Sakonnakon */

class SakonRegis extends KuRegis
{
	/* Internal section list mapped with course defined year */
	protected $aSecList = array();
	
	/**************** Protected local methods ***************/
	
	/********************* Public methods *******************/	
	/**
	 * login : Login to KU Regis system
	 * @param string $username
	 * @param string $password
	 * @param string $campus
	 * @return string - The cookie string used for further requests. (empty string if fail)
	 **/
	public function login( $username, $password, $campus )
	{
		/* We do nothing for Sakon as the data are public */
		$this->sCookies = 'SAKON';
		return( $this->sCookies );
 	}
	/*------------------------------------------------------------*/
	
	function logout()
	{
		/* We do not perform any thing for Sakon campus */
		/* Clear previous cookies */
		$this->sCookies = '';
		
		return( true );
	}
	/*------------------------------------------------------------*/

	/**
	 * get_sec_list : Retrieve section list fro the Regis system
	 * @return array - Array of section number of the specified course in the specified year and semester
	**/
	public function get_sec_list()
	{
		$this->aSecList = array();	/* Clear internal list */

		/* We did not login to Regis before */
		if( ( strlen( $this->sCookies ) == 0 ) || ( $this->sCookies != 'SAKON' ) )
		{
			return( array() );
		}
		
		/* We must set the parameters of the active course first */
		if( ( strlen( $this->sCourseID ) == 0 ) || 
			( strlen( $this->sAcademicYear ) == 0 ) ||
		    ( strlen( $this->sSemesterCode ) == 0 ) )
		{
			return( array() );
		}
		
		/* ---- Step 1: Retrieve section list as an HTML document from Regis --- */
		$req_opt = sprintf( '?tSearch=%s&rSearch=code&year=25%s&sem=%s&submit=ค้นหา',
							 $this->sCourseID, $this->sAcademicYear, $this->sSemesterCode );
		$main_url = 'https://misreg.csc.ku.ac.th/misreg/ku8/index.php';
		$trig_url =  $main_url . $req_opt;

		/* Initialize curl */
		$ch = curl_init();
	
		/* Set URL */
		curl_setopt( $ch, CURLOPT_URL, $trig_url );
		curl_setopt( $ch, CURLOPT_REFERER, $main_url );

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
		$result = str_replace("\t", '', $result); // remove tabs
		$result = str_replace("\n", '', $result); // remove new lines
		$result = str_replace("\r", '', $result); // remove carriage returns
		$result = str_replace("tis-620", 'UTF-8', $result); // Change charset to UTF8
		$result_utf8 = parent::iso8859_11toUTF8( $result );
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
		2 <table>-tags. The last <table> must be the section list */
		$sec_table = $tables->item( 1 );

		/* The $sec_table must be a valid DOM object, aka. a valid set of table rows. */
		if( !is_object( $sec_table ) ) {
			return( array() );
		}
	
		$sec_rows = $sec_table->childNodes;

		/* Section list starts from row 1 (counting from 0 ) */
		$sec_array = array();

		for( $i = 1; $i < $sec_rows->length; $i++ )
		{
			if( $sec_rows->item( $i )->hasChildNodes() )
			{
				$sec_items = $sec_rows->item( $i )->childNodes;

				// Check for "ไม่พบข้อมูล" which has only 1 item
				if( $sec_items->length < 6 )
				{
					// Invalid entry
					continue;
				}

/*
print( "--------\n");
print_r( $sec_items->length );
print( "\n" );
for( $j = 0; $j < $sec_items->length; $j++ )
{
print( '==>' . strval( $j) . "\n");
print_r( $sec_items->item($j) );
print( "\n" );
}
print( "analyze node 1\n");
$dbg = $sec_items->item( 1 )->data;
print( strlen( $dbg->length ) );

print( "\n" );
*/
				// Extract section
				$sSection = $sec_items->item( 6 )->nodeValue;
/*
debug_log("sec_items " . print_r($sec_items, TRUE));
debug_log(" - item0 " . print_r($sec_items->item(0), TRUE));
debug_log(" - item1 " . print_r($sec_items->item(1), TRUE));
debug_log(" - item2 " . print_r($sec_items->item(2), TRUE));
debug_log(" - item3 " . print_r($sec_items->item(3), TRUE));
debug_log(" - item4 " . print_r($sec_items->item(4), TRUE));
debug_log(" - item5 " . print_r($sec_items->item(5), TRUE));
debug_log(" - item6 " . print_r($sec_items->item(6), TRUE));
debug_log(" - item7 " . print_r($sec_items->item(7), TRUE));
debug_log(" - item8 " . print_r($sec_items->item(8), TRUE));
debug_log(" - item9 " . print_r($sec_items->item(9), TRUE));
debug_log(" - item10 " . print_r($sec_items->item(10), TRUE));
debug_log(" - item11 " . print_r($sec_items->item(11), TRUE));
debug_log(" - item12 " . print_r($sec_items->item(12), TRUE));
				
*/
				// Extract course defined year from course name
				// It is in the format "nnnn (yr) (mod)". So, we use explode to extract yr
				$aCourseinfo = explode( "(", $sec_items->item( 5 )->nodeValue );

				$aTemp = explode( ")", $aCourseinfo[ 1 ] );
				$sYr = $aTemp[0];
				
				// Lecture:Lab
				/* Bacause Sakon campus lists only 1 section per line. There is no
				   link between Lect and Lab as Bangken. However, the section number
				   can indicate whether it is Lect or Lab. The section number for Lect
				   is less than 100; while, Lab starts from 100
				*/
				if( intval( $sSection ) < 100 )
				{
					$sec_data = $sSection . ':0';
				}
				else
				{
					$sec_data = '0:' . $sSection;
				}
				
				$sec_array[] = $sec_data;
				$this->aSecList[] = $sYr . '-' . $sec_data;
			}
		}
		return( $sec_array );
	}
	/*------------------------------------------------------------*/

	/**
	 * get_students : Retrieve students in a sepecified section
	 * @param int $grp_lect - Lecture section (0 if no lecture for this course)
	 * @param int $grp_lab - Lab section (0 if no lab for this course)
	 * @return string - CSV-formatted string according to KU Regis

	KU Regis format
	   0 => index number
	   1 => course number
	   2 => student id
	   3 => student fullname (in Thai)
	   4 => student major id
	   5 => student status ("W" = withdrew, "" = enrolled)

	CAUTION: We require to use RegisGetSecList of the same course in the same
	semester and academic year before using this function. Otherwise, the KU Regis
	will not generate student-listing file.
	**/
	function get_students( $grp_lect, $grp_lab )
	{
		/* We did not login to Regis before */
		if( ( strlen( $this->sCookies ) == 0 ) || ( $this->sCookies != 'SAKON' ) )
		{
			return( '' );
		}
		
		/* We must set the parameters of the active course first */
		if( ( strlen( $this->sCourseID ) == 0 ) || 
			( strlen( $this->sAcademicYear ) == 0 ) ||
		    ( strlen( $this->sSemesterCode ) == 0 ) )
		{
			return( '' );
		}
		
		/* We must initialize $aSecList by using get_sec_list first */
		if( count( $this->aSecList ) == 0 )
		{
			return( '' );
		}
		
		/* ---- Prepare the course defined year from previously stored $aSecList ---- */
		$sYr = '';
		$sGroupStr = $grp_lect . ':' . $grp_lab;
		foreach( $this->aSecList as $sSecCombo )
		{
			$aSection = explode( '-', $sSecCombo );
			if( $aSection[1] == $sGroupStr )
			{
				$sYr = $aSection[0];
				break;
			}
		}
		/* If the given sections do not match any previously stored data */
		if( strlen( $sYr ) == 0 )
		{
			return( '' );
		}
		
		/* ---- Step 1: Retrieve student list as an HTML document from Regis --- */
		if( intval( $grp_lect ) == 0 )
		{
			$grp = $grp_lab;
		} 
		else
		{
			$grp = $grp_lect;
		}
		$req_opt = sprintf( 'cs_code=%s&section=%s&yr=%s&sm=%s&year=%s',
							 $this->sCourseID, $grp, $this->sAcademicYear, $this->sSemesterCode, $sYr );
		$main_url = 'https://misreg.csc.ku.ac.th/misreg/ku8/show.php?';
		$trig_url =  $main_url . $req_opt;

		/* Initialize curl */
		$ch = curl_init();
	
		/* Set URL */
		curl_setopt( $ch, CURLOPT_URL, $trig_url );
		curl_setopt( $ch, CURLOPT_REFERER, $main_url );

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

		/* Preprocessing the result */
		$result = str_replace("\t", '', $result); // remove tabs
		$result = str_replace("\n", '', $result); // remove new lines
		$result = str_replace("\r", '', $result); // remove carriage returns
		$result = str_replace("tis-620", 'UTF-8', $result); // Change charset to UTF8
		$result_utf8 = str_replace("\xA7\xB4\xE0\xC3\xD5\xC2\xB9", 'W', $result); // remove งดเรียน
		/* KU-Regis gives ISO8859-11-encodeded text, so convert it to utf8 */
		$result_utf8 = parent::iso8859_11toUTF8( $result_utf8 );
		/* Check for <title>404 Not Found</title> for unknown course */
		if( false !== strpos( $result_utf8, '<title>404 Not Found</title>' ) ) {
			return( '' );
		}
	
		/* --- Step 2: Parse retrieved HTML document for the student list --- */
		$sDrop = parent::iso8859_11toUTF8( 'งดเรียน' );
		/* Store HTML document in DOM */
		$dom = new DOMDocument;
		@$dom->loadHTML( $result_utf8 ); /* Suppress ill-formed html warning */

		$tables = $dom->getElementsByTagName( 'table' );

		/* This part is a dirty hard-code. We assume that there must be
		only 2 <table>-tags. */
		$std_table = $tables->item( 1 );
	
		/* The $sec_table must be a valid DOM object, aka. a valid set of table rows. */
		if( !is_object( $std_table ) ) {
			return( '' );
		}
	
		$std_rows = $std_table->childNodes;
		$sStudentCSV = '';
		for( $i = 1; $i < $std_rows->length; $i++ )
		{
			if( $std_rows->item( $i )->hasChildNodes() )
			{
				$std_items = $std_rows->item( $i )->childNodes;

				// Now $std_items are <td> tags inside each <tr>...</tr>
				// Check for "ไม่พบข้อมูล" which has only 1 item
				if( $std_items->length < 7 )
				{
					// Invalid entry
					continue;
				}
				
				// Extract information and link them in Regis CSV format
				$sCSVLine = $std_items->item( 0 )->nodeValue . ','; // Index
				$sCSVLine = $sCSVLine . $this->sCourseID . ','; // Course id
				$sCSVLine = $sCSVLine . $std_items->item( 1 )->nodeValue . ','; // Student ID
				$sCSVLine = $sCSVLine . $std_items->item( 2 )->nodeValue . ','; // Student name
				$sCSVLine = $sCSVLine . $std_items->item( 3 )->nodeValue . ','; // Major ID
				$sCSVLine = $sCSVLine . ','; // Dummy field used to indicate enrolment type in Bangkhen

				/* Adjust enrollment status */
				$aStdStat = explode( ' ', $std_items->item( 6 )->nodeValue );
				if( $aStdStat[ 0 ] == 'W' )
				{
					$sCSVLine = $sCSVLine . 'W';
				}
				
				$sCSVLine = $sCSVLine . "\n";
				
				/* Combine items into a line of CSV */
				$sStudentCSV = $sStudentCSV . $sCSVLine;
			}
		}

		return( $sStudentCSV );
	}
	/*------------------------------------------------------------*/
}
/*------------------------------------------------------------*/

/* Class for Regis database in Sriracha 
  The class directly connects to Sriracha Regis database. 
  The database engine is MS SQL Server with the configuration as:
  ip: 158.108.102.17
  acc: ocs_ku
  pass: KUcs#58
  db: transcripts
  table: register_01999111
*/


class SrirachaRegis extends KuRegis
{
	/**************** Protected local methods ***************/
	
	/********************* Public methods *******************/	
	/**
	 * login : Login to KU Regis system
	 * @param string $username
	 * @param string $password
	 * @param string $campus
	 * @return string - The cookie string used for further requests. (empty string if fail)
	 **/
	public function login( $username, $password, $campus )
	{
		/* We do nothing for Sakon as the data are public */
		$this->sCookies = 'SRIRACHA';
		return( $this->sCookies );
 	}
	/*------------------------------------------------------------*/
	
	function logout()
	{
		/* We do not perform any thing for Sakon campus */
		/* Clear previous cookies */
		$this->sCookies = '';
		
		return( true );
	}
	/*------------------------------------------------------------*/

	/**
	 * get_sec_list : Retrieve section list fro the Regis system
	 * @return array - Array of section number of the specified course in the specified year and semester
	**/
	public function get_sec_list()
	{
		/* We did not login to Regis before */
		if( ( strlen( $this->sCookies ) == 0 ) || ( $this->sCookies != 'SRIRACHA' ) )
		{
			return( array() );
		}
		
		/* We must set the parameters of the active course first */
		if( ( strlen( $this->sCourseID ) == 0 ) || 
			( strlen( $this->sAcademicYear ) == 0 ) ||
		    ( strlen( $this->sSemesterCode ) == 0 ) )
		{
			return( array() );
		}
		
		/*
		
		select distinct lc_section, lb_section from register_01999111 where CS_CODE='01999111' and SM_SEM='1' and sm_yr='59'
		
		*/
		/* Cleanup */
		if( $this->sSemesterCode == '3' )
		{
			$this->sSemesterCode = '2';
		}

		/* Connect to database server */
		try
		{
			$xDBConn = new PDO ('odbc:SrirachaRegis', 'ocs_ku', 'KUcs#58');
		} 
		catch (PDOException $e)
		{
			ku_log( 'Connection to Sriracha failed: ' . $e->getMessage() );
			return( array() );
		}
		/* Select database */
		$xDBConn->exec( "use transcripts" );

		/* Prepare parameter */
		$sCond = "CS_CODE='" . $this->sCourseID . "' and SM_YR='" . $this->sAcademicYear . "' and SM_SEM='" . $this->sSemesterCode . "'";
		
		/* Get data */
		$xRegisData = $xDBConn->query( "select distinct lc_section, lb_section from [transcripts].dbo.[register_01999111] where " . $sCond );

		/* Parse data */
		$sec_array = array();		
		foreach( $xRegisData as $xRow )
		{
			$sec_array[] = $xRow[ 'lc_section' ] . ':' . $xRow[ 'lb_section' ];
		}
		return( $sec_array );
	}
	/*------------------------------------------------------------*/

	/**
	 * get_students : Retrieve students in a sepecified section
	 * @param int $grp_lect - Lecture section (0 if no lecture for this course)
	 * @param int $grp_lab - Lab section (0 if no lab for this course)
	 * @return string - CSV-formatted string according to KU Regis

	KU Regis format
	   0 => index number
	   1 => course number
	   2 => student id
	   3 => student fullname (in Thai)
	   4 => student major id
	   5 => dummy ( used by Bangkhen );
	   6 => student status ("W" = withdrew, "" = enrolled)

	CAUTION: We require to use RegisGetSecList of the same course in the same
	semester and academic year before using this function. Otherwise, the KU Regis
	will not generate student-listing file.
	
	select distinct cs_code, std_id, name, major, attr from register_01999111 where CS_CODE='01999111' and SM_SEM='1' and sm_yr='59' and LC_SECTION=800 and lB_section=0

	**/
	function get_students( $grp_lect, $grp_lab )
	{
		/* We did not login to Regis before */
		if( ( strlen( $this->sCookies ) == 0 ) || ( $this->sCookies != 'SRIRACHA' ) )
		{
			return( '' );
		}
		
		/* We must set the parameters of the active course first */
		if( ( strlen( $this->sCourseID ) == 0 ) || 
			( strlen( $this->sAcademicYear ) == 0 ) ||
		    ( strlen( $this->sSemesterCode ) == 0 ) )
		{
			return( '' );
		}
		
		/* Connect to database server */
		try
		{
			$xDBConn = new PDO ('odbc:SrirachaRegis', 'ocs_ku', 'KUcs#58');
		} 
		catch (PDOException $e)
		{
			print 'Connection to Sriracha failed: ' . $e->getMessage();
			return( array() );
		}

		/* Prepare parameter */
		$sCond = "CS_CODE='" . $this->sCourseID . "' and SM_YR='" . $this->sAcademicYear . "' and SM_SEM='" . $this->sSemesterCode . "'";
		$sCond = $sCond . " and LC_SECTION='" . $grp_lect . "' and LB_SECTION='" .  $grp_lab . "'";
		$sSql = "select distinct cs_code, std_id, name, major, attr from [transcripts].dbo.[register_01999111] where " . $sCond;
		
		/* Get data */
		$xRegisData = $xDBConn->query( $sSql );
		
		/* Parse data */
		$sStudentCSV = "";
		$nCount = 0;
		foreach( $xRegisData as $xRow )
		{
			$nCount = $nCount + 1;
			// Extract information and link them in Regis CSV format
			$sCSVLine = strval( $nCount ) . ','; // Index
			$sCSVLine = $sCSVLine . $xRow[ "cs_code" ] . ','; // Course id
			$sCSVLine = $sCSVLine . $xRow[ "std_id" ] . ','; // Student ID
			//$sCSVLine = $sCSVLine . parent::iso8859_11toUTF8( $xRow[ "name" ] ) . ','; // Student name
			$sCSVLine = $sCSVLine . $xRow[ "name" ] . ','; // Student name
			$sCSVLine = $sCSVLine . $xRow[ "major" ] . ','; // Major ID
			$sCSVLine = $sCSVLine . ','; // Dummy
			
			/* Adjust enrollment status */
			$sAttr = $xRow[ "attr" ];
			if( $sAttr[0] == 'D' )
			{
				$sCSVLine = $sCSVLine . 'W';
			}
			
			$sCSVLine = $sCSVLine . "\n";
			
			/* Combine items into a line of CSV */
			$sStudentCSV = $sStudentCSV . $sCSVLine;
		}

		return( $sStudentCSV );
	}
	/*------------------------------------------------------------*/

}
/*
//$t = new CentralRegis();
$t = new SrirachaRegis();
$t->login( "fengstp", "daryl.2013", "B" );
$t->set_active_course( "01999111", "59", "1" );
$x = $t->get_sec_list();
$stu = $t->get_students( 800, 0 );
//$stu = $t->get_students( 1, 0 );

print_r($x);
print_r($stu);
//file_put_contents("log2.log", $stu );
*/
