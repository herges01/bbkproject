<?php
#add your user defined functions here


#HTML GENERATION FUNCTIONS PROVIDED

function htmlHeading($text, $level) {
	$heading = trim(strtolower($text));
	switch ($level) {
		case 1 :
		case 2 :
			$heading = ucwords($heading);
			break;
		case 3 :
		case 4 :
		case 5 :
		case 6 :
			$heading = ucfirst($heading);
			break;
		default: #traps unknown heading level exception
			$heading = '<FONT COLOR="#ff0000">Unknown heading level:' . $level . '</FONT>';
		}
	return '<h' . $level . '>' . htmlentities($heading) . '</h' . $level .  '>';
}

function htmlParagraph($text) {
	return '<p>' . htmlentities(trim($text)) . '</p>';
}


function htmlNAV($navData,$URLparams) { 
#$navData array with key=URL parameter data value data=menu option
#N.B. all links will re-load index.php and pass URL parameters
    $html = '<nav>'; #add NAV and UL element tags
    $html .= '<ul>';
    foreach ($navData as $key => $menuitem) { #build the NAV links
        $html .= "<li><a href=\"index.php?$URLparams=$key\">$menuitem</a></li>";
    }
    $html .= '</ul>'; #close NAV and UL element tags
    $html .= '</nav>';
    return $html;
}



	function clearFormPlaceholders() {
		$placeHolders = ['[+uName+]'=>'',
                     '[+pwd+]'=>'',
                     '[+loginError+]'=>'',
					 '[+loggedInName+]'=>''
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
		$formPlaceholders['[+uName+]'] = trim(htmlentities($formData['userName']));
		$formPlaceholders['[+pwd+]'] = trim(htmlentities($formData['password']));
		$formPlaceholders['[+loggedInName+]'] = trim(htmlentities($formData['userName']));
    
		#validate the individual form data elements; setting clean data and any errors messages
    
		if (validUserName(trim($formData['userName']))) { 
			$cleanData['userName'] = trim($formData['userName']); #store in clean data array
		} 
		else {
			$validData = false;
			$formPlaceholders['[+loginError+]'] = 'Username or password is incorrect';
		}
    
		if (validPassword(trim($formData['password']))) {
			$cleanData['password'] = trim($formData['password']);
		} 
		else {
			$validData = false;
			$formPlaceholders['[+loginError+]'] = 'Username or password is incorrect';
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

	function validPassword($pwd) {
		#match a-z
		$containsLower  = preg_match('/[a-z]/', $pwd); 
		#match A-Z
		$containsUpper  = preg_match('/[A-Z]/', $pwd); 
		#match any digit
		$containsDigit   = preg_match('/\d/', $pwd);
		#match special characters
		$specialCharacters = preg_match('/[£$%&*~#]/', $pwd);
		if ((strlen($pwd) < 10 ) or (!$containsLower) or (!$containsUpper) or 
			(!$containsDigit) or (!$specialCharacters)) {
			return false; #if any of the match conditions fail
		} 
		else {
			return true; #only if all match conditions pass
		}
	}
		
	function UserExistsCheck($cleanData, $pdo, $db, $tableName) {
			$sql = "SELECT COUNT(*) FROM `{$db}`.`{$tableName}` WHERE  Username = :username AND Password = :password"; #retrieves the username and password data to check them
			try {
				$stmt = $pdo->prepare($sql);
				$stmt->bindParam(':username', $cleanData['userName']);#binds the params in a prepared statement before executing
				$stmt->bindParam(':password', $cleanData['password']);
				$stmt->execute();
        
				$count = $stmt->fetchColumn();
        
				if ($count == 1) { #if the count is 1, a match has been found, validating the username and password
					return true;
				}
				else {
					return false;
				}
			}
			catch (PDOException $e) {
				$errorCode = $e->getCode();
				$errorMessage = $e->getMessage();
				return htmlParagraph("$errorCode : $errorMessage");
			}
		}
		
		function SessionSet($data,$cleanData){
		$_SESSION['uname'] = $cleanData['userName'];
		$_SESSION['pwd'] = $cleanData['password'];
		}
		
		function UserDataFetcher($pdo,$db,$tableName){
		$sql = "SELECT  *
                    FROM `{$db}`.`{$tableName}`"; #retrieves the data from the database
            try {
                    $stmt = $pdo->query($sql);       #execute the SQL statement with inserted data
                    #use PDOStatement class method fetchAll() to retrieve table rows into array
                    $data = $stmt->fetchAll(PDO::FETCH_ASSOC); #retrieves all the data in the form of an array
					return $data;
            } catch (PDOException $e) { #handle any errors
                    $errorCode = $e->getCode();
                    $errorMessage = $e->getMessage();
                    echo "</p>$errorCode : $errorMessage</p>";
                    return [];
            }	
		}
		
		function NAVsetter($data){
			$selectedNAV = ['home'=>'Home'];  #default value
			if(isset($data['usertype'])){  #checks if the usertype has been found
				switch ($data['usertype']) {
				case 'student':
					return $selectedNAV = ['home'=>'Home','student'=>'Student'];
					break;
				case 'academic':
					return $selectedNAV = ['home'=>'Home','student'=>'Student','academic'=> 'Academic'];
					break;
				case 'admin' :
					return $selectedNAV = ['home'=>'Home','student'=>'Student','academic'=> 'Academic','admin'=> 'Admin'];
					break;
				}
			}
			return $selectedNAV;
		}

		function TableCreate($mainParams, $mainParamsAtt, $pdo, $db, $tableName) {
			$sql = "CREATE TABLE IF NOT EXISTS `{$db}`.`{$tableName}` (";
		
			$columns = [];
			foreach ($mainParams as $key => $columnName) {
				$columnType = $mainParamsAtt[$key . 'A'];
				$columns[] = "`$columnName` $columnType";
			}
		
			// Add columns + PRIMARY KEY
			$sql .= implode(", ", $columns);
			
			// Ensure 'id' exists before using as primary key
			if (isset($mainParams['id'])) {
				$sql .= ", PRIMARY KEY (`{$mainParams['id']}`)";
			}
		
			$sql .= ")";
		
			try {
				$stmt = $pdo->query($sql);
			} catch (PDOException $e) {
				$errorMessage = $e->getMessage();
				return "<p>Failed to CREATE table {$tableName}; error message: $errorMessage</p>";
			}
		}
		
		function clearFormPlaceholdersLogin() {
			$placeHolders = [
						 '[+username+]'=>'',
						 '[+usernameError+]'=>'',
						 '[+password+]'=>'',
						 '[+passwordError+]'=>''
						];
			return $placeHolders;
		}
		function validateformDatalogin($formData) {
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
			function htmlTable($data) {
				if (!is_array($data) || empty($data)) {
					return '<p>No data available</p>';
				}
				#build HTML table from 2D associative key data array
				$html = '<table>'; #begin table tag
				//create table header from first row of data using the keys
				$html .= "<tr>"; #create table row tag
				foreach(array_keys($data[0]) as $key) { //create each <th> element
					$key = htmlentities(trim($key)); #sanitise table data
					$html .= "<th>$key</th>";
				}
				$html .= "</tr>"; #close table row tag and new line
				#create table data rows
				foreach($data as $row) {
					$html .= "<tr>"; #create table row tag
					foreach($row as $value) { #create each <td> element
						$data = htmlentities(trim($value)); //sanitise table data
						$html .= "<td>$value</td>";
					}
					$html .= "</tr>"; //close table row tag and new line
				}
				$html .= "</table>";
				return $html; //return final HTML code 
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
			function clearInputFormPlaceholders() {
				$placeHolders = [
							 '[+date+]'=>'',
							 '[+dateError+]'=>'',
							 '[+transaction+]'=>'',
							 '[+transactionError+]'=>'',
							 '[+amount+]'=>'',
							 '[+amountError+]'=>'',
							 '[+necessitySelected+]'=>'',
							 '[+wantSelected+]'=>'',
							 '[+emergencySelected+]'=>'',
							 '[+transactionCategoryError+]'=>''
							];
				return $placeHolders;
			}
		
			#function to validate all the form data
			#return Boolean flag, clean data array and placeholders array
			function validateInputformData($formData) {
				#process the submitted form data setting placeholders and validates all the form data elements
				$validData = true; #assume all form data is valid until any one form element fails
				$cleanData = array(); #array to hold form data which passes validation
				$formPlaceholders = clearInputFormPlaceholders(); #reset all form placeholders to NULL
			
				#set the value placeholders for the form data submitted
				$categoryTypeSelected = "[+{$formData['transactionCategory']}Selected+]";
				$formPlaceholders[$categoryTypeSelected] = 'selected';
				$formPlaceholders['[+date+]'] = isset($formData['date']) ? trim(htmlentities($formData['date'])) : '';
				$formPlaceholders['[+transaction+]'] = isset($formData['transaction']) ? trim(htmlentities($formData['transaction'])) : '';
				$formPlaceholders['[+amount+]'] = isset($formData['amount']) ? trim(htmlentities($formData['amount'])) : '';
				
			
				#validate the individual form data elements; setting clean data and any errors messages
			
			
				if (!isset($formData['transaction']) || empty(trim($formData['transaction']))) {
					$validData = false;
					$formPlaceholders['[+transactionError+]'] = 'Transaction is required';
				}
				else if (!validTransaction(trim($formData['transaction']))) {
					$validData = false;
					$formPlaceholders['[+transactionError+]'] = 'Transaction must be a string';
				}
				else {
					$cleanData['transaction'] = trim($formData['transaction']);
				}

				if (!isset($formData['date']) || empty(trim($formData['date']))) {
					$validData = false;
					$formPlaceholders['[+dateError+]'] = 'Date is required';
				}
				else if (!validDate(trim($formData['date']))) {
					$validData = false;
					$formPlaceholders['[+dateError+]'] = 'Date must be in the format YYYY-MM-DD';
				}
				else {
					$cleanData['date'] = trim($formData['date']);
				}

				if (!isset($formData['amount']) || empty(trim($formData['amount']))) {
					$validData = false;
					$formPlaceholders['[+amountError+]'] = 'Amount is required';
				}
				else if (!validTransactionAmount(trim($formData['amount']))) {
					$validData = false;
					$formPlaceholders['[+amountError+]'] = 'Amount must be a number';
				}
				else {
					$cleanData['amount'] = trim($formData['amount']);
				}

				if (!isset($formData['transactionCategory']) || empty(trim($formData['transactionCategory']))) {
					$validData = false;
					$formPlaceholders['[+transactionCategoryError+]'] = 'Category is required';
				}
				else if (!validTransactionCategory(trim($formData['transactionCategory']))) {
					$validData = false;
					$formPlaceholders['[+transactionCategoryError+]'] = 'Transaction category must be one of "Necessity", "Want", or "Emergency"';
				}
				else {
					$cleanData['transactionCategory'] = trim($formData['transactionCategory']);
				}

				return [$validData, $cleanData, $formPlaceholders];
			}

			function validTransactionCategory($transactionCategory) {
				if (in_array($transactionCategory, array('Necessity', 'Want', 'Emergency'))) {
					return true;
				}
				else {
					return false;
				}
			}

			function validDate($date) {
				if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
					return true;
				}
				else {
					return false;
				}
			}

			function validTransaction($transaction) {
				if (is_string($transaction)) {		
					return true;
				}
				else {
					return false;
				}
			}
			function validTransactionAmount($amount) {
				if (is_numeric($amount)) {	
					return true;
				}
				else {
					return false;
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

		function DataInputInserter($cleanData,$mainParams,$pdo,$db,$tableName){
			$sql = "INSERT INTO `{$db}`.`{$tableName}` (`{$mainParams['date']}`,`{$mainParams['transaction']}`,`{$mainParams['amount']}`,`{$mainParams['category']}`) 
		VALUES 
		 (:date, :transaction, :amount, :category)";
		try {
			$stmt = $pdo->prepare($sql); #use PDO query method to insert data into table from $sql
			$stmt->bindParam(':date', $cleanData['date']); #binds the params and prepares the statement to avoid injection attacks
			$stmt->bindParam(':transaction', $cleanData['transaction']);
			$stmt->bindParam(':amount', $cleanData['amount']);
			$stmt->bindParam(':category', $cleanData['transactionCategory']);
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

	function DateRange($fromDate,$toDate,$pdo,$db,$tableName,$mainParams){
		$sql = "SELECT * FROM `{$db}`.`{$tableName}` WHERE `{$mainParams['date']}` BETWEEN :fromDate AND :toDate";
		try {
			$stmt = $pdo->prepare($sql);
			$stmt->bindParam(':fromDate', $fromDate);
			$stmt->bindParam(':toDate', $toDate);
			$stmt->execute();
			$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
			return $data;
		}
		catch (PDOException $e) {
			$errorCode = $e->getCode();
			$errorMessage = $e->getMessage();
			return htmlParagraph("$errorCode : $errorMessage");
		}
	}		

	function DateAscending($pdo,$db,$tableName,$mainParams){
		$sql = "SELECT * FROM `{$db}`.`{$tableName}` ORDER BY `{$mainParams['date']}` ASC";
		try {
			$stmt = $pdo->prepare($sql);
			$stmt->execute();
			$data = $stmt->fetchAll(PDO::FETCH_ASSOC);	
			return $data;
		}
		catch (PDOException $e) {
			$errorCode = $e->getCode();
			$errorMessage = $e->getMessage();
			return htmlParagraph("$errorCode : $errorMessage");
		}					
	}

	function DateDescending($pdo,$db,$tableName,$mainParams){
		$sql = "SELECT * FROM `{$db}`.`{$tableName}` ORDER BY `{$mainParams['date']}` DESC";
		try {
			$stmt = $pdo->prepare($sql);
			$stmt->execute();
			$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
			return $data;
		}
		catch (PDOException $e) {
			$errorCode = $e->getCode();
			$errorMessage = $e->getMessage();
			return htmlParagraph("$errorCode : $errorMessage");
		}
	}	

	function TransactionCategory($pdo,$db,$tableName,$mainParams,$category){
		$sql = "SELECT * FROM `{$db}`.`{$tableName}` WHERE `{$mainParams['category']}` = :category";
		try {
			$stmt = $pdo->prepare($sql);
			$stmt->bindParam(':category', $category);
			$stmt->execute();		
			$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
			return $data;
		}
		catch (PDOException $e) {
			$errorCode = $e->getCode();
			$errorMessage = $e->getMessage();
			return htmlParagraph("$errorCode : $errorMessage");
		}
	}	

	function TotalSpent($pdo,$db,$tableName,$mainParams){
		$sql = "SELECT SUM(`{$mainParams['amount']}`) as 'Total Spent' FROM `{$db}`.`{$tableName}`";
		try {
			$stmt = $pdo->prepare($sql);
			$stmt->execute();	
			$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
			return $data;
		}
		catch (PDOException $e) {
			$errorCode = $e->getCode();
			$errorMessage = $e->getMessage();
			return htmlParagraph("$errorCode : $errorMessage");	
		}
	}	

	function TotalSpentByCategory($pdo,$db,$tableName,$mainParams,$category){
		$sql = "SELECT SUM(`{$mainParams['amount']}`) as 'Total Spent - {$category}' 
				FROM `{$db}`.`{$tableName}` 
				WHERE `{$mainParams['category']}` = :category";
		try {
			$stmt = $pdo->prepare($sql);
			$stmt->bindParam(':category', $category);
			$stmt->execute();
			$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
			return $data;
		}
		catch (PDOException $e) {	
			$errorCode = $e->getCode();
			$errorMessage = $e->getMessage();
			return htmlParagraph("$errorCode : $errorMessage");
		}
	}

	function AmountAscending($pdo,$db,$tableName,$mainParams){
		$sql = "SELECT * FROM `{$db}`.`{$tableName}` ORDER BY `{$mainParams['amount']}` ASC";
		try {
			$stmt = $pdo->prepare($sql);
			$stmt->execute();
			$data = $stmt->fetchAll(PDO::FETCH_ASSOC);	
			return $data;
		}
		catch (PDOException $e) {
			$errorCode = $e->getCode();
			$errorMessage = $e->getMessage();
			return htmlParagraph("$errorCode : $errorMessage");
		}
	}

	function AmountDescending($pdo,$db,$tableName,$mainParams){
		$sql = "SELECT * FROM `{$db}`.`{$tableName}` ORDER BY `{$mainParams['amount']}` DESC";
		try {
			$stmt = $pdo->prepare($sql);
			$stmt->execute();
			$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
			return $data;
		}
		catch (PDOException $e) {
			$errorCode = $e->getCode();
			$errorMessage = $e->getMessage();	
			return htmlParagraph("$errorCode : $errorMessage");
		}
	}

	function UpdateData($pdo,$db,$tableName,$mainParams,$cleanData){
		$sql = "UPDATE `{$db}`.`{$tableName}` SET `{$mainParams['transaction']}` = :transaction, `{$mainParams['amount']}` = :amount, `{$mainParams['category']}` = :category WHERE `{$mainParams['id']}` = :id";
		try {
			$stmt = $pdo->prepare($sql);
			$stmt->bindParam(':transaction', $cleanData['transaction']);
			$stmt->bindParam(':amount', $cleanData['amount']);
			$stmt->bindParam(':category', $cleanData['transactionCategory']);
			$stmt->bindParam(':id', $cleanData['id']);
			$stmt->execute();
		}
		catch (PDOException $e) {	
			$errorCode = $e->getCode();
			$errorMessage = $e->getMessage();
			return htmlParagraph("$errorCode : $errorMessage");
		}
	}	

	function DeleteUser($pdo, $db, $tableName, $userId) {
		$sql = "DELETE FROM `{$db}`.`{$tableName}` WHERE `userID` = :userId";
		try {
			$stmt = $pdo->prepare($sql);
			$stmt->bindParam(':userId', $userId);
			$stmt->execute();
			return true;
		}
		catch (PDOException $e) {
			$errorCode = $e->getCode();
			$errorMessage = $e->getMessage();
			return htmlParagraph("$errorCode : $errorMessage");
		}
	}
?>		

