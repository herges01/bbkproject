 <?php
	
	function htmlHeading($text, $level) { #heading trim and function for easier use
    	$html = htmlentities(trim($text));
    	return "<h$level>$html</h$level>";
	}
	
	function htmlParagraph($text) {
		$html = htmlentities(trim($text));
		return "<p>$html</p>";
	}
	
	function clearFormPlaceholders() {
		$placeHolders = [
                     '[+username+]'=>'',
                     '[+usernameError+]'=>'',
                     '[+password+]'=>'',
                     '[+passwordError+]'=>''
                    ];
		return $placeHolders;
	}

	#function to validate all the form data
	#return Boolean flag, clean data array and placeholders array
	function validateformData($formData) {
		#process the submitted form data setting placeholders and validates all the form data elements
		$validData = true; #assume all form data is valid until any one form element fails
		$cleanData = array(); #array to hold form data which passes validation
		$formPlaceholders = clearFormPlaceholders(); #reset all form placeholders to NULL
    
		#set the value placeholders for the form data submitted
		$formPlaceholders['[+username+]'] = trim(htmlentities($formData['username']));
		$formPlaceholders['[+password+]'] = trim(htmlentities($formData['password']));
    
		#validate the individual form data elements; setting clean data and any errors messages
    
    
		if (validUserName(trim($formData['username']))) { 
			$cleanData['username'] = trim($formData['username']); #store in clean data array
		} 
		else {
			$validData = false;
			$formPlaceholders['[+usernameError+]'] = 'Username must be at least 10 characters and alphanumeric';
		}
    
		if (validPassword(trim($formData['password']))) {
			$cleanData['password'] = trim($formData['password']);
		} 
		else {
			$validData = false;
			$formPlaceholders['[+passwordError+]'] = '>= 10 characters; include one uppercase, lowercase, plus a digit';
		}
		#Return valid data Boolean, clean data array and placeholders array     
		return [$validData, $cleanData, $formPlaceholders];
		}


	function validUserName($username) {
		#At least 10 characters and alphanumeric
		if (strlen($username) >= 10 and ctype_alnum($username)) {
			return true;
		} 
		else {
        return false;
		}
	}

	function validPassword($password) {
		#match a-z
		$containsLower  = preg_match('/[a-z]/', $password); 
		#match A-Z
		$containsUpper  = preg_match('/[A-Z]/', $password); 
		#match any digit
		$containsDigit   = preg_match('/\d/', $password);
		#match special characters
		$specialCharacters = preg_match('/[£$%&*~#]/', $password);
		if ((strlen($password) < 10 ) or (!$containsLower) or (!$containsUpper) or 
			(!$containsDigit) or (!$specialCharacters)) {
			return false; #if any of the match conditions fail
		} 
		else {
			return true; #only if all match conditions pass
		}
	}


		function DataInserter($cleanData,$mainParams,$pdo,$db,$tableName){
					$sql = "INSERT INTO `{$db}`.`{$tableName}` (`{$mainParams['username']}`,`{$mainParams['password']}`) 
				VALUES 
				 (:username, :password)";
				try {
					$stmt = $pdo->prepare($sql); #use PDO query method to insert data into table from $sql
					$stmt->bindParam(':username', $cleanData['username']); #binds the params and prepares the statement to avoid injection attacks
					$stmt->bindParam(':password', $cleanData['password']);
					$stmt->execute();
				} 
				catch (PDOException $e) {
					$errorCode = $e->getCode();
					$errorMessage = $e->getMessage();
					if ($errorCode == 23000) {
						return "<p>Data INSERT failed – duplicate data.</p>";
					}
					else {
						return htmlParagraph("$errorCode : $errorMessage");
					}
				}
			}	
			
		function UserDuplicateTester($cleanData, $mainParams, $pdo, $db, $tableName) {
			# Check if the record already exists to make sure there would be no duplicates
			$sql = "SELECT COUNT(*) FROM `{$db}`.`{$tableName}` WHERE `{$mainParams['username']}` = :username";
			try {
				$stmt = $pdo->prepare($sql);
				$stmt->bindParam(':username', $cleanData['username']); #binds the params and prepares the statement to avoid injection attacks
				$stmt->execute();
        
				$count = $stmt->fetchColumn();
        
				if ($count > 0) {
					return false;
				}
				else {
					return true;
				}
			}
			catch (PDOException $e) {
				$errorCode = $e->getCode();
				$errorMessage = $e->getMessage();
				return htmlParagraph("$errorCode : $errorMessage");
			}
		}
		
		function TableCreate($mainParams, $mainParamsAtt, $pdo, $db, $tableName) {
			$sql = "CREATE TABLE IF NOT EXISTS `{$db}`.`{$tableName}` (";
			$columns = [];
			foreach ($mainParams as $key => $columnName) {
				$columnType = $mainParamsAtt[$key . 'A'];
				$columns[] = "`$columnName` $columnType";
			}
			$sql .= implode(", ", $columns);
			$sql .= ", PRIMARY KEY (`{$mainParams['id']}`)";
			$sql .= ")";
			try {
				$pdo->query($sql);
			} catch (PDOException $e) {
				$errorMessage = $e->getMessage();
				return htmlParagraph("Failed to CREATE table usersTable; error message: : $errorMessage");
			}
		}
		
		function UserDataFetcher($pdo,$db,$tableName){
		$sql = "SELECT  *FROM `{$db}`.`{$tableName}`"; #retrieves the data from the database
            try {
                    $stmt = $pdo->query($sql);       
                    #use PDOStatement class method fetchAll() to retrieve table rows into array
                    $data = $stmt->fetchAll(); #retrieves all the data in the form of an array
					return $data;
            } catch (PDOException $e) { #handle any errors
                    $errorCode = $e->getCode();
                    $errorMessage = $e->getMessage();
                    echo "</p>$errorCode : $errorMessage</p>";
                    return false;
            }	
		}
?> 	