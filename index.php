<?php
session_start();

require_once 'includes/functions.php';  #user defined function library
require_once 'includes/config.php'; #database settings and connection

// Set view from URL or default to 'home'
$view = isset($_GET['view']) ? $_GET['view'] : 'home';

//views that do not require login
$homeViews = ['home', 'userCreation'];

// views that require login
$loginViews = ['userTransaction', 'userInput', 'userDelUp', 'graph'];

// if trying to access a protected view without login, redirect to home view
if (!in_array($view, $homeViews) && (!isset($_SESSION['uname']) || !isset($_SESSION['pwd']))) {
	$view = 'home';
}

switch ($view) {
	case 'home':
		include 'views/home.php';
		break;
	case 'userTransaction':
		include 'views/userTransaction.php';
		break;
	case 'userInput':
		include 'views/userInput.php';
		break;
	case 'userDelUp':
		include 'views/userDelUp.php';
		break;
	case 'userCreation':
		include 'views/userCreation.php';
		break;
	case 'graph':
		include 'views/graph.php';
		break;
	case 'userSettings':
		include 'views/userSettings.php';
		break;
	default:
		include 'views/404.php';
		break;
}


if (isset($_SESSION['uname']) && isset($_SESSION['pwd'])) { #This is to ensure the correct nav menu always loads 
	$selectedNAV = ['home' => 'Home', 'userTransaction' => 'User Transaction', 'userInput' => 'User Input', 'userDelUp' => 'User Delete & Update', 'graph' => 'Graph', 'userSettings' => 'User Settings'];
} else {
	$selectedNAV = ['home' => 'Home', 'userCreation' => 'User Creation'];
}


$tableName = "projectUsersTable";
$validData = true; #assume form data will be valid unless set to false by validation function
$cleanData = array(); #holds form data which has passed validation
$placeholders = clearFormPlaceholders();
$form = '';
$loginOutForm = '';

if (isset($_POST['logout'])) {
	$userName = $_SESSION['uname'];
	$tenDays = time() + (10 * 24 * 60 * 60);
	$yesterday = time() - (24 * 60 * 60); #set timestamp in past
	if (isset($_COOKIE[$userName])) { #deletes a cookie that already exists to update it with the last known page being browsed
		setcookie($userName, "", $yesterday);
	}
	setcookie($userName, $view, $tenDays);
	$_SESSION = array(); #clear the session array data
	if (ini_get("session.use_cookies")) { #delete the session cookie
		$params = session_get_cookie_params();#get session cookie parameters
		#use setcookie() to delete the session cookie
		setcookie(
			session_name(),
			'',
			$yesterday,
			$params["path"],
			$params["domain"],
			$params["secure"],
			$params["httponly"]
		);
	}
	session_destroy(); #PHP destroys the session, remove data from server
	#reload current page if needed
	header('Location: ' . $_SERVER['PHP_SELF']);
}


if (isset($_POST['login'])) { #data has been submitted to the form, validate it
	#validate the form data items and set form placeholders and clean data array
	$formData = validateFormData($_POST);
	#returns 3 values [0]valid data Boolean [1]clean data [2]form placeholders
	$validData = $formData[0]; #boolean returned to determine valid form data
	$cleanData = $formData[1]; #data items passed validation indexed by form field key
	$placeholders = $formData[2]; #placeholder names are keys -> data
}

if (isset($_POST['login']) and $validData) { #form submitted and no errors
	if (UserExistsCheck($cleanData, $pdo, $db, $tableName)) { #ensures the details match
		SessionSet($_POST, $cleanData); #sets the name of the sessions info
		$selectedNAV = NAVsetter($_SESSION);
		$userName = $_SESSION['uname'];
		if (isset($_COOKIE[$userName]) && $_COOKIE[$userName] != 'userCreation') {
			header('Location: ' . $_SERVER['PHP_SELF'] . '?view=' . $_COOKIE[$userName]); #after logging in, redirects you to the last page you were on
		} else {
			header('Location: ' . $_SERVER['PHP_SELF'] . '?view=home');
		}
		exit();
	} else {
		$placeholders['[+loginError+]'] = "Wrong username or password";
		$template = file_get_contents('html/loginFormTemplate.html'); #get the html form template
		$loginOutForm = str_replace(array_keys($placeholders), array_values($placeholders), $template);
	}
} else { #display the html form with any clean data or error messages
	$template = file_get_contents('html/loginFormTemplate.html'); #get the html form template
	$loginOutForm = str_replace(array_keys($placeholders), array_values($placeholders), $template);
}

if (isset($_SESSION['uname']) and isset($_SESSION['pwd'])) { #this is to make sure the form shows the logged out one while a session is active
	$placeholders['[+loggedInName+]'] = $_SESSION['uname'];
	$template = file_get_contents('html/logoutFormTemplate.html'); #get the html form template
	$loginOutForm = str_replace(array_keys($placeholders), array_values($placeholders), $template);
}

$heading2 = htmlHeading("Transaction Management System with graphical representation", 2);
$nav = htmlNAV($selectedNAV, 'view');
$template = file_get_contents('html/pageTemplate.html'); #get the html template contents
echo str_replace(['[+title+]', '[+heading2+]', '[+loginOutForm+]', '[+nav+]', '[+heading+]', '[+content+]', '[+form+]'], [$headTitle, $heading2, $loginOutForm, $nav, $viewHeading, $content, $form], $template);
?>