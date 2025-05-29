<?php
#Your PHP solution code should go here...
$headTitle = "User Settings View";
$viewHeading = htmlHeading("User Settings View", 2);
require_once 'includes/config.php';
require_once 'includes/functions.php';


$tableName = "projectUsersTable";
$mainParams = ['id' => 'userID', 'username' => 'Username', 'password' => 'Password'];
$content = '';
$form = '';    #HTML form to be displayed in page template
$validData = true; #assume form data will be valid unless set to false by validation function
$cleanData = array(); #holds form data which has passed validation
$placeholdersSettings = clearFormPlaceholdersSettings(); #set all form placeholdersSettings to NULL (initial display)

# to ensure the collapible stays open 
$usernameCollapsibleState = isset($_POST['usernameCollapsibleState']) ? $_POST['usernameCollapsibleState'] : 'closed';
$passwordCollapsibleState = isset($_POST['passwordCollapsibleState']) ? $_POST['passwordCollapsibleState'] : 'closed';

if (isset($_POST['userDataClear'])) { #clears the form of all data
	$placeholdersSettings = clearFormPlaceholdersSettings();
}


if (isset($_POST['userDataSubmittedPassword']) || isset($_POST['userDataSubmittedUsername'])) {
	#data has been submitted to the form, validate it
	#validate the form data items and set form placeholdersSettings and clean data array
	$formData = validateformDataSettings($_POST);
	#returns 3 values [0]valid data Boolean [1]clean data [2]form placeholdersSettings
	$validData = $formData[0]; #boolean returned to determine valid form data
	$cleanData = $formData[1]; #data items passed validation indexed by form field key
	$placeholdersSettings = $formData[2]; #placeholder names are keys -> data	
}

if (isset($_POST['userDataSubmittedUsername']) && $validData) { #form submitted and no errors
	if (updateUsername($pdo, $db, $tableName, $cleanData['oldUsername'], $cleanData['newUsername'])) {
		renameTable($pdo, $db, $cleanData['oldUsername'], $cleanData['newUsername']);
		$content .= htmlParagraph('Successfully updated from ' . $cleanData['oldUsername'] . ' to ' . $cleanData['newUsername']);
		$_SESSION['uname'] = $cleanData['newUsername']; // Update session
		setcookie('uname', $cleanData['newUsername'], time() + (86400 * 30), "/"); // Update cookie
	} else {
		$content .= '<p style="color:red">Failed to update username. Please try again.</p>';
		error_log("Username update failed for user: " . $cleanData['oldUsername']);
	}
	$placeholdersSettings = clearFormPlaceholdersSettings($placeholdersSettings); #after successfully inserting the data we clear the data for the next data entry
	$template = file_get_contents('html/userSettingsForm.html'); #get the html form template
	$form = str_replace(array_keys($placeholdersSettings), array_values($placeholdersSettings), $template);
	$form = str_replace('[+usernameCollapsibleState+]', $usernameCollapsibleState, $form);
	$form = str_replace('[+passwordCollapsibleState+]', $passwordCollapsibleState, $form);
} else { #display the html form with any clean data or error messages
	if (!$validData) { #there are errors - prompt user to fix
		$content .= '<p style="color:red">' . 'There are data errors in your form; Please correct the 
					errors highlighted in red below:</p>';
	}
	$template = file_get_contents('html/userSettingsForm.html'); #get the html form template
	$form = str_replace(array_keys($placeholdersSettings), array_values($placeholdersSettings), $template);
	$form = str_replace('[+usernameCollapsibleState+]', $usernameCollapsibleState, $form);
	$form = str_replace('[+passwordCollapsibleState+]', $passwordCollapsibleState, $form);
}

if (isset($_POST['userDataSubmittedPassword']) && $validData) {
	#form submitted and no errors
	if (updatePassword($pdo, $db, $tableName, $cleanData['newPassword'], $_SESSION['uname'])) {
		$content .= htmlParagraph('Password successfully updated');
		$_SESSION['pwd'] = $cleanData['newPassword']; // Update session password
	} else {
		$content .= '<p style="color:red">Failed to update password. Please try again.</p>';
		error_log("Password update failed for user: " . $_SESSION['uname']);
	}
	$placeholdersSettings = clearFormPlaceholdersSettings($placeholdersSettings); #after successfully inserting the data we clear the data for the next data entry
	$template = file_get_contents('html/userSettingsForm.html'); #get the html form template
	$form = str_replace(array_keys($placeholdersSettings), array_values($placeholdersSettings), $template);
	$form = str_replace('[+usernameCollapsibleState+]', $usernameCollapsibleState, $form);
	$form = str_replace('[+passwordCollapsibleState+]', $passwordCollapsibleState, $form);
} else { #display the html form with any clean data or error messages
	$template = file_get_contents('html/userSettingsForm.html'); #get the html form template
	$form = str_replace(array_keys($placeholdersSettings), array_values($placeholdersSettings), $template);
	$form = str_replace('[+usernameCollapsibleState+]', $usernameCollapsibleState, $form);
	$form = str_replace('[+passwordCollapsibleState+]', $passwordCollapsibleState, $form);
}


$template = file_get_contents('html/userCreationTemplate.html'); #get the html template contents
$content .= $form;
?>