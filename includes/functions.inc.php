<?php

function process_script() {
	$action=strtolower(getRequest('action',true,'get'));
	$accepted_actions='login,signup,account,edit,logout,cancel';
	// explode AAs into array
	$accepted_actions_array = explode(",", $accepted_actions); 
	
	// if action is empty or the action is not one 
	// of the accepted actions, go to login menu
	if (empty($action) || !in_array($action, $accepted_actions_array)) {
		if(!empty($_SESSION['user_id'])) { // session exists and fresh website access
			$action = 'account'; 
		} else { 
			$action='login';
		}
	} elseif(!empty($_SESSION['user_id'])) { // session exists
		if($action == 'login' || $action == 'signup') {
			$action = 'account';
		} // else its not login or signup, which are allowed with a session
	} else { // no session exists
		// if action is not one where access is allowed without session
		if(!($action == 'login' || $action == 'signup')) {
			$action = 'login';
		} // else its login or signup, which are allowed without a session
	}

	return get_template($action, $action()); 
}

function &cancel() { 
	// TODO: CONFIRM DELETE WORKS
	$tempID = get_SESSION('user_id');
	mysql_query("DELETE FROM users WHERE id='$tempID'");

	Session_destroy();
	set_header('login'); 
	exit();
}

function &logout() {
	Session_destroy();
	set_header('login'); 
	exit();
}

function &login(){
	$HTML=array();
	$HTML['email']='';
	$HTML['password']='';
	$HTML['login_error']='';
	$userID = 0;
	if (getRequest('submitted',true,'post') !=='yes') {return $HTML;}
	
	foreach($HTML as $key=> &$value) {
		$value=getRequest($key, true, 'post');
	}
	
	$userID=0;
	if (empty($HTML['email'])) {
		$HTML['login_error'] = 'Email Cannot be empty';
	} elseif (empty($HTML['password'])) { 
		$HTML['login_error'] ='Password cannot be empty';
	} elseif (filter_var($HTML['email'],FILTER_VALIDATE_EMAIL) ===false) {
		$HTML['login_error'] ='Invalid Email Address';
	} else {
		$userID=validate_record($HTML['email'],$HTML['password']);
		if ($userID < 1) {
			$HTML['login_error'] ='Account not found or invalid Email/Password';
		}
	}
	
	if (empty($HTML['login_error'])) { // successful login
        set_SESSION('user_id', $userID);
        set_SESSION('email', $HTML['email']);
		set_header('account'); //If no errors -> go to account
		exit();
	}

	return $HTML;
}

function &edit() {
	$HTML=array();
	$tempID = get_SESSION('user_id');
	$query = "SELECT * FROM users WHERE id='$tempID' LIMIT 1";
	$res = mysql_query($query);
	$row = mysql_fetch_array($res);
	set_SESSION('country', $row['country']);
	set_SESSION('email', $row['email']);
	$HTML['email']= get_SESSION('email');
	$HTML['password']='';
	$HTML['confirm_password']='';
	$HTML['city']= $row['city'];
	$HTML['country_options_escape']='';
	$HTML['email_error']='';
	$HTML['confirm_password_error'] =''; 
	$HTML['city_error']='';
	$HTML['countryID_error']='';
	$HTML['signup_error']='';


	if (getRequest('submitted',true,'post') !=='yes') {return $HTML;}
	
	foreach($HTML as $key=> &$value) {
		$value=getRequest($key, true, 'post');
	}
	
	$userID=0;
	if (empty($HTML['email'])) {
		$HTML['email_error'] = 'Email Cannot be empty';
	} elseif (filter_var($HTML['email'],FILTER_VALIDATE_EMAIL) ===false) {
		$HTML['email_error'] ='Invalid Email Address';
	}

	if($HTML['email'] != get_SESSION('email') && check_user_entry_exists($HTML['email'])){
		$HTML['signup_error'] ='Email Address already registered!';
	}

	if(!(empty($HTML['confirm_password']) && empty($HTML['password']))){
		if (empty($HTML['confirm_password']) || !preg_match('((?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\!@#$%\^\&\*]).{6,20})', $HTML['password'])) { 
			$HTML['confirm_password_error'] ='Passwords has to be 6-20 chars and more secure!';
		} elseif ($HTML['confirm_password'] !== $HTML['password']){
			$HTML['confirm_password_error'] ='Passwords do not match';
		} 
	}

	if (empty($HTML['city'])) { 
		$HTML['city_error'] ='City name cannot be empty!';
	} elseif (preg_match('((?=.*[\!@#$%\^\&\*]).)', $HTML['city'])) {
		$HTML['city_error'] ='Special Symbols are not allowed!';
	}

 	if ($_POST['countryID'] === 'Please Select') { 
		$HTML['countryID_error'] ='Please select the country';
	} else {
		$country = $_POST['countryID'];

		set_SESSION('country', $country);
	}

	if (empty($HTML['email_error']) && empty($HTML['confirm_password_error']) && empty($HTML['city_error']) && empty($HTML['countryID_error']) && empty($HTML['signup_error'])) {
		$encrypted = encrypt($HTML['password']);
		set_SESSION('email', $HTML['email']);
		$query = mysql_query("UPDATE users SET email='$HTML[email]', city='$HTML[city]', country='$country' WHERE id='$tempID'");  // update everything but password

		if(!(empty($HTML['confirm_password']) && empty($HTML['password']))){ // password needs to be seperate
			$query = mysql_query("UPDATE users SET password='$encrypted' WHERE id='$tempID'");
		}

		set_header('login'); //If no errors -> go to login
		exit();
	}

	return $HTML;
} 

function &signup($edit=false){
	$HTML=array();
	$HTML['email']='';
	$HTML['password']='';
	$HTML['confirm_password']='';
	$HTML['city']='';
	$HTML['country_options_escape']='';
	$HTML['email_error']='';
	$HTML['confirm_password_error'] =''; 
	$HTML['city_error']='';
	$HTML['countryID_error']='';
	$HTML['signup_error']='';

	if (getRequest('submitted',true,'post') !=='yes') {return $HTML;}
	
	foreach($HTML as $key=> &$value) {
		$value=getRequest($key, true, 'post');
	}
	
	$userID=0;
	if (empty($HTML['email'])) {
		$HTML['email_error'] = 'Email Cannot be empty';
	} elseif (filter_var($HTML['email'],FILTER_VALIDATE_EMAIL) ===false) {
		$HTML['email_error'] ='Invalid Email Address';
	}

	if(check_user_entry_exists($HTML['email'])){
		$HTML['signup_error'] ='Email Address already registered!';
	}

	if (empty($HTML['confirm_password']) || !preg_match('((?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\!@#$%\^\&\*]).{6,20})', $HTML['password'])) { 
		$HTML['confirm_password_error'] ='Passwords has to be 6-20 chars and more secure!';
	} elseif ($HTML['confirm_password'] !== $HTML['password']){
		$HTML['confirm_password_error'] ='Passwords do not match';
	} 

	if (empty($HTML['city'])) { 
		$HTML['city_error'] ='City name cannot be empty!';
	} elseif (preg_match('((?=.*[\!@#$%\^\&\*]).)', $HTML['city'])) {
		$HTML['city_error'] ='Special Symbols are not allowed!';
	}

 	if ($_POST['countryID'] === 'Please Select') { 
		$HTML['countryID_error'] ='Please select the country';
	} else {
		$country = $_POST['countryID'];

		set_SESSION('country', $country);
	}

	if (empty($HTML['email_error']) && empty($HTML['confirm_password_error']) && empty($HTML['city_error']) && empty($HTML['countryID_error']) && empty($HTML['signup_error'])) {
		$encrypted = encrypt($HTML['password']);
		$query = mysql_query("INSERT INTO users (email, password, city, country) VALUES ('$HTML[email]', '$encrypted', '$HTML[city]', '$country')");  
		set_header('login'); //If no errors -> go to login
		exit();
	}

	return $HTML;
}

function validate_record($email, $pass) {
	if (empty($GLOBALS['DB'])) {die ('Database Link is not set'); }

	$email = strip_tags(substr($email,0,32));
	$pass = strip_tags(substr($pass,0,32));

	$row = array();
	$email = mysql_real_escape_string($email);
	$query = "SELECT * FROM users WHERE email='" . $email . "' LIMIT 1";
	$res = mysql_query($query);

	$row = mysql_fetch_array($res);

	if (isset($row['password'])) {
	    $sql_password = $row['password'];
	} else {
	    $sql_password = '1';
	}

	$sql_password = mysql_real_escape_string($sql_password);
	$de = decrypt($sql_password);

	if($de === $pass){
		return $row['id'];
	} else {
		return 0;
	}

}

function get_template($file, &$HTML=null){
	// You might want to modify $HTML data before processing templates
	// .....................
	if($file === 'signup' || $file === 'edit'){
			$query = "SELECT * FROM countries WHERE active='YES'";
		$res = mysql_query($query);
		if(!empty($_SESSION['country'])){
			$temp = get_SESSION('country');
		} else {
			$temp = 'Please Select';
		}

		$HTML['country_options_escape'] = "<option value='{$temp}'>{$temp}</option>";
		while ($row = mysql_fetch_array($res)) {
		    $HTML['country_options_escape'] .= "<option value='".$row['country']."'>".$row['country']."</option>";
		}
	}

	$content='';
	ob_start();
		if (@include(TMPL_DIR . '/' .$file .'.tmpl.php')):
		$content=ob_get_contents();
	endif;
	ob_end_clean();
	return $content;
}
















function encrypt($text, $SALT=null) {
	return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5(SECURE_KEY), $text, MCRYPT_MODE_CBC, md5(md5(SECURE_KEY))));
} 
    
function decrypt($text, $SALT=null) { 
	return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5(SECURE_KEY), base64_decode($text), MCRYPT_MODE_CBC, md5(md5(SECURE_KEY))), "\0");
}  

function check_user_entry_exists($email){
	if (empty($GLOBALS['DB'])) {die ('Database Link is not set'); }
	$email = mysql_real_escape_string($email);
	$res = mysql_query("SELECT 1 FROM users WHERE email='$email' LIMIT 1");

	return mysql_num_rows($res); // since its limited to 1, the response will be binary/boolean, either 0 or 1
}

function set_header($action=null) {
	$url = (empty($action)) ? urlhost() : urlhost().'?action='.$action;
	header('Location: '. $url );
	exit();
}

function &account() {
	$HTML=array();
	return $HTML;
}

function get_SESSION($key) {
	return ( !isset($_SESSION[$key]) ) ? NULL : decrypt($_SESSION[$key]);
}

function set_SESSION($key, $value='') {
	if (!empty($key)) {
		$_SESSION[$key]=encrypt($value);
		return true;
	}
	return false;
}

function util_getenv ($key) {
		return ( isset($_SERVER[$key])? $_SERVER[$key]:(isset($_ENV[$key])? $_ENV[$key]:getenv($key)) );
}

function host ($protocol=null) {
	$host = util_getenv('SERVER_NAME');
	if (empty($host)) {	$host = util_getenv('HTTP_HOST'); }
	return (!empty($protocol)) ? $protocol.'//'.$host  : 'http://'.$host;
}

function urlhost ($protocol=null) {
	return host($protocol).$_SERVER['SCRIPT_NAME'];
}

function getRequest($str='', $removespace=false, $method=null){
	if (empty($str)) {return '';}
  		switch ($method) {
			case 'get':
				$data=(isset($_GET[$str])) ? $_GET[$str] : '' ;
				break;
			case 'post':
				$data=(isset($_POST[$str])) ? $_POST[$str] : '';
				break;
				
			default:
				$data=(isset($_REQUEST[$str])) ? $_REQUEST[$str] : '';
		}
 		
		if (empty($data)) {return $data;}
		
		
		if (get_magic_quotes_gpc()) {
			$data= (is_array($data)) ? array_map('stripslashes',$data) : stripslashes($data);	
		}

		if (!empty($removespace)) {
			$data=(is_array($data)) ? array_map('removeSpacing',$data) : removeSpacing($data);
		}

		return $data;
	}

function removeSpacing($str) {
		return trim(preg_replace('/\s\s+/', ' ', $str));
}
	
	
function utf8HTML ($str='') {
  	   	return htmlentities($str, ENT_QUOTES, 'UTF-8', false); 
}

?>