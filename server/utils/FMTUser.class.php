<?php
/* 
** Description:	Data Object representing user and his privileges
**				for particular project
** @package:	Utils
** @author:		Kliment Stefanov <stefanov@uk.ibm.com>
** @created:	12/03/2008
*/
Class FMTUser
{
	// Array containing the privileges
	private $prvlg = array();
	// Other user data
	private $userName;
	private $userLang;
	
	/*
	* Name: FMTUser
	* Desc: Class constructor
	* Inpt:	$projName			-> Type: String,	Value: Name of the project
	* 		$userMail			-> Type: String,	Value: E-mail of the user
	* Outp: none
	* Date: 12.03.2008
	*/
	public function __construct($projName, $userMail) {
		
		$userData = getUserData( $projName, $userMail );
		
		$this->userName = $userData['user_name'];
		$this->userLang = $userData['lang'];
		$this->prvlg[0] = $userData['manager'];
		
		for ($i = 1; $i < count($userData) - 4; $i++) {
			if ( $i < 10 ) {
				$this->prvlg[$i] = $userData['prvlg_0'.$i];
			} else {
				$this->prvlg[$i] = $userData['prvlg_'.$i];
			}
		}
	}
	
	/*
	* Name: getName
	* Desc: Returns if the privilege with this ID is assigned to the user
	* Inpt:	$id		-> Type: INT,		Value: ID of the privilege
	* Outp: 		-> Type: String,	Value: Real name of the user
	* Date: 13.03.2008
	*/
	public function getName() {
		
		return $this->userName;
	}
	
	/*
	* Name: getLang
	* Desc: Returns if the privilege with this ID is assigned to the user
	* Inpt:	$id		-> Type: INT,		Value: ID of the privilege
	* Outp: 		-> Type: String,	Value: Language code
	* Date: 13.03.2008
	*/
	public function getLang() {
		
		return $this->userLang;
	}
	
	/*
	* Name: getPrivilege
	* Desc: Returns if the privilege with this ID is assigned to the user
	* Inpt:	$id		-> Type: INT,		Value: ID of the privilege
	* Outp: 		-> Type: Boolean,	Value: TRUE if yes, FALSE otherwise
	* Date: 13.03.2008
	*/
	public function getPrivilege($id) {
		
		return $this->prvlg[$id];
	}
}
?>