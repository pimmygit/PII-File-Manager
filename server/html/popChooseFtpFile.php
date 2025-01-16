<?php
require_once('../utils/logger.php');
require_once('../utils/functions.php');
require_once('../utils/FTP.class.php');

// Get values
if(isset($_GET["currProj"])) {
	
	$currProj = $_GET["currProj"];

	// Get FTP server details
	$ftpServer = getFtpServer($currProj);
	// Create FTP connection to the server
	$ftpConn = new FTP($ftpServer[0], 21);
	// Authenticate the user
	if ($ftpConn && $ftpSession = $ftpConn->login($ftpServer[1], $ftpServer[2])) {
		// Get list of available scans for this project
		//----------------------------------------------
		// Define the project location
		$fullPath = ftp_pwd($ftpSession) . '/' . str_replace(" ", "", $currProj);
		// Get list of files in this directory
		$scanList = ftp_nlist($ftpSession, $fullPath);
	}
}

// To call this pop-up from javascript:
//---------------------------------------------
//function uploadFromFTP(currProj) {
//	var w = 350;
//	var h = 120;
//	var winl = (screen.width - w) / 2;
//	var wint = (screen.height - h) / 2;
//	
//	window.open('popChooseFtpFile.php?currProj='+currProj, '', 'width='+w+', height='+h+', top='+wint+', left='+winl+', scroll=no, status=no, toolbar=no');
//
//	return false;
//}
//---------------------------------------------
?>

<html>
<head>
	<title>Upload file from FTP server.</title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<style type="text/css">
	<!--
	.popPanel {
		position: relative;
		width: 300px;
		height: 20px;
		margin-top: 3px;
		margin-left: auto;
		margin-right: auto;
		padding: 5px;
		font-family: Verdana;
		font-weight: bold;
		font-size: 13px;
		text-align: center;
	}
	.dropMenu {
		border: 1px solid gray;
		margin-bottom: 10px;
		font-family: Verdana;
		font-weight: normal;
		font-size: 13px;
		color: #404040;
		width: 100%;
	}
	-->
	</style>
	<script language="JavaScript">
	name = 'uploadFrame';
	
	function chooseScanFTP(scanName) {
		
		document.getElementById("scanFileName").value = scanName;
		document.scanChooser.submit();
	}

	function cancelAction(){
		window.close();
	}
</script>
</head>

<body bgcolor="#D3D3D3" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
<form name="scanChooser" action="scanExp.php" target="scanExp" method="post" style="margin: 0">
	<div class="popPanel" style="margin-top:20px; background-color:#A9A9A9;">Please choose file to get:</div>
	<div class="popPanel" style="height:40; background-color:#BBBBBB;">
		<select class="dropMenu" style="width:250px; margin-top:10px;" name="ftpScanSelector">
			<option value ="none">Select scan from the list.</option>
			<?php
			if (is_array($scanList) ) {
				foreach ( $scanList as $scanName ) {
					echo '				<option value="'.substr($scanName, strrpos($scanName, '/') - strlen($scanName) + 1).'" onClick="chooseScanFTP(this.value);">'.substr($scanName, strrpos($scanName, '/') - strlen($scanName) + 1).'</option>'.PHP_EOL;
				}
			}
			?>
		</select>
	</div>
	<input type="hidden" id="scanFileName" name="scanFileName" value="" />
</form>


</body>
</html>