<?php
$headTitle = 'PHP Cwk2 Home Page';
$viewHeading = htmlHeading('Home Page View',2);
$content = htmlParagraph("Set up the following users in the database for testing...");
$content .= htmlParagraph("username:ubadmin001 password:Aaaa1111## userType:admin");
$content .= htmlParagraph("username:ubacadem01 password:Aaaa1111## userType:academic");
$content .= htmlParagraph("username:fflintoff01 password:Aaaa1111## userType:student");
$content .= htmlParagraph("Users in the database:");
#add your code here to display users stored in the database
$tableName = "usersTable";
$data = UserDataFetcher($pdo,$db,$tableName);
		foreach ($data as $info){
			$content .= htmlParagraph('username: ' . $info['Username'] . ' , ' . 'password: ' . $info['Password'] . ' , ' . 'userType: ' . $info['Usertype']);
		}
		?>
