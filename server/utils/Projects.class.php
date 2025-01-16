<?php
/* 
** Description:	Data Object representing list users
**				for particular project
** @package:	Utils
** @author:		Kliment Stefanov <stefanov@uk.ibm.com>
** @created:	26/10/2007
*/
require_once('mysql.php');
require_once('logger.php');

Class Projects
{
	// Pointer
	private $ptr = 0;
	// Array containing the projects
	private $projects = array();
   
	// Class constructor
    public function __construct($anArray=false) {
	
		if($anArray != false) {
		
			if(is_array($anArray)) {
			
				$this->projects[] = $anArray;
			}
		}
	}
   
	/*
	* Name: dbGetProjects
	* Desc: Pushes an element (project properties) to the object
	* Inpt:	$userName	-> Type: String,	Value: Name of the user
	* Outp: 			-> Type: INT,		Value: Number of elements in the object
	* Date: 26.10.2007
	*/
	public function dbGetProjects($userMail) {
		
		// Create list of projects for this user
		// If the user is an admin then he has access to all projects
		if (true || in_array($userMail, $FMT_ADMIN_LIST)) {
			msg_log(DEBUG, "Requesting all projects for admin: [". $userMail ."].", SILENT);
			$sqlResponse = selectData(TB_USER, 'DISTINCT project', '');
		} else {
			msg_log(DEBUG, "Requesting projects for manager: [". $userMail ."].", SILENT);
			$sqlResponse = selectData(TB_USER, 'DISTINCT project', 'user_mail = "'.$userMail.'" AND manager = true');
		}
				
		if ($sqlResponse) {
			
			$projList = '"';
			
			while ($userData = mysqli_fetch_array($sqlResponse, MYSQLI_ASSOC)) {
				$projList = $projList.$userData['project'].'", "';
			}
			msg_log(DEBUG, "Found ". substr_count($projList, ',') ." projects.", SILENT);
			$projList = substr($projList, 0, -3);
		}
		
		if (empty($projList)) {
			return 0;
		}
		
		msg_log(DEBUG, "Fetching project information.", SILENT);
				
		// If this user is a manager of one or more projects:
		$sqlResponse = selectData(TB_PROJ, '*', 'name IN ('.$projList.') ORDER BY name ASC');
		
		if ($sqlResponse) {
			
			while ($userData = mysqli_fetch_array($sqlResponse, MYSQLI_ASSOC)) {

				// Populate the array with users
				if($this->getNumRows() > 0) {
					array_push($this->projects, $userData);
				} else {
					$this->projects[] = $userData;
				}
			}
		}
		
		return $this->getNumRows();
	}

	/*
	* Name: getProject
	* Desc: Fetches the data under the pointer
	* Inpt:	none
	* Outp: Type: Array, Value: Array of project properties
	* Date: 26.10.2007
	*/
	public function getProject() {
	
		if(isset($this->projects[$this->ptr]))
			return $this->projects[$this->ptr++];
		
		return false;
	}
	
	/*
	* Name: moveNext
	* Desc: Moves to the next element in the data object
	* Inpt:	none
	* Outp: Type: boolean
	* Date: 26.10.2007
	*/
	public function moveNext() {
		
		$newPtr = $this->ptr + 1;
		
		if(isset($this->projects[$newPtr])) {
			
			$this->ptr = $newPtr;
			return true;
		}
		
		return false;
	}
	
	/*
	* Name: movePrevious
	* Desc: Moves to the previous element in the data object
	* Inpt:	none
	* Outp: Type: boolean
	* Date: 26.10.2007
	*/
	public function movePrevious() {
		
		$newPtr = $this->ptr - 1;
		
		if(isset($this->projects[$newPtr]))
            return $this->projects[$newPtr];

		return false;
	}

	/*
	* Name: getNumRows
	* Desc: Counts the number of elements (projects)
	* Inpt:	none
	* Outp: Type: INT, Value: Number of elements in the object
	* Date: 26.10.2007
	*/
	public function getNumRows() {
		return count($this->projects);
	}

	/*
	* Name: resetPointer
	* Desc: Resets the pointer to the beginning of the array
	* Inpt:	none
	* Outp: none
	* Date: 26.10.2007
	*/
	public function resetPointer() {
		$this->ptr = 0;
	}
}
?>