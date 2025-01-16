<?php
/* 
* @package:		Security
* @subpackage:	User Authentication
* @author:		Kliment Stefanov <stefanov@uk.ibm.com>
*/

if (!empty($_GET['sevr'])) {
	
	switch ($_GET['sevr']) {
		
		case 'error' :
			$severity = 'Error';
			break;
		
		case 'warn' :
			$severity = 'Warning';
			break;
		
		case 'info' :
			$severity = 'Information';
			break;
			
		default :
			$severity = 'Message';
			break;
	}
	
} else {
	$severity = 'Message';
}

if (!empty($_GET['msg'])) {
	$message = $_GET['msg'];
} else {
	$message = '';
}
?>
<html>
<head>
	<title>PII F.M.T. - Message</title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
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
				echo '<p class="regularText" style="letter-spacing:1.5;">'.$severity.'</p>';
			?>
			</div>
		</div>
		<div class="formPanel">
			<?php
				echo '<p class="regularText" style="margin:10px; text-align:left; line-height:1.5;">'.$message.'</p>';
			?>
		</div>
	</div>
</div>

</body>
</html>
