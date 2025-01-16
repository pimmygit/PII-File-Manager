<?php
/* 
** Description:	Extension class to ZipArchive
**				
** @package:	Utils
** @author:		Kliment Stefanov <stefanov@uk.ibm.com>
** @created:	14/02/2008
*/

class FMTZipArchive extends ZipArchive {
	/*
	* Name: addDir
	* Desc: Adds directory and its content to the ZIP archive
	* Inpt:	$srcPath	-> Type: String,	Value: Directory to add
	* Inpt:	$dstArchive	-> Type: String,	Value: Target ZIP archive
	* Outp:	none
	* Date: 14.02.2008
	*/
	public function addDir($srcPath, $dstArchive) {
		
		$this->addEmptyDir($dstArchive);
		
		$files = new RecursiveDirectoryIterator($srcPath);
		
		foreach ($files as $fileinfo) {
			
			if ( $fileinfo->isFile() || $fileinfo->isDir() ) {
				
				$method = $fileinfo->isFile() ? 'addFile' : 'addDir';
				
				$this->$method($fileinfo->getPathname(), $dstArchive . '/' . $fileinfo->getFilename());
			}
		}
	}
}
?>