<?php
$headTitle = "Update Transaction";
$viewHeading = htmlHeading("Update and delete Transaction", 2);

require_once 'includes/config.php';
require_once 'includes/functions.php';

$userName = $_SESSION['uname'];
$tableName = "{$userName}TransactionsTable";
$mainParams = ['id' => 'Transaction ID', 'date' => 'Date', 'transaction' => 'Transaction', 'amount' => 'Amount', 'category' => 'Category'];
$mainParamsAtt = ['idA' => ' INT(11) NOT NULL AUTO_INCREMENT', 'dateA' => ' DATE NOT NULL', 'transactionA' => ' VARCHAR(50) NOT NULL', 'amountA' => ' DECIMAL(10,2) NOT NULL', 'categoryA' => ' VARCHAR(15) NOT NULL'];
TableCreate($mainParams, $mainParamsAtt, $pdo, $db, $tableName);
$content = '';
$form = '';
$data = UserDataFetcher($pdo, $db, $tableName);
$validData = true; #assume form data will be valid unless set to false by validation function
$cleanData = array(); #holds form data which has passed validation
$placeholders = clearInputFormPlaceholders(); #set all form placeholders to NULL (initial display)


if (isset($_POST['userDataClear'])) { #clears the form of all data
    $placeholders = clearInputFormPlaceholders();
}


if (isset($_POST['userDeleteSubmitted']) || isset($_POST['userUpdateSubmitted'])) { #data has been submitted to the form, validate it
    #validate the form data items and set form placeholders and clean data array
    $formData = validateInputformData($_POST);
    #returns 3 values [0]valid data Boolean [1]clean data [2]form placeholders
    $validData = $formData[0]; #boolean returned to determine valid form data
    $cleanData = $formData[1]; #data items passed validation indexed by form field key
    $placeholders = $formData[2]; #placeholder names are keys -> data
}

if (isset($_POST['userDeleteSubmitted']) and $validData) { #form submitted and no errors
    if (idMatcher($cleanData, $mainParams, $pdo, $db, $tableName)) {            
        DeleteData($pdo, $db, $tableName, $mainParams, $cleanData); #correct order
        $placeholders = clearInputFormPlaceholders(); #after successfully inserting the data we clear the data for the next data entry
        $template = file_get_contents('html/updateDelForm.html'); #get the html form template
        $form = str_replace(array_keys($placeholders), array_values($placeholders), $template);
        $content .= htmlParagraph('Deleted data successfully');
    } else {
        $content .= '<p style="color:red">' . 'ID not found' . '</p>';
        $template = file_get_contents('html/updateDelForm.html'); #get the html form template
        $form = str_replace(array_keys($placeholders), array_values($placeholders), $template);
    }
} else if (isset($_POST['userUpdateSubmitted']) and $validData) {
    if (idMatcher($cleanData, $mainParams, $pdo, $db, $tableName)) {    
        UpdateData($pdo, $db, $tableName, $mainParams, $cleanData); #correct order
        $placeholders = clearInputFormPlaceholders(); #after successfully inserting the data we clear the data for the next data entry
        $template = file_get_contents('html/updateDelForm.html'); #get the html form template
        $form = str_replace(array_keys($placeholders), array_values($placeholders), $template);
        $content .= htmlParagraph('Updated data successfully');
    } else {
        $content .= '<p style="color:red">' . 'ID not found' . '</p>';
        $template = file_get_contents('html/updateDelForm.html'); #get the html form template
        $form = str_replace(array_keys($placeholders), array_values($placeholders), $template);
    }
} else { #display the html form with any clean data or error messages
    if (!$validData) { #there are errors - prompt user to fix
        $content .= '<p style="color:red">' . 'There are data errors in your form; Please correct the errors highlighted in red below:</p>';
    }
    $template = file_get_contents('html/updateDelForm.html'); #get the html form template
    $form = str_replace(array_keys($placeholders), array_values($placeholders), $template);
}

TableCreate($mainParams, $mainParamsAtt, $pdo, $db, $tableName);
$template = file_get_contents('html/userCreationTemplate.html'); #get the html template contents
$content .= $form;
$content .= htmlHeading("Transactions", 2);
$data = UserDataFetcher($pdo, $db, $tableName);
$content .= htmlTable($data);
?>