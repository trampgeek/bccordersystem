<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

function authenticate() {
	/*
	 * Called by the pre_controller_constructor hook to authenticate a user via the Joomla website.
	 * Users must be logged in to Joomla before using the database; the user information is picked
	 * up via the Joomla session cookie and used to find user login, name and club contact roles.
	 * On return the global variable $userData has been set as follows:
	 * $userData['userid'] is the logged-in user ID. 0 means not logged in.
	 * $userData['login'] is the login name if logged in
	 * $userData['name'] is the person's name, e.g. 'Richard Lobb'
	 * $userData['hasFullAccess'] is a boolean true IFF this is a logged in
	 * club officer with at least one of the roles listed in the config parameter
	 * full_access_roles (see config.php).
	 * $userData['roles'] is a list of the roles this user has in the club, as
	 * obtained from the Joomla contact_details table.
	 * for ordinary members without other roles.
	 */
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Pragma: no-cache");
	$data = getJoomlaUserData();
	$GLOBALS['userData'] = $data;
	date_default_timezone_set("Pacific/Auckland");
}

function getJoomlaUserData() {
	// This code is stuck together from the appropriate bits of the Joomla
	// source code, mostly to be found in includes/joomla.php. It will probably
	// not be robust in the event of a Joolma version upgrade, in which case it
	// will be necessary to temporarily replace this function with a hack that
	// sets the CI session data to a suitable working value while I/you figure
	// out what to do for the future!

	$joomlaBase = config_item('joomla_base');
	define('_VALID_MOS',1);
	require_once("$joomlaBase/configuration.php");
	require_once("$joomlaBase/includes/joomla.php");
	
	//Joomla Code: $sessionCookieName 	= mosMainFrame::sessionCookieName( );
	$sessionCookieName 	= md5( 'site' . substr($mosConfig_live_site, 7) );  // Skips http://
	$sessionCookie 	= @strval($_COOKIE[$sessionCookieName]);
	//Joomla Code:  $sessionValueCheck 	= mosMainFrame::sessionCookieValue( $sessioncookie );
	$ip	= $_SERVER['REMOTE_ADDR'];
	$browser = $_SERVER['HTTP_USER_AGENT'];
	$GLOBALS['mosConfig_secret'] = $mosConfig_secret;
	$sessionValueCheck = mosHash( $sessionCookie . $ip . $browser );

	$userId = 0;
	$login = '';
	$name = '';
	$email = '';
	$fullAccess = false;
	$roles = array();
	
	$prefix = $mosConfig_dbprefix;
	if ( $sessionCookie && strlen($sessionCookie) == 32 && $sessionCookie != '-') {
		$con = mysql_connect($mosConfig_host, $mosConfig_user, $mosConfig_password);
		$con || die('Could not connect to Joomla database');
		mysql_select_db($mosConfig_db) || die ('Could not open database');
		$result = mysql_query("select * from {$prefix}session where session_id = '$sessionValueCheck'");
		if ($result) {
			$row = mysql_fetch_assoc($result);
			$userId = $row['userid'];
		}
	}
	
	if ($userId != 0) {
		// User is logged into Joomla. Get their name and login name.
		$result = mysql_query(
			"SELECT name, email, username as login
			 FROM {$prefix}users
			 WHERE {$prefix}users.id = '$userId'"
		);
		if (!$result) die("Whoops. Something blew up! Please tell the webmaster");
		
		$row = mysql_fetch_assoc($result);
		$name = $row['name'];
		$login = $row['login'];
		$email = $row['email'];
		
		$result = mysql_query(
                    "SELECT role as Role
                     FROM ctcweb9_ctc.members_roles, ctcweb9_ctc.roles
                     WHERE memberId='$userId'
                     AND members_roles.roleId = roles.id"
                );
                        
	    	//"SELECT con_position as Role
	     	// FROM {$prefix}contact_details
	     	// WHERE user_id = '$userId'"
		
		if (mysql_num_rows($result) > 0) {
			while (($row = mysql_fetch_array($result)) != NULL) {
				$roles[] = strtolower($row['Role']);
			}
		}
		
		$fullAccessRoles = config_item('full_access_roles');
		if (count(array_intersect($roles, $fullAccessRoles)) > 0) {
			$fullAccess = true;
		}
	}
	
	return array('userid' => $userId, 'login' => $login, 'roles' => $roles,
		'hasFullAccess' => $fullAccess, 'email' => $email, 'name' => $name);
}
?>
