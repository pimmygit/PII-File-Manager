<?php
/* 
* @package:		Security
* @subpackage:	User Authentication
* @author:		Kliment Stefanov <stefanov@uk.ibm.com>
*/
include_once('../config/constants.php');
include_once('../config/settings.php');
include_once('../utils/logger.php');
include_once('../utils/functions.php');
include_once('../security/LoginLDAP.class.php');

$usrAgent =  $_SERVER['HTTP_USER_AGENT'];

if ( 0 && strstr($usrAgent,"Firefox") == NULL ) {
	print "Sorry, but your browser is not supported. Please use Firefox version > 1.0 (http://www.mozilla.com/firefox/)";
   	exit();
}

msg_log(DEBUG, "PAGE: login.php", SILENT);

if (!empty($_POST['ibmUSER']) || !empty($_POST['ibmPASS'])) {
	
	$loginMail = $_POST['ibmUSER'];
	$loginPass = $_POST['ibmPASS'];
	$alertMess = '';
	
	if ( USE_LDAP_AUTHENTICATION ) {
		$user = new LoginLDAP();
	} else {
		$user = new LoginLocal();
	}
	
	if ( !$user->isValidUser($loginMail) ) {
		$alertMess = 'Invalid IBM E-mail address.';
	} else {
		
		if ( !$user->isValidPassword($loginPass) ) {
			$alertMess = 'Incorrect password.';
		} else {
			// Start the session
			session_start();
			session_register("piifmt");
				
			// Get domain without www and any port (if present)
			$domain = getDomain();
			// Set expiration (30 days)
			$expire = time() + (86400*30);
			// Set the cookie
			setcookie('ibmMail', $loginMail, $expire, '/', $domain, 0);
			
			// Set session variables
			$_SESSION['userMail'] = $loginMail;
			$_SESSION['userName'] = $user->getUserName();
			
			if ($user->hasAccess($loginMail)) {
				// Redirect to the callers page if known
				if ( isset($_SESSION['URL_BACK']) && !empty($_SESSION['URL_BACK'])) {
					header("Location: " . $_SESSION['URL_BACK']);
					exit();
				} else {
					header("Location: piifmt.php");
					exit();
				}
			} else {
				msg_log(WARN, "Unauthorized IBM user is trying to log in.", SILENT);
				$alertMess = 'You are not authorized to access the PII File Management Tool.';
			}
		}
	}
} else {
	
	if( isset($_COOKIE["ibmMail"]) ) {
		$loginMail = $_COOKIE["ibmMail"];
	} else {
		$loginMail = '';
	}
	
	$loginPass = '';
	$alertMess = '';
}
?>
<html>
<head>
	<title>PII F.M.T. - Login</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" type="text/css" href="main.css" />
	<script type="text/javascript" src="javascript/security.js"></script>
	<script type="text/javascript" src="javascript/functions.js"></script>
</head>

<body onLoad="positionCursor();">

<div class="titleBar"></div>

<div class="sideBar">
	<div><ul class="buttons">
		<li><a href="index.php">Home</a></li>
		<li><a href="login.php">Login</a></li>
		<li><a href="../help.php">Help</a></li>
	</ul></div>
</div>

<div id="mainContainer"><i></i>
	<div id="mainContainerVertCenter" align="center">
		<div class="infoPanel" align="center">
		<div class="infoGlue">
		<?php
			if ( empty($alertMess) ) {
				echo '<p class="regularText">Please log in with your IBM E-mail and password.</p>';
			} else {
				echo '<p class="regularText">'.$alertMess.'</p>';
			}
		?>
		</div>
		</div>
		<div class="formPanel">
		<form name="userCredentials" id="userCredentials" method="post" action="login.php">
			<div class="textField">
				<p align="left">
					Username: <input class="fmtInputbox" type="text" name="ibmUSER" id="ibmUSER" value="<?php echo $loginMail; ?>" size="20" />
				</p>
				<p align="left">
					Password: <input class="fmtInputbox" type="password" name="ibmPASS" id="ibmPASS" value="" size="20" />
				</p>
				<p align="right">
					<input class="formButtons" type="submit" name="Login" value="Login" onClick="return verifyFields();" />
				</p>
			</div>
			<?php
			if ( isset($referer) && !empty($referer))
				echo '<input type="hidden" id="referer" name="referer" value="'.$referer.'" />'.PHP_EOL;
			?>
		</form>
		</div>
	</div>
</div>

</body>
</html>
