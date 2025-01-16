<?php
/* 
** Description:	provides interface to MySQL database
** @package:	utils
** @author:		Kliment Stefanov <stefanov@uk.ibm.com>
** @created:	12/09/2007
*/

/*
* Name: getColumnType
* Desc: Determines the data type of the specified column
* Inpt:	$table	-> Type: String, Value: [Table name]
*		$column	-> Type: String/Int, Value: [Columnn index || Column name]
* Outp: Type: String, Value: [NUMERIC||STRING||DATE] on succes, Error code otherwise
* Date: 22.03.2007
*/
function getColumnType($table, $column) {

	// Connect to the database
	$connection = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	
	/* check connection */
	if (mysqli_connect_errno())
	{
	   return CONNECT_FAILED;
	}
	
	$sqlQuery = 'DESC '.$table;
	
	// Get query result
	$sqlResult = mysqli_query($connection, $sqlQuery);

	// Close connection
	mysqli_close($connection);
	
	if ($sqlResult) {
	
		$i = 0;
		while ($userData = mysqli_fetch_array($sqlResult, MYSQLI_ASSOC)) {
			
			// Depending on what we compare to: Column index or Column name
			if ($column === $i || $column === $userData["Field"]) {
				
				$cutPosition = strpos($userData["Type"], '(');
				if ($cutPosition) {
					$columnType = substr($userData["Type"], 0, $cutPosition);
				} else {
					$columnType = $userData["Type"];
				}
					
				$cutPosition = strpos($columnType, ' ');
				if ($cutPosition) {
					$columnType = substr($columnType, 0, $cutPosition);
				}

				//echo "Column_".$i."->index[".$columnIndex."]:name[".$userData["Field"]."]:type[".$columnType."]:pos[".$cutPosition."], ";
				
				switch (strtolower($columnType)) {
				
					case 'bit':
					case 'tinyint':
					case 'bool':
					case 'boolean':
					case 'smallint':
					case 'mediumint':
					case 'int':
					case 'integer':
					case 'bigint':
					case 'float':
					case 'double':
					case 'dec':
					case 'decimal':
						return 'numeric';
						break;
						
					case 'date':
					case 'datetime':
					case 'time':
					case 'timestamp':
					case 'year':
						return 'date';
						break;
						
					case 'char':
					case 'varchar':
					case 'binary':
					case 'varbinary':
					case 'blob':
					case 'text':
					case 'enum':
					case 'set':
						return 'string';
						break;
						
					default:
						return 'string';
				}
			}
			$i++;
		}
	} else {
		return SQL_FAILED;
	}	
}

/*
* Name: insertData
* Desc: Inserts data into the database
* Inpt:	$table			-> Type: String, Value: [Table name]
*		$columnNames	-> Type: Array, Value: [Columnn names as strings]
*		$columnData		-> Type: Array, Value: [Columnn data]
* Outp: Type: Int, Value: TRUE on succes, Error code otherwise
* Date: 22.03.2007
*/
function insertData($table, $columnNames, $columnData) {
	
	$sqlQuery = '';
	
	// Create SQL statement
	if (count($columnNames) == count($columnData)) {
	
		$sqlQuery = $sqlQuery.'INSERT INTO '.$table.' (';
		
		foreach($columnNames as $columnName) {
			$sqlQuery = $sqlQuery.$columnName.', ';
		}
		
		$sqlQuery = substr($sqlQuery, 0, -2);
		$sqlQuery = $sqlQuery.') VALUES (';
		
		$i = 0;
		foreach($columnData as $data) {
			
			switch (getColumnType($table, $columnNames[$i])) {
			
				case 'numeric':
					$sqlQuery = $sqlQuery.$data.", ";
					break;
					
				case 'date':
					if ($data == 'timestamp') {
						$sqlQuery = $sqlQuery."NOW(), ";
					} else {
						$sqlQuery = $sqlQuery."'".$data."', ";
					}
					break;
					
				case 'string':
					$sqlQuery = $sqlQuery."'".$data."', ";
					break;
					
				default:
					$sqlQuery = $sqlQuery."'".$data."', ";
			}
			$i++;
		}
		
		$sqlQuery = substr($sqlQuery, 0, -2);
		$sqlQuery = $sqlQuery.')';
	} else {
		msg_log(ERROR, "Failed to create insert statement: columns do not match data.", NOTIFY);
		return COMMAND_FAILED;
	}
	
	// Connect to the database
	$connection = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	
	/* check connection */
	if (mysqli_connect_errno()) {
		msg_log(ERROR, "Failed to connect to MySQL database [" . DB_NAME ."] on server: [". DB_USER . "@" . DB_HOST . ":" . DB_PORT . "].", NOTIFY);
		return CONNECT_FAILED;
	}
	msg_log(DEBUG, "Connected to MySQL database [" . DB_NAME ."] on server: [". DB_USER . "@" . DB_HOST . ":" . DB_PORT . "].", SILENT);

	// Execute query
	msg_log(DEBUG, "Executing SQL: [". $sqlQuery ."].", SILENT);
	if (mysqli_query($connection, $sqlQuery)) {
	
		mysqli_close($connection);
		return COMMAND_OK;
	} else {
	
		mysqli_close($connection);
		return SQL_FAILED;
	}
}

/*
* Name: updateData
* Desc: Updates single data cell in the specified database
* Inpt:	$table		-> Type: String, Value: [Table name]
*		$column		-> Type: String, Value: [Column name]
*		$data		-> Type: Any, Value: [Column data]
*		$condition	-> Type: String, Value: [Condition as string]
* Outp: Type: Int, Value: TRUE on succes, Error code otherwise
* Date: 22.03.2007
*/
function updateData($table, $column, $data, $condition) {
	
	// Create SQL statement
	if (is_array($column) && is_array($data)) {
		
		if (sizeOf($column) != sizeOf($data)) {
			msg_log(ERROR, "Failed to create update statement: columns do not match data.", NOTIFY);
			return COMMAND_FAIL;
		}
		
		$sqlQuery = "UPDATE ".$table." SET ";
		
		for ($i=0; $i<sizeOf($column); $i++) {
			
			switch (getColumnType($table, $column[$i])) {
			
				case 'numeric':
					$sqlQuery = $sqlQuery.$column[$i]." = ".$data[$i].", ";
					break;
					
				case 'date':
					if ($data == 'timestamp') {
						$sqlQuery = $sqlQuery.$column[$i]." = NOW(), ";
					} else {
						$sqlQuery = $sqlQuery.$column[$i]." = '".$data[$i]."', ";
					}
					break;
					
				default: // Which includes STRING
					$sqlQuery = $sqlQuery.$column[$i]." = '".$data[$i]."', ";
					break;
			}
		}
		
		$sqlQuery = substr($sqlQuery, 0, -2);
		$sqlQuery = $sqlQuery." WHERE ".$condition;
		
	} else {
		
		switch (getColumnType($table, $column)) {
		
			case 'numeric':
				$sqlQuery = "UPDATE ".$table." SET ".$column." = ".$data." WHERE ".$condition;
				break;
				
			case 'date':
				if ($data == 'timestamp') {
					$sqlQuery = "UPDATE ".$table." SET ".$column." = NOW() WHERE ".$condition;
				} else {
					$sqlQuery = "UPDATE ".$table." SET ".$column." = '".$data."' WHERE ".$condition;
				}
				break;
				
			case 'string':
				$sqlQuery = "UPDATE ".$table." SET ".$column." = '".$data."' WHERE ".$condition;
				break;
				
			default:
				$sqlQuery = "UPDATE ".$table." SET ".$column." = '".$data."' WHERE ".$condition;
		}
	}
		
	//error_log("SQL: " . $sqlQuery);
	// Connect to the database
	$connection = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	
	/* check connection */
	if (mysqli_connect_errno()) {
		msg_log(ERROR, "Failed to connect to MySQL database [" . DB_NAME ."] on server: [". DB_USER . "@" . DB_HOST . ":" . DB_PORT . "].", NOTIFY);	
	   return CONNECT_FAILED;
	}
	msg_log(DEBUG, "Connected to MySQL database [" . DB_NAME ."] on server: [". DB_USER . "@" . DB_HOST . ":" . DB_PORT . "].", SILENT);
	
	// Execute query
	msg_log(DEBUG, "Executing SQL: [". $sqlQuery ."].", SILENT);
	if (mysqli_query($connection, $sqlQuery)) {
	
		mysqli_close($connection);
		return COMMAND_OK;
	} else {
	
		mysqli_close($connection);
		return SQL_FAILED;
	}
}

/*
* Name: selectData
* Desc: Retrieves data from the specified database and conditions
* Inpt:	[Table name], [Columnn names as array of strings || *], [Condition as string]
* Inpt:	$table			-> Type: String, Value: [Table name]
*		$columnNames	-> Type: Array, Value: [Columnn names as strings]
*		$condition		-> Type: String, Value: [Condition as string]
* Outp: Type: Object, Value: SQL Response on succes, Error code otherwise
* Date: 22.03.2007
*/
function selectData($table, $columnNames, $condition) {
	
	$sqlQuery = 'SELECT ';
	//echo "table[".$table."], columnNames[".$columnNames."], condition[".$condition."] - ";

	// Create SQL statement
	if (isset($columnNames) && ($columnNames != '*')) {
	
		if (is_array($columnNames)) {
		
			foreach($columnNames as $columnName) {
			
				$sqlQuery = $sqlQuery.$columnName.', ';
			}
			$sqlQuery = substr($sqlQuery, 0, -2);
			
		} else {
			$sqlQuery = $sqlQuery.$columnNames;
		}
		
		$sqlQuery = $sqlQuery.' FROM '.$table;

	} else {	
	
		$sqlQuery = "SELECT * FROM ".$table;
	}

	// Apply any conditions if any
	if (!empty($condition)) {
		
		$sqlQuery = $sqlQuery.' WHERE '.$condition;
	}

	// Connect to the database
	$connection = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	
	// Verify connection
	if (!$connection) {
		msg_log(ERROR, "Failed to connect to MySQL database [" . DB_NAME ."] on server: [". DB_USER . "@" . DB_HOST . ":" . DB_PORT . "].", NOTIFY);
		return CONNECT_FAILED;
	}
	msg_log(DEBUG, "Connected to MySQL database [" . DB_NAME ."] on server: [". DB_USER . "@" . DB_HOST . ":" . DB_PORT . "].", SILENT);
		
	// Get query result
	msg_log(DEBUG, "Executing SQL: [". $sqlQuery ."].", SILENT);
	$sqlResult = mysqli_query($connection, $sqlQuery);
	
	// Close connection
	mysqli_close($connection);
	
	return $sqlResult;
}

/*
* Name: removeData
* Desc: Removes data from the database by the specified conditions
* Inpt:	$table		-> Type: String, Value: [Table name]
*		$condition	-> Type: String, Value: [Condition as string]
* Outp: SQL Response on succes, Error code otherwise
* Date: 24.03.2007
*/
function removeData($table, $condition) {
	
	$sqlQuery = 'DELETE FROM '.$table;

	// Apply any conditions if any
	if (!empty($condition)) {
		
		$sqlQuery = $sqlQuery.' WHERE '.$condition;
	}
	//error_log("SQL: " . $sqlQuery);
	// Connect to the database
	$connection = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	
	// Verify connection
	if (!$connection) {
		msg_log(ERROR, "Failed to connect to MySQL database [" . DB_NAME ."] on server: [". DB_USER . "@" . DB_HOST . ":" . DB_PORT . "].", NOTIFY);
		return CONNECT_FAILED;
	}
	msg_log(DEBUG, "Connected to MySQL database [" . DB_NAME ."] on server: [". DB_USER . "@" . DB_HOST . ":" . DB_PORT . "].", SILENT);
		
	// Get query result
	msg_log(DEBUG, "Executing SQL: [". $sqlQuery ."].", SILENT);
	$sqlResult = mysqli_query($connection, $sqlQuery);
	
	// Close connection
	mysqli_close($connection);
	
	return $sqlResult;
}
?>