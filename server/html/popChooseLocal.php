<?php
require_once('../../utils/logger.php');
require_once('../../utils/functions.php');
require_once('../../utils/FTP.class.php');

// Get values
if(isset($_GET["currProj"]))
	$currProj = $_GET["currProj"];

// Get FTP server details
$ftpServer = getFtpServer($currProj);
// Create FTP connection to the server
$ftpConn = new FTP($ftpServer[0], 21);
// Authenticate the user
if ($ftpConn && $ftpConn->login($ftpServer[1], $ftpServer[2])) {
	// Get list of available scans for this project
	//----------------------------------------------
	// Define the project location
	$fullPath = ftp_pwd($ftpConn) . '/' . str_replace(" ", "", $currProj);
	// Get list of files in this directory
	$scanList = ftp_nlist($ftpConn, $fullPath);
}
?>

<html>
<head>
<title>Upload File</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<script language="JavaScript">
name = 'uploadFrame';
function verify(){
  var fields = document.fileChooser;
  var elementcount = fields.length;
  for (i=0; i<elementcount; i++){
          if (fields[i].type=='file'){
                  var selFile=fields[i].value;
                  if (selFile.length < 4){
                          return(false);
                  }
          }
  }
  window.close();
  return(true);
}

function cancelAction(){
  window.close();
}
</script>
</head>

<body bgcolor="#D3D3D3" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
<!-- Table with one cell to center the entire page -->
<table width="100%" height="100%" border="0" cellpadding="0" cellspacing="0"><tr><td align="center" valign="middle">
	<form name="scanChooser" action="../scanExp.php" target="scanExp" enctype="multipart/form-data" method="post" style="margin: 0" onSubmit="return verify();">
	<table width="90%" bordercolor="#000000" frame="above" cellspacing="0" cellpadding="0">
		<tr><td bgcolor="#A9A9A9">Please choose a file to upload:</td></tr>
		<tr><td>
			<select class="dropMenu" style="width:150px;" name="ftpScanSelector">
				<option value ="none">Select scan from the list.</option>
				<?php
				if (is_array($scanList) ) {
					foreach ( $scanList as $scanName ) {
						if (isset($_POST['scanValue']) && !empty($_POST['scanValue']) && ($_POST['scanValue'] == $scanName) ) {
							echo '				<option value="'.$scanName.'" onClick="return chooseScan(this.value);" SELECTED>'.$scanName.'</option>'.PHP_EOL;
						} else {
							echo '				<option value="'.$scanName.'" onClick="return chooseScan(this.value);">'.$scanName.'</option>'.PHP_EOL;
						}
					}
				}
				?>
			</select>
		</td></tr>
	</table>
	</form>
</td></tr></table>

</body>
</html>