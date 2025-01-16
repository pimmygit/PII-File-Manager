<?php
/* 
** Description:	Object to hold PII files and their attributes
**
** @package:	Utils
** @author:		Kliment Stefanov <stefanov@uk.ibm.com>
** @created:	15/01/2008
*/

Class PIIFile {
	
	private $fullPath;
	private $fileSize;
	private $fileDate;
	private $fileContent;
	
	function __construct() {
		$this->fullPath = '';
		$this->fileSize = 0;
		$this->fileDate = '';
		$this->fileContent = null;
	}
	
	function getFileName() {
		
		if (empty($this->fullPath)) {
			return false;
		} else {
			// We assume UNIX path and return everything after the last slash
			return substr($this->fullPath, strrpos($this->fullPath, "/"));
		}
	}
	
	function getFullPath() {
		return $this->fullPath;
	}
	
	function setFullPath($fPath) {
		$this->fullPath = $fPath;
	}
	
	function getSize() {
		return $this->fileSize;
	}
	
	function setSize($fSize) {
		$this->fileSize = $fSize;
	}
	
	function getDate() {
		return $this->fileDate;
	}
	
	function setDate($fDate) {
		$this->fileDate = $fDate;
	}
	
	function getContent() {
		return $this->fileContent;
	}
	
	function setContent($fContent) {
		$this->fileContent = $fContent;
	}
	
	
}
?>