<?php
$headTitle = 'PHP Cwk2 Home Page';
$viewHeading = htmlHeading('Home Page View',2);
$content = htmlParagraph("Users in the database:");
#add your code here to display users stored in the database
$tableName = "projectUsersTable";
$data = UserDataFetcher($pdo,$db,$tableName);
foreach ($data as $info){
	$content .= htmlParagraph('username: ' . $info['Username'] . ' , ' . 'password: ' . $info['Password']);
}

?>
