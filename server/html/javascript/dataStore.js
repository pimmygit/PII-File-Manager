/* 
** Description:	Classes for data objects
**				
** @package:	Data Store
** @author:		Kliment Stefanov <stefanov@uk.ibm.com>
** @created:	26/10/2007
*/

/*
* Name: DataStack
* Desc: Store for Data Objects of various type
* Inpt:	none
* Outp: none
* Date: 27.10.2007
*/
function DataStack() {

        this.dbNumRows = 0;
        this.dataStack = new Array;

        if (typeof location._initialized == 'undefined') {

                // Defines the number of items returned from the database
                DataStack.prototype.setDBNumRows = function (numRows) {
                        this.dbNumRows = numRows;
                };

                // Defines the number of items returned from the database
                DataStack.prototype.getDBNumRows = function () {
                        return this.dbNumRows;
                };

                DataStack.prototype.pushData = function (dataPacket) {
                        this.dataStack.push(dataPacket);
                };

                DataStack.prototype.updateData = function (id, newData) {
                        this.dataStack[id] = newData;
                };

                DataStack.prototype.unshiftData = function () {
                        return this.dataStack.unshift();
                };

                DataStack.prototype.popData = function () {
                        return this.dataStack.pop();
                };

                DataStack.prototype.shiftData = function () {
                        return this.dataStack.shift();
                };
                
                DataStack.prototype.getElement = function (id) {
                        return this.dataStack[id];
                };

                DataStack.prototype.getSize = function () {
                        return this.dataStack.length;
                };

                DataStack.prototype.isEmpty = function () {
                        if (this.dataStack.length > 0) {
                                return false;
                        } else {
                                return true;
                        }
                };

				DataStack.prototype.removeAll = function () {
                        this.dataStack = new Array;
                };
 
                DataStack._initialized = true;
        }
}

/*
* Name: ProjectData
* Desc: Object containing properties for a particular project
* Inpt:	none
* Outp: none
* Date: 02.11.2007
*/
function ProjectData() {

	// Initialize properties
	this.name = '';						// Name of the project
	this.ccLocation = '';				// Location of the project in ClearCase
	this.ccActivity = '';				// ClearCase activity to use
	this.ccView = '';					// ClearCase view to use
	this.ccCodeReview = '';				// Person to perform code review
	this.ftpServer = '';				// FTP server location for file uploading/downloading
	this.ftpUser = '';					// FTP user account name
	this.ftpPass = '';					// FTP password
	this.lastModify = '';				// Timestamp of the last modification of the project

	if (typeof(ProjectData._initialized) == 'undefined') {
	
		ProjectData.prototype.setProperties = function(propsList) {
			
			this.name = propsList[0];
			this.ccLocation = propsList[1];
			this.ccActivity = propsList[2];
			this.ccView = propsList[3];
			this.ccCodeReview = propsList[4];
			this.ftpServer = propsList[5];
			this.ftpUser = propsList[6];
			this.ftpPass = propsList[7];
			this.lastModify = propsList[8];
		};
		
		ProjectData.prototype.setName = function(newName) { this.name = newName; };
		ProjectData.prototype.getName = function() { return this.name; };

		ProjectData.prototype.setCcLocation = function(ccLocation) { this.ccLocation = ccLocation; };
		ProjectData.prototype.getCcLocation = function() { return this.ccLocation; };

		ProjectData.prototype.setCcActivity = function(ccActivity) { this.ccActivity = ccActivity; };
		ProjectData.prototype.getCcActivity = function() { return this.ccActivity; };

		ProjectData.prototype.setCcView = function(ccView) { this.ccView = ccView; };
		ProjectData.prototype.getCcView = function() { return this.ccView; };

		ProjectData.prototype.setCcCodeReview = function(ccCodeReview) { this.ccCodeReview = ccCodeReview; };
		ProjectData.prototype.getCcCodeReview = function() { return this.ccCodeReview; };

		ProjectData.prototype.setFtpServer = function(ftpServer) { this.ftpServer = ftpServer; };
		ProjectData.prototype.getFtpServer = function() { return this.ftpServer; };

		ProjectData.prototype.setFtpUser = function(ftpUser) { this.ftpUser = ftpUser; };
		ProjectData.prototype.getFtpUser = function() { return this.ftpUser; };

		ProjectData.prototype.setFtpPass = function(ftpPass) { this.ftpPass = ftpPass; };
		ProjectData.prototype.getFtpPass = function() { return this.ftpPass; };

		ProjectData.prototype.setLastModify = function(lastModify) { this.lastModify = lastModify; };
		ProjectData.prototype.getLastModify = function() { return this.lastModify; };

		ProjectData.prototype.showProps = function() {
			alert("name[" + this.name +
			"]\nccLocation[" + this.ccLocation +
			"]\nccActivity[" + this.ccActivity +
			"]\nccView[" + this.ccView +
			"]\nccCodeReview[" + this.ccCodeReview +
			"]\nftpServer[" + this.ftpServer +
			"]\nftpUser[" + this.ftpUser +
			"]\nftpPass[" + this.ftpPass +
			"]\nlastModify[" + this.lastModify + "]");
		};
		
		ProjectData._initialized = true;
	}
}
