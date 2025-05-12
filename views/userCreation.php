<?php
#Your PHP solution code should go here...
$headTitle = "User Creation View";
$viewHeading = htmlHeading("User Creation View", 2);
require_once 'includes/config.php';
require_once 'includes/functions.php';

$tableName = "projectUsersTable";
$mainParams = ['id' => 'userID', 'username' => 'Username', 'password' => 'Password'];
$mainParamsAtt = ['idA' => ' INT(11) NOT NULL AUTO_INCREMENT', 'usernameA' => ' VARCHAR(30) NOT NULL UNIQUE', 'passwordA' => ' VARCHAR(15) NOT NULL'];
$content = '';
$form = '';    #HTML form to be displayed in page template
$validData = true; #assume form data will be valid unless set to false by validation function
$cleanData = array(); #holds form data which has passed validation
$placeholdersLogin = clearFormPlaceholdersLogin(); #set all form placeholdersLogin to NULL (initial display)
echo '<pre>';
print_r($placeholdersLogin);
echo '</pre>';


if (isset($_POST['userDataClear'])) { #clears the form of all data
	$placeholdersLogin = clearFormPlaceholdersLogin();
}


if (isset($_POST['userDataSubmitted'])) { #data has been submitted to the form, validate it
	#validate the form data items and set form placeholdersLogin and clean data array
	$formData = validateFormDataLogin($_POST);
	#returns 3 values [0]valid data Boolean [1]clean data [2]form placeholdersLogin
	$validData = $formData[0]; #boolean returned to determine valid form data
	$cleanData = $formData[1]; #data items passed validation indexed by form field key
	$placeholdersLogin = $formData[2]; #placeholder names are keys -> data
}

if (isset($_POST['userDataSubmitted']) and $validData) { #form submitted and no errors
	if (UserDuplicateTester($cleanData, $mainParams, $pdo, $db, $tableName)) { #checks theres no duplicates
		$content .= htmlParagraph('New user' . $cleanData['username'] . ' successfully inserted into the database'); #display clean array
		$placeholdersLogin = clearFormPlaceholdersLogin($placeholdersLogin); #after successfully inserting the data we clear the data for the next data entry
		DataInserter($cleanData, $mainParams, $pdo, $db, $tableName); #inserts all the data into the database
		$template = file_get_contents('html/userCreationForm.html'); #get the html form template
		$form = str_replace(array_keys($placeholdersLogin), array_values($placeholdersLogin), $template);
	} else {
		$content .= '<p style="color:red">' . 'Duplicate data found</p>';
		$template = file_get_contents('html/userCreationForm.html'); #get the html form template
		echo '<pre>';
		print_r($placeholdersLogin);
		echo '</pre>';
		$placeholdersLogin = 
		$form = str_replace(array_keys($placeholdersLogin), array_values($placeholdersLogin), $template);
	}
} else { #display the html form with any clean data or error messages
	if (!$validData) { #there are errors - prompt user to fix
		$content .= '<p style="color:red">' . 'There are data errors in your form; Please correct the 
					errors highlighted in red below:</p>';
	}
	$template = file_get_contents('html/userCreationForm.html'); #get the html form template
	$form = str_replace(array_keys($placeholdersLogin), array_values($placeholdersLogin), $template);
}

TableCreate($mainParams, $mainParamsAtt, $pdo, $db, $tableName);
$template = file_get_contents('html/userCreationTemplate.html'); #get the html template contents
$content .= $form;
$content .= htmlHeading("Users Stored In The Database", 2);
$data = UserDataFetcher($pdo, $db, $tableName);
foreach ($data as $info) {
	$content .= htmlParagraph('ID: ' . $info['userID'] . ' , ' . 'Username: ' . $info['Username']);
}
?>