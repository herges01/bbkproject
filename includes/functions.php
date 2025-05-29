<?php
#add your user defined functions here


#HTML GENERATION FUNCTIONS PROVIDED

function htmlHeading($text, $level)
{
	$heading = trim(strtolower($text));
	switch ($level) {
		case 1:
		case 2:
			$heading = ucwords($heading);
			break;
		case 3:
		case 4:
		case 5:
		case 6:
			$heading = ucfirst($heading);
			break;
		default: #traps unknown heading level exception
			$heading = '<FONT COLOR="#ff0000">Unknown heading level:' . $level . '</FONT>';
	}
	return '<h' . $level . '>' . htmlentities($heading) . '</h' . $level . '>';
}

function htmlParagraph($text)
{
	return '<p>' . htmlentities(trim($text)) . '</p>';
}


function htmlNAV($navData, $URLparams)
{
	$html = '';
	foreach ($navData as $key => $menuitem) {
		$html .= "<a href=\"index.php?$URLparams=$key\" class=\"w3-bar-item w3-button w3-hover-white\">$menuitem</a>";
	}
	return $html;
}



function clearFormPlaceholders()
{
	$placeHolders = [
		'[+uName+]' => '',
		'[+pwd+]' => '',
		'[+loginError+]' => '',
		'[+loggedInName+]' => ''
	];
	return $placeHolders;
}

#function to validate all the form data
#return Boolean flag, clean data array and placeholders array
function validateformData($formData)
{
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
	} else {
		$validData = false;
		$formPlaceholders['[+loginError+]'] = 'Username or password is incorrect';
	}

	if (validPassword(trim($formData['password']))) {
		$cleanData['password'] = trim($formData['password']);
	} else {
		$validData = false;
		$formPlaceholders['[+loginError+]'] = 'Username or password is incorrect';
	}
	#Return valid data Boolean, clean data array and placeholders array     
	return [$validData, $cleanData, $formPlaceholders];
}

function validUserName($username)
{
	#At least 10 characters and alphanumeric
	if (strlen($username) >= 10 and ctype_alnum($username)) {
		return true;
	} else {
		return false;
	}
}

function validPassword($pwd)
{
	#match a-z
	$containsLower = preg_match('/[a-z]/', $pwd);
	#match A-Z
	$containsUpper = preg_match('/[A-Z]/', $pwd);
	#match any digit
	$containsDigit = preg_match('/\d/', $pwd);
	#match special characters
	$specialCharacters = preg_match('/[£$%&*~#]/', $pwd);
	if (
		(strlen($pwd) < 10) or (!$containsLower) or (!$containsUpper) or
		(!$containsDigit) or (!$specialCharacters)
	) {
		return false; #if any of the match conditions fail
	} else {
		return true; #only if all match conditions pass
	}
}

function UserExistsCheck($cleanData, $pdo, $db, $tableName)
{
	$sql = "SELECT COUNT(*) FROM `{$db}`.`{$tableName}` WHERE  Username = :username AND Password = :password"; #retrieves the username and password data to check them
	try {
		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(':username', $cleanData['userName']);#binds the params in a prepared statement before executing
		$stmt->bindParam(':password', $cleanData['password']);
		$stmt->execute();

		$count = $stmt->fetchColumn();

		if ($count == 1) { #if the count is 1, a match has been found, validating the username and password
			return true;
		} else {
			return false;
		}
	} catch (PDOException $e) {
		$errorCode = $e->getCode();
		$errorMessage = $e->getMessage();
		return htmlParagraph("$errorCode : $errorMessage");
	}
}

function SessionSet($data, $cleanData)
{
	$_SESSION['uname'] = $cleanData['userName'];
	$_SESSION['pwd'] = $cleanData['password'];
}

function UserDataFetcher($pdo, $db, $tableName)
{
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

function NAVsetter($data)
{
	$selectedNAV = ['home' => 'Home'];  #default value
	if (isset($_SESSION['uname']) && isset($_SESSION['pwd'])) { #This is to ensure the correct nav menu always loads 
		$selectedNAV = ['home' => 'Home', 'userTransaction' => 'User Transaction', 'userInput' => 'User Input', 'userDelUp' => 'User Delete & Update', 'graph' => 'Graph', 'userSettings' => 'User Settings'];
	} else {
		$selectedNAV = ['home' => 'Home', 'userCreation' => 'User Creation'];
	}
	return $selectedNAV;
}

function TableCreate($mainParams, $mainParamsAtt, $pdo, $db, $tableName)
{
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

function clearFormPlaceholdersLogin()
{
	$placeHolders = [
		'[+username+]' => '',
		'[+usernameError+]' => '',
		'[+password+]' => '',
		'[+passwordError+]' => ''
	];
	return $placeHolders;
}
function validateformDatalogin($formData)
{
	#process the submitted form data setting placeholders and validates all the form data elements
	$validData = true; #assume all form data is valid until any one form element fails
	$cleanData = array(); #array to hold form data which passes validation
	$formPlaceholders = clearFormPlaceholdersLogin(); #reset all form placeholders to NULL

	#set the value placeholders for the form data submitted
	$formPlaceholders['[+username+]'] = trim(htmlentities($formData['username']));
	$formPlaceholders['[+password+]'] = trim(htmlentities($formData['password']));

	#validate the individual form data elements; setting clean data and any errors messages

	if (!isset($formData['username']) || empty(trim($formData['username']))) {
		$validData = false;
		$formPlaceholders['[+usernameError+]'] = 'Username is required';
	} elseif (!validUserName(trim($formData['username']))) {
		$validData = false;
		$formPlaceholders['[+usernameError+]'] = 'Username must be at least 10 characters and alphanumeric';
	} else {
		$cleanData['username'] = trim($formData['username']);
	}

	if (!isset($formData['password']) || empty(trim($formData['password']))) {
		$validData = false;
		$formPlaceholders['[+passwordError+]'] = 'Password is required';
	} elseif (!validPassword(trim($formData['password']))) {
		$validData = false;
		$formPlaceholders['[+passwordError+]'] = 'Password must be at least 10 characters; include one uppercase, lowercase, plus a digit';
	} else {
		$cleanData['password'] = trim($formData['password']);
	}
	#Return valid data Boolean, clean data array and placeholders array     
	return [$validData, $cleanData, $formPlaceholders];
}
function htmlTable($data)
{
	if (!is_array($data) || empty($data)) {
		return '<p>No data available</p>';
	}
	#build HTML table from 2D associative key data array
	$html = '<table>'; #begin table tag
	//create table header from first row of data using the keys
	$html .= "<tr>"; #create table row tag
	foreach (array_keys($data[0]) as $key) { //create each <th> element
		$key = htmlentities(trim($key)); #sanitise table data
		$html .= "<th>$key</th>";
	}
	$html .= "</tr>"; #close table row tag and new line
	#create table data rows
	foreach ($data as $row) {
		$html .= "<tr>"; #create table row tag
		foreach ($row as $value) { #create each <td> element
			$data = htmlentities(trim($value)); //sanitise table data
			$html .= "<td>$value</td>";
		}
		$html .= "</tr>"; //close table row tag and new line
	}
	$html .= "</table>";
	return $html; //return final HTML code 
}
function UserDuplicateTester($cleanData, $mainParams, $pdo, $db, $tableName)
{
	# Check if the record already exists to make sure there would be no duplicates
	$sql = "SELECT COUNT(*) FROM `{$db}`.`{$tableName}` WHERE `{$mainParams['username']}` = :username";
	try {
		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(':username', $cleanData['username']); #binds the params and prepares the statement to avoid injection attacks
		$stmt->execute();

		$count = $stmt->fetchColumn();

		if ($count > 0) {
			return false;
		} else {
			return true;
		}
	} catch (PDOException $e) {
		$errorCode = $e->getCode();
		$errorMessage = $e->getMessage();
		return htmlParagraph("$errorCode : $errorMessage");
	}
}
function clearInputFormPlaceholders()
{
	$placeHolders = [
		'[+id+]' => '',
		'[+idError+]' => '',
		'[+date+]' => '',
		'[+dateError+]' => '',
		'[+transaction+]' => '',
		'[+transactionError+]' => '',
		'[+amount+]' => '',
		'[+amountError+]' => '',
		'[+necessitySelected+]' => '',
		'[+wantSelected+]' => '',
		'[+emergencySelected+]' => '',
		'[+transactionCategoryError+]' => ''
	];
	return $placeHolders;
}

#function to validate all the form data
#return Boolean flag, clean data array and placeholders array
function validateInputformData($formData)
{
	$validData = true;
	$cleanData = array();
	$formPlaceholders = clearInputFormPlaceholders();
	$userName = $_SESSION['uname'];

	// Set selected category placeholder (if exists)
	if (isset($formData['transactionCategory'])) {
		$categoryTypeSelected = "[+{$formData['transactionCategory']}Selected+]";
		$formPlaceholders[$categoryTypeSelected] = 'selected';
	}

	$formPlaceholders['[+date+]'] = isset($formData['date']) ? trim(htmlentities($formData['date'])) : '';
	$formPlaceholders['[+transaction+]'] = isset($formData['transaction']) ? trim(htmlentities($formData['transaction'])) : '';
	$formPlaceholders['[+amount+]'] = isset($formData['amount']) ? trim(htmlentities($formData['amount'])) : '';

	if (isset($_GET['view']) && $_GET['view'] == 'userDelUp') {
		if (!isset($formData['id']) || empty(trim($formData['id']))) {
			$validData = false;
			$formPlaceholders['[+idError+]'] = 'ID is required';
		} elseif (!validId(trim($formData['id']))) {
			$validData = false;
			$formPlaceholders['[+idError+]'] = 'ID must be a number';
		} else {
			$cleanData['id'] = trim($formData['id']);
		}

		if (isset($_POST['userDeleteSubmitted'])) {
			return [$validData, $cleanData, $formPlaceholders];
		}
	}

	// 🔽 General input validation
	if (!isset($formData['transaction']) || empty(trim($formData['transaction']))) {
		$validData = false;
		$formPlaceholders['[+transactionError+]'] = 'Transaction is required';
	} elseif (!validTransaction(trim($formData['transaction']))) {
		$validData = false;
		$formPlaceholders['[+transactionError+]'] = 'Transaction must be a string';
	} else {
		$cleanData['transaction'] = trim($formData['transaction']);
	}

	if (!isset($formData['date']) || empty(trim($formData['date']))) {
		$validData = false;
		$formPlaceholders['[+dateError+]'] = 'Date is required';
	} elseif (!validDate(trim($formData['date']))) {
		$validData = false;
		$formPlaceholders['[+dateError+]'] = 'Date must be in the format YYYY-MM-DD';
	} else {
		$cleanData['date'] = trim($formData['date']);
	}

	if (!isset($formData['amount']) || empty(trim($formData['amount']))) {
		$validData = false;
		$formPlaceholders['[+amountError+]'] = 'Amount is required';
	} elseif (!validTransactionAmount(trim($formData['amount']))) {
		$validData = false;
		$formPlaceholders['[+amountError+]'] = 'Amount must be a number';
	} else {
		$cleanData['amount'] = trim($formData['amount']);
	}

	if (!isset($formData['transactionCategory']) || empty(trim($formData['transactionCategory']))) {
		$validData = false;
		$formPlaceholders['[+transactionCategoryError+]'] = 'Category is required';
	} elseif (!validTransactionCategory(trim($formData['transactionCategory']))) {
		$validData = false;
		$formPlaceholders['[+transactionCategoryError+]'] = 'Transaction category must be one of "Necessity", "Want", or "Emergency"';
	} else {
		$cleanData['transactionCategory'] = trim($formData['transactionCategory']);
	}

	// ✅ Regular return for non-admin or input form views
	return [$validData, $cleanData, $formPlaceholders];
}

function validId($id)
{
	if (is_numeric($id)) {
		return true;
	} else {
		return false;
	}
}
function validTransactionCategory($transactionCategory)
{
	if (in_array($transactionCategory, array('Necessity', 'Want', 'Emergency'))) {
		return true;
	} else {
		return false;
	}
}

function validDate($date)
{
	if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
		return true;
	} else {
		return false;
	}
}

function validTransaction($transaction)
{
	if (is_string($transaction)) {
		return true;
	} else {
		return false;
	}
}
function validTransactionAmount($amount)
{
	if (is_numeric($amount)) {
		return true;
	} else {
		return false;
	}
}

function DataInserter($cleanData, $mainParams, $pdo, $db, $tableName)
{

	$sql = "INSERT INTO `{$db}`.`{$tableName}` (`{$mainParams['username']}`,`{$mainParams['password']}`) 
			VALUES 
			 (:username, :password)";
	try {
		$stmt = $pdo->prepare($sql); #use PDO query method to insert data into table from $sql
		$stmt->bindParam(':username', $cleanData['username']); #binds the params and prepares the statement to avoid injection attacks
		$stmt->bindParam(':password', $cleanData['password']);
		$stmt->execute();
	} catch (PDOException $e) {
		$errorCode = $e->getCode();
		$errorMessage = $e->getMessage();
		if ($errorCode == 23000) {
			return "<p>Data INSERT failed – duplicate data.</p>";
		} else {
			return htmlParagraph("$errorCode : $errorMessage");
		}
	}
}

function DataInputInserter($cleanData, $mainParams, $pdo, $db, $tableName)
{
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
	} catch (PDOException $e) {
		$errorCode = $e->getCode();
		$errorMessage = $e->getMessage();
		if ($errorCode == 23000) {
			return "<p>Data INSERT failed – duplicate data.</p>";
		} else {
			return htmlParagraph("$errorCode : $errorMessage");
		}
	}
}

function DataUpdateInserter($cleanData, $mainParams, $pdo, $db, $tableName)
{
	$sql = "UPDATE `{$db}`.`{$tableName}` SET `{$mainParams['transaction']}` = :transaction, `{$mainParams['amount']}` = :amount, `{$mainParams['category']}` = :category WHERE `{$mainParams['id']}` = :id";
	try {
		$stmt = $pdo->prepare($sql); #use PDO query method to insert data into table from $sql
		$stmt->bindParam(':transaction', $cleanData['transaction']);
		$stmt->bindParam(':amount', $cleanData['amount']);
		$stmt->bindParam(':category', $cleanData['transactionCategory']);
		$stmt->execute();
	} catch (PDOException $e) {
		$errorCode = $e->getCode();
		$errorMessage = $e->getMessage();
		if ($errorCode == 23000) {
			return "<p>Data INSERT failed – duplicate data.</p>";
		} else {
			return htmlParagraph("$errorCode : $errorMessage");
		}
	}
}

function DateRange($fromDate, $toDate, $pdo, $db, $tableName, $mainParams)
{
	$sql = "SELECT * FROM `{$db}`.`{$tableName}` WHERE `{$mainParams['date']}` BETWEEN :fromDate AND :toDate";
	try {
		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(':fromDate', $fromDate);
		$stmt->bindParam(':toDate', $toDate);
		$stmt->execute();
		$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $data;
	} catch (PDOException $e) {
		$errorCode = $e->getCode();
		$errorMessage = $e->getMessage();
		return htmlParagraph("$errorCode : $errorMessage");
	}
}
function DateCategoryRange($fromDate, $toDate, $pdo, $db, $tableName, $mainParams,$category)
{
	$sql = "SELECT * FROM `{$db}`.`{$tableName}` WHERE `{$mainParams['date']}` BETWEEN :fromDate AND :toDate AND `{$mainParams['category']}` = :category";
	try {
		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(':fromDate', $fromDate);
		$stmt->bindParam(':toDate', $toDate);
		$stmt->bindParam(':category', $category);
		$stmt->execute();
		$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $data;
	} catch (PDOException $e) {
		$errorCode = $e->getCode();
		$errorMessage = $e->getMessage();
		return htmlParagraph("$errorCode : $errorMessage");
	}
}

function DateAscending($data, $mainParams)
{
	usort($data, function ($a, $b) use ($mainParams) {
		return strtotime($a[$mainParams['date']]) <=> strtotime($b[$mainParams['date']]);
	});
	return $data;
}

function DateDescending($data, $mainParams)
{
	usort($data, function ($a, $b) use ($mainParams) {
		return strtotime($b[$mainParams['date']]) <=> strtotime($a[$mainParams['date']]);
	});
	return $data;
}

function TransactionCategory($pdo, $db, $tableName, $mainParams, $category)
{
	$sql = "SELECT * FROM `{$db}`.`{$tableName}` WHERE `{$mainParams['category']}` = :category";
	try {
		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(':category', $category);
		$stmt->execute();
		$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $data;
	} catch (PDOException $e) {
		$errorCode = $e->getCode();
		$errorMessage = $e->getMessage();
		return htmlParagraph("$errorCode : $errorMessage");
	}
}

function idTableRemover($data)
{
	foreach ($data as &$row) {
		unset($row['Transaction ID']);
	}
	unset($row);
	return $data;
}

function TotalSpent($pdo, $db, $tableName, $mainParams)
{
	$sql = "SELECT SUM(`{$mainParams['amount']}`) as 'Total Spent' FROM `{$db}`.`{$tableName}`";
	try {
		$stmt = $pdo->prepare($sql);
		$stmt->execute();
		$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $data;
	} catch (PDOException $e) {
		$errorCode = $e->getCode();
		$errorMessage = $e->getMessage();
		return htmlParagraph("$errorCode : $errorMessage");
	}
}

function TotalSpentByCategory($pdo, $db, $tableName, $mainParams, $category)
{
	$sql = "SELECT SUM(`{$mainParams['amount']}`) as 'Total Spent - {$category}' 
				FROM `{$db}`.`{$tableName}` 
				WHERE `{$mainParams['category']}` = :category";
	try {
		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(':category', $category);
		$stmt->execute();
		$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $data;
	} catch (PDOException $e) {
		$errorCode = $e->getCode();
		$errorMessage = $e->getMessage();
		return htmlParagraph("$errorCode : $errorMessage");
	}
}

function AmountAscending($data, $mainParams)
{
	usort($data, function ($a, $b) use ($mainParams) {
		return $a[$mainParams['amount']] <=> $b[$mainParams['amount']];
	});
	return $data;
}

function AmountDescending($data, $mainParams)
{
	usort($data, function ($a, $b) use ($mainParams) {
		return $b[$mainParams['amount']] <=> $a[$mainParams['amount']];
	});
	return $data;
}

function UpdateData($pdo, $db, $tableName, $mainParams, $cleanData)
{
	$sql = "UPDATE `{$db}`.`{$tableName}` SET `{$mainParams['date']}` = :date, `{$mainParams['transaction']}` = :transaction, `{$mainParams['amount']}` = :amount, `{$mainParams['category']}` = :category WHERE `{$mainParams['id']}` = :id";
	try {
		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(':date', $cleanData['date']);
		$stmt->bindParam(':transaction', $cleanData['transaction']);
		$stmt->bindParam(':amount', $cleanData['amount']);
		$stmt->bindParam(':category', $cleanData['transactionCategory']);
		$stmt->bindParam(':id', $cleanData['id']);
		$stmt->execute();
	} catch (PDOException $e) {
		$errorCode = $e->getCode();
		$errorMessage = $e->getMessage();
		return htmlParagraph("$errorCode : $errorMessage");
	}
}

function DeleteData($pdo, $db, $tableName, $mainParams, $cleanData)
{
	$sql = "DELETE FROM `{$db}`.`{$tableName}` WHERE `{$mainParams['id']}` = :id";
	try {
		// Delete the record
		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(':id', $cleanData['id']);
		$stmt->execute();

		// Get the new max ID
		$idColumn = $mainParams['id'];
		$maxIdSql = "SELECT MAX(`$idColumn`) AS max_id FROM `{$db}`.`{$tableName}`";
		$stmt = $pdo->query($maxIdSql);
		$maxId = $stmt->fetchColumn();

		// Set AUTO_INCREMENT to max_id + 1 (or 1 if table is now empty)
		$nextId = ($maxId === null) ? 1 : ($maxId + 1);
		$autoIncSql = "ALTER TABLE `{$db}`.`{$tableName}` AUTO_INCREMENT = $nextId";
		$pdo->exec($autoIncSql);

	} catch (PDOException $e) {
		$errorCode = $e->getCode();
		$errorMessage = $e->getMessage();
		return htmlParagraph("$errorCode : $errorMessage");
	}
}

function idMatcher($cleanData, $mainParams, $pdo, $db, $tableName)
{
	$sql = "SELECT * FROM `{$db}`.`{$tableName}` WHERE `{$mainParams['id']}` = :id";
	try {
		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(':id', $cleanData['id']);
		$stmt->execute();
		$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if (count($data) > 0) {
			return true;
		} else {
			return false;
		}
	} catch (PDOException $e) {
		$errorCode = $e->getCode();
		$errorMessage = $e->getMessage();
		return htmlParagraph("$errorCode : $errorMessage");
	}
}

function clearFormPlaceholdersSettings()
{
	$placeHolders = [
		'[+oldUsername+]' => '',
		'[+newUsername+]' => '',
		'[+oldUsernameError+]' => '',
		'[+newUsernameError+]' => '',
		'[+password+]' => '',
		'[+passwordError+]' => '',
		'[+newPassword+]' => '',
		'[+newPasswordError+]' => ''
	];
	return $placeHolders;
}
function validateformDataSettings($formData)
{
	$validData = true;
	$cleanData = array();
	$placeholdersSettings = array();

	// Initialize placeholders
	$placeholdersSettings = [
		'[+oldUsername+]' => '',
		'[+oldUsernameError+]' => '',
		'[+newUsername+]' => '',
		'[+newUsernameError+]' => '',
		'[+password+]' => '',
		'[+passwordError+]' => '',
		'[+newPassword+]' => '',
		'[+newPasswordError+]' => ''
	];

	// Validate username change
	if (isset($formData['userDataSubmittedUsername'])) {
		// Validate old username
		if (empty($formData['oldUsername'])) {
			$validData = false;
			$placeholdersSettings['[+oldUsernameError+]'] = 'Current username is required';
		} else {
			$cleanData['oldUsername'] = trim($formData['oldUsername']);
			$placeholdersSettings['[+oldUsername+]'] = $cleanData['oldUsername'];
		}

		// Validate new username
		if (empty($formData['newUsername'])) {
			$validData = false;
			$placeholdersSettings['[+newUsernameError+]'] = 'New username is required';
		} else {
			$cleanData['newUsername'] = trim($formData['newUsername']);
			$placeholdersSettings['[+newUsername+]'] = $cleanData['newUsername'];

			// Check if new username is different from old username
			if (isset($cleanData['oldUsername']) && $cleanData['newUsername'] === $cleanData['oldUsername']) {
				$validData = false;
				$placeholdersSettings['[+newUsernameError+]'] = 'New username must be different from current username';
			}

			// Validate username format
			if (!preg_match('/^[a-zA-Z0-9]{10,30}$/', $cleanData['newUsername'])) {
				$validData = false;
				$placeholdersSettings['[+newUsernameError+]'] = 'Username must be 10-30 characters and alphanumeric only';
			}
		}
	}

	// Validate password change
	if (isset($formData['userDataSubmittedPassword'])) {
		// Validate new password (first password input)
		if (empty($formData['password'])) {
			$validData = false;
			$placeholdersSettings['[+passwordError+]'] = 'New password is required';
		} else {
			$cleanData['password'] = trim($formData['password']);
			$placeholdersSettings['[+password+]'] = $cleanData['password'];

			// Validate password format
			if (!validPassword($cleanData['password'])) {
				$validData = false;
				$placeholdersSettings['[+passwordError+]'] = 'Password must be 10-15 characters and include uppercase, lowercase, number, and special character';
			}
		}

		// Validate current password matches new password confirmation (second password input)
		if (empty($formData['newPassword'])) {
			$validData = false;
			$placeholdersSettings['[+newPasswordError+]'] = 'Please confirm the new password';
		} else {
			$cleanData['newPassword'] = trim($formData['newPassword']);
			$placeholdersSettings['[+newPassword+]'] = $cleanData['newPassword'];

			// Check if confirmation matches new password
			if (isset($cleanData['password']) && $cleanData['newPassword'] !== $cleanData['password']) {
				$validData = false;
				$placeholdersSettings['[+newPasswordError+]'] = 'Passwords do not match';
			}
		}
	}

	return array($validData, $cleanData, $placeholdersSettings);
}

function updateUsername($pdo, $db, $tableName, $oldUsername, $newUsername)
{
	try {
		$sql = "UPDATE `{$db}`.`{$tableName}` SET `Username` = :newUsername WHERE `Username` = :oldUsername";
		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(':newUsername', $newUsername);
		$stmt->bindParam(':oldUsername', $oldUsername);
		$stmt->execute();

		if ($stmt->rowCount() === 0) {
			throw new PDOException("No user updated - old username may not exist");
		}
		return true;
	} catch (PDOException $e) {
		error_log("Update username in users table failed: " . $e->getMessage());
		return false;
	}
}

function renameTable($pdo, $db, $oldUsername, $newUsername)
{
	try {
		$oldTableName = "{$oldUsername}TransactionsTable";
		$newTableName = "{$newUsername}TransactionsTable";

		// Check if the old transactions table exists
		$checkSql = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
                     WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :tableName";
		$stmt = $pdo->prepare($checkSql);
		$stmt->execute([':db' => $db, ':tableName' => $oldTableName]);
		$tableExists = $stmt->fetchColumn() > 0;

		if ($tableExists) {
			$renameSql = "RENAME TABLE `{$db}`.`{$oldTableName}` TO `{$db}`.`{$newTableName}`";
			$result = $pdo->exec($renameSql);
			if ($result === false) {
				throw new PDOException("Failed to rename transactions table");
			}
		}
		return true;
	} catch (PDOException $e) {
		error_log("Rename transactions table failed: " . $e->getMessage());
		return false;
	}
}

function updatePassword($pdo, $db, $tableName, $newPassword, $username)
{
	try {
		$pdo->beginTransaction();

		$sql = "UPDATE `{$db}`.`{$tableName}` SET `Password` = :newPassword WHERE `Username` = :username";

		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(':newPassword', $newPassword);
		$stmt->bindParam(':username', $username);

		$stmt->execute();

		$affected = $stmt->rowCount();
		error_log("Password update affected rows: {$affected}");

		if ($affected === 0) {
			throw new PDOException("No rows updated — username may be incorrect");
		}

		$pdo->commit();
		return true;
	} catch (PDOException $e) {
		if ($pdo->inTransaction()) {
			$pdo->rollBack();
		}
		error_log("Password update failed: " . $e->getMessage());
		return false;
	}
}
?>	