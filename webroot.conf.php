<?php
// HAS TO BE IN THE ROOT DIRECTORY OF THE SITE!!!
set_time_limit(0); 
error_reporting(E_ALL);
ini_set('session.use_cookies',1);
ini_set('session.use_only_cookies',1);
ini_set('session.use_trans_sid',0);
ini_set('session.gc_maxlifetime',7200); // 2 hour session!
ini_set('default_charset', 'UTF-8');

mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

@set_magic_quotes_runtime(0);

session_start();

// So templates can be included
define ('TMPL_DIR', './templates');
define ('SECURE_KEY', 'po64'); //Used in encrypt/decrypt functions!

// You need to set your own parameters!!
define('MYSQL_SERVER', 'localhost:3306');
define('MYSQL_USER', 'INSERT YOUR DB USERNAME HERE');
define('MYSQL_DB', 'INSERT YOUR DB HERE');
define('MYSQL_PASSWORD', 'INSERT YOUR DB PASSWORD HERE');

// You'd need to activate it once you have operational system
$GLOBALS['DB']= @mysql_connect(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD) or die ('Cannot connect to the MySQL server');
mysql_select_db(MYSQL_DB, $GLOBALS['DB']) or die ('Cannot select MySQL database');

require_once('./includes/functions.inc.php');
?>