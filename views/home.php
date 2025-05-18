<?php
$headTitle = 'BSc Computing Project: Transaction Management System';
$viewHeading = htmlHeading('Home Page View',2);
#add your code here to display users stored in the database
$tableName = "projectUsersTable";
$content = htmlHeading("This is a site designed to help you manage your finances. You can add, delete, and update your transactions, and view them in a table or graph.",3);
$content .= htmlParagraph("Users in the database: (Only one user is shown for testing purposes)");
$data = UserDataFetcher($pdo,$db,$tableName);
if(count($data) > 0){	
    $content .= htmlParagraph('Username: ' . $data[0]['Username'] . ' ' . 'Password: ' . $data[0]['Password']);
}
else{
    $content .= htmlParagraph("No users found");
}
?>
