/* 
** Description:	Contains functions for producing log information
**				related to the L10N tool operation.
** @package:	utils
** @author:		Kliment Stefanov <stefanov@uk.ibm.com>
** @created:	14/09/2007
*/


/*
* Name: getXmlHttpRequest
* Desc: Creates the XML request object depending on the browser type
* Inpt:	none
* Outp: XMLHTTPRequest
* Date: 26/10/2007
*/
function getXmlHttpRequest() {

	if (window.XMLHttpRequest) {
		xmlhttp = new XMLHttpRequest(  );
		//xmlhttp.overrideMimeType('text/xml');
		return xmlhttp;
	} else {
		try {
			xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
			return xmlhttp;
		} catch (e)	{
			try {
				xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
				return xmlhttp;
			} catch (e) {
				return false;
			}
		}
	}
}

/*
* Name: syncXmlHttpRequest
* Desc: Sends synchronous XML HTTP request to a php function,
*		then executes the required command
* Inpt:	url		-> Type: String, Value: target php script
*		act		-> Type: String, Value: requested action
*		par		-> Type: String, Value: parameters to be send
* Outp:  		-> Type: Boolean,Value: TRUE on success, ERROR CODE otherwise
* Date: 29/10/2007
*/
function syncXmlHttpRequest(url, act, par) {

	var xmlhttp = getXmlHttpRequest();
	var request = url + '?action=' + act + par;
	
	//alert(request);
	xmlhttp.open('GET', request, false);
	xmlhttp.setRequestHeader('Accept','message/x-jl-formresult');
	xmlhttp.send(null);
	
	response = xmlhttp.responseText;
	//alert("XML HTTP Response: [" +response+ "]");
	
	if (xmlhttp.status == 200) {

		switch (response) {
		
			case '-7':
				//alert("Unknown server error.");
				return response;
			case '-6':
				//alert("Bad XML HTTP Request.");
				return response;
			case '-5':
				//alert("Internal server error.");
				return response;
			case '-4':
				//alert("Already exist.");
				return response;
			case '-3':
				//alert("Does not exist.");
				return response;
			case '-2':
				//alert("Wrong password.");
				return response;
			case '-1':
				//alert("Database error.");
				return response;
			case '0':
				//alert("Failed to connect to database.");
				return response;
			case '1':
				// Command executed successfully
				return response;
				break;
				
			default:
				// If it's not a return code (max of three chars), then it must be JavaScript array containing
				// the user preferences for the particular user.
				
				// If database is empty, or we have a strange response there is no need to populate the table
				if (response.length > 3 && response != 'empty') {
				
					// Determine the required action
					switch (act) {
					
						case 'getUsers':
							createUserDataArray(response);
							//prefList.showProps();
							break;
							
						case 'deviceMan':
							//createDeviceListArray(response);
							break;
			
						case 'isIBMer':
							return response;
							break;
				
						case 'getProjectsForUser':
							return response;
							break;
					
						case 'getAuthCC':
							return response;
							break;
					
						case 'getCurrProj':
							return response;
							break;
				
						case 'getScansFTP':
							return response;
							break;
							
						case 'getLangList':
							return response;
							break;
				
						case 'getPackLangCode':
							// Strip the LANG: prefix
							return response.substring(5);
							break;
						
						case 'packGetState':
							return response;
							break;
						
						case 'getProjectData':
							return createProjData(response);
							break;		
									
						case 'gpsData':
							//createGPSDataArray(response);
							break;
				
						default:
							alert("Bad request: [" + response + "]");
							break;
					}
					
				} else {
					if (response.length < 3 )
						alert("Illegal operation: [" + response + "]");
				}
		}
		return true;
	} else {
		alert("Bad response from server: [" + xmlhttp.status + "].");
		return -6;
	}
}

/*
* Name: xmlHttpRequest
* Desc: Sends asynchronous XML HTTP request to a php function,
*		then executes the required command
* Inpt:	url		-> Type: String, Value: target php script
*		act		-> Type: String, Value: requested action
*		par		-> Type: String, Value: parameters to be send
* Outp:  		-> Type: Boolean,Value: TRUE on success, ERROR CODE otherwise
* Date: 30/10/2007
*/
function xmlHttpRequest(url, act, par) {

	var xmlhttp = getXmlHttpRequest();
	var request = url + '?action=' + act + par;
	
	//alert(request);
	xmlhttp.open('GET', request, true);
	xmlhttp.onreadystatechange = function() { //Call a function when the state changes.
		if(xmlhttp.readyState == 4 && xmlhttp.status == 200) {
			
			response = xmlhttp.responseText;
			//alert("XML HTTP Response: [" +response+ "]");

			switch (response) {
			
				case '-7':
					alert("Unknown server error.");
					return response;
				case '-6':
					alert("Bad XML HTTP Request.");
					return response;
				case '-5':
					alert("Internal server error.");
					return response;
				case '-4':
					alert("Already exist.");
					return response;
				case '-3':
					alert("User does not exist.");
					return response;
				case '-2':
					alert("Wrong password.");
					return response;
				case '-1':
					alert("Database error.");
					return response;
				case  '0':
					alert("Failed to connect to database.");
					return response;
				case '1':
					// Command executed successfully
					return true;
					break;
					
				default:
					// If it's not a return code (max of three chars), then it must be JavaScript array containing
					// the user preferences for the particular user.
		
					// If database is empty, or we have a strange response there is no need to populate the table
					if (response.length > 3 && response != 'empty') {
					
						// Determine the required action
						switch (act) {
						
							case 'getProjectsForManager':
								populateStackProjects(response);
								break;
					
							case 'getPrivileges':
								var prvlgList = response.split(',');
								break;
								
							case 'getLangList':
								populateLangArray(response);
								break;
				
							case 'getRenRules':
								createRenamingRulesArray(response);
								break;
					
							case 'scanForPII':
								populateScanTable(response);
								break;
					
							case 'gpsData':
								//createGPSDataArray(response);
								break;
					
							default:
								alert("Bad request: [" + response + "]");
								break;
						}
						
					} else {
						if (response.length < 3 )
							alert("Illegal operation: [" + response + "]");
					}
			}
			return true;
		}
	};
	xmlhttp.send('');
}

/*
* Name: populateStackProjects
* Desc: Converts the CSV list of project data into an object of project data
* Inpt: User data in a CSV format
* Outp: none
* Date: 02.11.2007
*/
function populateStackProjects(csvList) {
	
	var tokenArray = csvList.split(',');
	
	for (var i=0; i<tokenArray.length; i=i+9) {
		var newProject = new ProjectData();
		//alert("Adding data: [" + tokenArray[i] + "], [" + tokenArray[i+1] + "], [" + tokenArray[i+2] + "], [" + tokenArray[i+3] + "], [" + tokenArray[i+4] + "], [" + tokenArray[i+5] + "], [" + tokenArray[i+6] + "], [" + tokenArray[i+7] + "], [" + tokenArray[i+8] + "]"); 
		newProject.setProperties(new Array(tokenArray[i], tokenArray[i+1], tokenArray[i+2], tokenArray[i+3], tokenArray[i+4], tokenArray[i+5], tokenArray[i+6], tokenArray[i+7], tokenArray[i+8]));
		projData.pushData(newProject);
	}
	
	populateProjects();
}

/*
* Name: createProjData
* Desc: Converts the CSV list of project data into an object of project data
* Inpt: User data in a CSV format
* Outp: none
* Date: 02.04.2007
*/
function createProjData(csvList) {
	
	var tokenArray = csvList.split(',');
	
	// Project properties must be exactly 9
	if (tokenArray.length != 9) {
		return -5; // Internal server error
	}
	
	var newProject = new ProjectData();
	//alert("Adding data: [" + tokenArray[0] + "], [" + tokenArray[1] + "], [" + tokenArray[2] + "], [" + tokenArray[3] + "], [" + tokenArray[4] + "], [" + tokenArray[5] + "], [" + tokenArray[6] + "], [" + tokenArray[7] + "], [" + tokenArray[8] + "]"); 
	newProject.setProperties(new Array(tokenArray[0], tokenArray[1], tokenArray[2], tokenArray[3], tokenArray[4], tokenArray[5], tokenArray[6], tokenArray[7], tokenArray[8]));

	return newProject;
}

/*
* Name: populateLanguages
* Desc: Converts the CSV list of languages data into an array
* Inpt: User data in a CSV format
* Outp: none
* Date: 07.11.2007
*/
function populateLangArray(csvList) {
	
	var tokenArray = csvList.split(',');
	
	// Clean the list befor populating it
	langList = new Object();
	
	for (var i=0; i<tokenArray.length; i=i+2) {
		//alert("Adding data: langList[" + tokenArray[i] + "] = " + tokenArray[i+1]); 
		langList[tokenArray[i]] = tokenArray[i+1];
	}
	
	if (document.getElementById('langTable')) {
		popuateLangs(langList);
	}
}

/*
* Name: createUserDataArray
* Desc: Converts the CSV list of user data into an array of users
* Inpt: User data in a CSV format
* Outp: none
* Date: 26.10.2007
*/
function createUserDataArray(csvList) {
	
	var tokenArray = csvList.split(',');
	
	// Set the number of items returned from the DB (first item from the cvsList is the number of returned rows)
	//userData.setDBNumRows(tokenArray.shift());
	var dbRetNumRows = tokenArray.shift();
	userData.setDBNumRows(dbRetNumRows);
	// Set the number of properties for each user (second item from the cvsList is the number properties)
	//userData.setDBNumRows(tokenArray.shift());
	var dbRetNumCols = tokenArray.shift();

	for (var i=0; i<dbRetNumRows; i++) {
		
		// Dynamic build of the array of user properties
		// depending on their number per user in the database.
		var _userProps = new Array;
		for (var j=0; j<dbRetNumCols; j++) {
			_userProps.push(tokenArray[(i*dbRetNumCols)+j]);
		}

		userData.pushData(_userProps);
	}
}

/*
* Name: createRenamingRulesArray
* Desc: Converts the CSV list of renaming rules into an array: Array[_from_] = _to_
* Inpt: File renaming rules data in a CSV format
* Outp: none
* Date: 13.11.2007
*/
function createRenamingRulesArray(csvList) {
	
	var tokenArray = csvList.split(',');
	
	// First clean the object of old data
	rulesList = new Object();
	
	for (var i=0; i<tokenArray.length; i=i+2) {
		//alert("Adding data: langList[" + tokenArray[i] + "] = " + tokenArray[i+1]); 
		rulesList[tokenArray[i]] = tokenArray[i+1];
	}
	
	popuateRules();
}