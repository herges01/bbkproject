<!DOCTYPE html>
<html lang="en">
<head>
    <title>BSc Computing Project User Creation System</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<link rel="stylesheet" type="text/css" href="css/styles.css">

</head>
<body>
<?php
	session_start();
/*	Web Programming Using PHP Cwk2 Task 4 
	This script acts as the controller for a Single Point of Entry MVC design model
	It's main functions are:
	1. Include user defined functions and create a new PDO connection
	2. Display login form and home page
	3. If user submits login ID and pwd via login form, validate
	4. If authenticated credentials create session and build NAV based on user type; display user and logout form
	5. If user saved cookie, use to set last view, or display home view
	6. If authenticated credentials allow browsing to NAV views and include and display any selected view
	7. If user selects logout, destroy session and set user cookie for 10 days saving last view
	
*/

	
	
	require_once 'includes/functions.php';  #user defined function library
	require_once 'includes/config.php'; #database settings and connection
	
	print_r($_SESSION);
	print_r($_GET);
	print_r($_POST);
	if (isset($_GET['view'])) {
    $view = $_GET['view'];
	} 
	else {
    $view = 'home';
	}	
	#include model code for view selected
	switch ($view) {
		case 'home' :
			include 'views/home.php';
			break;
		case 'userTransaction' :
			include 'views/userTransaction.php';
			break;
		case 'userInput' :
			include 'views/userInput.php';
			break;
		case 'admin' :
			include 'views/admin.php';
			break;
			case 'userCreation' :
				include 'views/userCreation.php';
				break;
		default :
			include 'views/404.php';
	}
	
	
	if(isset($_SESSION['usertype'])){ #This is to ensure the correct nav menu always loads 
		switch ($_SESSION['usertype']) {
			case 'userTransaction':
				$selectedNAV = ['home'=>'Home','student'=>'Student'];
				break;
			case 'userInput':
				$selectedNAV = ['home'=>'Home','student'=>'Student','academic'=> 'Academic'];
				break;
			case 'admin' :
				$selectedNAV = ['home'=>'Home','userTransaction'=> 'User Transaction','userInput'=> 'User Input','admin'=> 'Admin'];
				break;
			case 'userCreation':
				$selectedNAV = ['home'=>'Home','userInput'=>'User Input'];
				break;
		}
	}
	else{
		$selectedNAV = ['home'=>'Home','userCreation'=>'User Creation'];
	}
	
	
	$tableName = "usersTable";
	$validData = true; #assume form data will be valid unless set to false by validation function
	$cleanData = array(); #holds form data which has passed validation
	$placeholders = clearFormPlaceholders();
	$form = '';
	$loginOutForm = '';
	
	if (isset($_POST['logout'])) {
		$userName = $_SESSION['uname'];
		$tenDays =  time() + (10*24 * 60 * 60);
		$yesterday = time() - (24 * 60 * 60); #set timestamp in past
		if (isset($_COOKIE[$userName])){ #deletes a cookie that already exists to update it with the last known page being browsed
			setcookie($userName,"",$yesterday);
		}
		setcookie($userName,$view,$tenDays);
		$_SESSION = array(); #clear the session array data
		if (ini_get("session.use_cookies")) { #delete the session cookie
		$params = session_get_cookie_params();#get session cookie parameters
		#use setcookie() to delete the session cookie
		setcookie(session_name(), '', $yesterday,
		$params["path"], $params["domain"],
		$params["secure"], $params["httponly"]);
		}
		session_destroy(); #PHP destroys the session, remove data from server
		#reload current page if needed
		header('Location: '.$_SERVER['PHP_SELF']);
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
		if(UserExistsCheck($cleanData,$pdo, $db, $tableName)){ #ensures the details match
			$usertype = UserTypeFetcher($cleanData,$pdo, $db, $tableName); 
			SessionSet($_POST,$cleanData,$usertype); #sets the name of the sessions info
			$selectedNAV = NAVsetter($_SESSION);
			$userName = $_SESSION['uname'];
			if (isset($_COOKIE[$userName])){
				header('Location: ' . $_SERVER['PHP_SELF'] . '?view=' . $_COOKIE[$userName]); #after logging in, redirects you to the last page you were on
			}
		}
		else {
			$placeholders['[+loginError+]'] = "Wrong username or password";
			$template = file_get_contents('html/loginFormTemplate.html'); #get the html form template
			$loginOutForm = str_replace(array_keys($placeholders), array_values($placeholders), $template);
		}
	}
	else { #display the html form with any clean data or error messages
		$template = file_get_contents('html/loginFormTemplate.html'); #get the html form template
		$loginOutForm = str_replace(array_keys($placeholders), array_values($placeholders), $template);
	}
	
	if (isset($_SESSION['uname']) and isset($_SESSION['pwd'])) { #this is to make sure the form shows the logged out one while a session is active
		$placeholders['[+loggedInName+]'] = $_SESSION['uname'];
		$template = file_get_contents('html/logoutFormTemplate.html'); #get the html form template
		$loginOutForm = str_replace(array_keys($placeholders), array_values($placeholders), $template);
	}
	
	$heading2 = htmlHeading("Transaction Management System with graphical representation",2);
	$nav = htmlNAV($selectedNAV,'view');
	$template = file_get_contents('html/pageTemplate.html'); #get the html template contents
	echo str_replace(['[+title+]','[+heading2+]','[+loginOutForm+]','[+nav+]','[+heading+]','[+content+]','[+form+]'], [$headTitle,$heading2,$loginOutForm,$nav,$viewHeading,$content,$form], $template);
?>
</body>
</html>