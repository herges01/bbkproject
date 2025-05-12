<?php
$headTitle = "Output View";
$viewHeading = htmlHeading("Output View",2);
require_once 'includes/config.php';
require_once 'includes/functions.php';

$userName = $_SESSION['uname'];
$tableName = "{$userName}TransactionsTable";
$mainParams = ['id' =>'Transaction ID','date' =>'Date','transaction' =>'Transaction','amount' =>'Amount','category' =>'Category'];
$mainParamsAtt = ['idA' => ' INT(11) NOT NULL AUTO_INCREMENT', 'dateA' =>' DATE NOT NULL','transactionA' =>' VARCHAR(50) NOT NULL','amountA' =>' DECIMAL(10,2) NOT NULL','categoryA' =>' VARCHAR(15) NOT NULL'];
TableCreate($mainParams,$mainParamsAtt,$pdo,$db,$tableName);
$content = '';
$form = ''; 
$data = UserDataFetcher($pdo,$db,$tableName);
$totalSpentByCategory = null;

$template = file_get_contents('html/outputForm.html');
$form = str_replace('[+form]',$form, $template);
$content .= $form;
if(isset($_POST['dateSubmitted']) && !empty($_POST['dateSubmitted'])) {
    if(isset($_POST['fromDate']) && !empty($_POST['fromDate']) && 
       isset($_POST['toDate']) && !empty($_POST['toDate'])) {
        $fromDate = $_POST['fromDate'];
        $toDate = $_POST['toDate'];
        $data = DateRange($fromDate,$toDate,$pdo,$db,$tableName,$mainParams);   
    }
    else if(isset($_POST['fromDate']) && !empty($_POST['fromDate'])) {
        $fromDate = $_POST['fromDate'];
        $today = date('Y-m-d');
        $data = DateRange($fromDate,$today,$pdo,$db,$tableName,$mainParams);
    }
    else if(isset($_POST['toDate']) && !empty($_POST['toDate'])) {
        $toDate = $_POST['toDate'];
        $data = DateRange('1970-01-01',$toDate,$pdo,$db,$tableName,$mainParams);
    }
    else if(isset($_POST['transactionCategory']) && !empty($_POST['transactionCategory']) && $_POST['transactionCategory'] !== 'All'){
        $data = TransactionCategory($pdo,$db,$tableName,$mainParams,$_POST['transactionCategory']);
        $totalSpentByCategory = TotalSpentByCategory($pdo,$db,$tableName,$mainParams,$_POST['transactionCategory']);
    } 
    $totalSpent = TotalSpent($pdo,$db,$tableName,$mainParams);  
    $content .= htmlTable($totalSpent);
    if($totalSpentByCategory !== null){
        $content .= htmlTable($totalSpentByCategory);
    }
    $content .= htmlTable($data);
}
else if(isset($_POST['ascSubmitted']) && !empty($_POST['ascSubmitted'])){
    $data = DateAscending($pdo,$db,$tableName,$mainParams);
    $totalSpent = TotalSpent($pdo,$db,$tableName,$mainParams);  
    $content .= htmlTable($totalSpent);
    $content .= htmlTable($data);   
}
else if(isset($_POST['descSubmitted']) && !empty($_POST['descSubmitted'])){
    $data = DateDescending($pdo,$db,$tableName,$mainParams);
    $totalSpent = TotalSpent($pdo,$db,$tableName,$mainParams);  
    $content .= htmlTable($totalSpent);
    $content .= htmlTable($data);
}        
else if(isset($_POST['ascAmountSubmitted']) && !empty($_POST['ascAmountSubmitted'])){
    $data = AmountAscending($pdo,$db,$tableName,$mainParams);
    $totalSpent = TotalSpent($pdo,$db,$tableName,$mainParams);  
    $content .= htmlTable($totalSpent);
    $content .= htmlTable($data);
}
else if(isset($_POST['descAmountSubmitted']) && !empty($_POST['descAmountSubmitted'])){
    $data = AmountDescending($pdo,$db,$tableName,$mainParams);
    $totalSpent = TotalSpent($pdo,$db,$tableName,$mainParams);  
    $content .= htmlTable($totalSpent);
    $content .= htmlTable($data);
}   
else{
    $data = UserDataFetcher($pdo,$db,$tableName);
    $totalSpent = TotalSpent($pdo,$db,$tableName,$mainParams);  
    $content .= htmlTable($totalSpent);
    $content .= htmlTable($data);
}   
?>