<?php

/**
 * ******************************************
 * ** Drupal Secure Download Script **
 * ******************************************
 *
 *  Author: Alex Shepherd <n00bATNOSPAMn00bsys0p.co.uk>
 *  License: GPL2 (http://www.gnu.org/licenses/gpl-2.0.html)
 *
 *  README: You will need to create a 404 error page called 404.php,
 *                as the script includes it if the file requested does not exist
 *                on the server as expected.
 **/
 
/**
 *  ***************
 *  ** checkAuth **
 *  ***************
 *
 *  Check whether the user is currently logged into
 *  Drupal.
 *
 *  return  bool  True if user has cookies which match
 *      the Drupal database.
 **/
function checkAuth() {
  // Initialise function variables
  $sessioncookie = '';
  $uid = '';
  $username = '';
  $cookies = $_COOKIE;
 
  // Read in Drupal settings file
  include('../sites/default/settings.php');
  $dbhost = $databases['default']['default']['host'];
  $dbpass = $databases['default']['default']['password'];
  $dbuser = $databases['default']['default']['username'];
  $dbname = $databases['default']['default']['database'];
 
  // Main processing loop
  foreach($cookies as $key=>$val) {
    // Ignore any cookie with anything but alphanumeric characters
    if(!preg_match('/^[\w-]+$/', $key)
    || !preg_match('/^[\w-]+$/', $val)) {
      continue;
    }
 
    // If we are using HTTPS, the first chars of the
    // Drupal cookie are SSESS. Drupal's HTTP cookies
    // start with SESS.
 
    // The commented line is the equivalent of the one below, which uses regex instead
    //if(substr($key, 0, 4) == 'SESS' && strlen($key) == 36) {
    if(preg_match('/^SESS[\w]{32}$/', $key)) {
      $sessioncookie = $val;
    } else {
      continue;
    }
 
    // Now we have a cookie, find it in the database
    mysql_connect($dbhost, $dbuser, $dbpass) or die('Critical failure. Cannot connect to database.');
    mysql_select_db($dbname) or die('Critical failure. Cannot open database.');
 
    // Bear in mind that you will need to change sid to ssid in the following line if you are using HTTPS
    $sql = 'SELECT u.uid,name FROM users u, sessions s WHERE u.uid=s.uid AND s.sid="'.$sessioncookie.'"';
    $result = mysql_query($sql);
 
    if(mysql_num_rows($result) > 0) {
      $row = mysql_fetch_array($result);
      $uid = $row['uid'];
      $username = $row['name'];
      break;
    } else continue; // No rows found, so on to the next cookie
  }
 
  // If we have user details after the loop, user is authenticated
  if($uid != '' && $username != '') return true;
 
  // All cookies have been checked, and all have failed
  return false;
}

/**
 *  Match the number given to a filename to download.
 *
 *  return  string  String will either be a filename or blank
 *
 **/
function getFileName($filenum) {
  $fname = '';
  // Check for a single number in the input
  if(!preg_match("/^[0-9]$/", $filenum)) return '';
  switch($filenum) {
    case 1:
      $fname = 'UserManual.pdf';
      break;
    case 2:
      $fname = 'EngineerManual.pdf';
      break;
  }
  return $fname;
}

/**
 *  Redirect to the referring page
 *
 * return n/a
 **/
function redirectToPrevious() {
  echo "<META HTTP-EQUIV=Refresh CONTENT=\"3; URL=".$_SERVER[HTTP_REFERER]."\">";
}

/**
 *  Pass a download link to the user.
 *
 *  return  n/a
 **/
function downloadFile($file) {
  header("Content-type: application/force-download");
  header("Content-Transfer-Encoding: Binary");
  header("Content-length: ".filesize($file));
  header("Content-disposition: attachment; filename=\"".basename($file)."\"");
  readfile("$file");
}

// Set variables.
$dir='/srv/filerepo/';
$filename='';
 
// Check authentication
if(!checkAuth()) {
  header("HTTP/1.1 403 Forbidden");
  exit;
}
 
// Sanitise user input by only allowing digits
$getnum = filter_input(INPUT_GET, 'file', FILTER_SANITIZE_NUMBER_INT);
 
// Process input and download file
$filename = getFileName($getnum);
 
if($filename == '') {
  header('HTTP/1.0 404 Not Found');
  include('404.php');
  exit;
}
 
$file = $dir.$filename;
downloadFile($file);
 
redirectToPrevious();

?>
