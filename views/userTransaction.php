<?php
$headTitle = "Output View";
$viewHeading = htmlHeading("Output View", 2);
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
$totalSpentByCategory = null;

$template = file_get_contents('html/outputForm.html');
$form = str_replace('[+form]', $form, $template);
$content .= $form;
if (isset($_POST['dateSubmitted']) && !empty($_POST['dateSubmitted'])) { #this is the search button being clicked
    if ( #both dates are set
        isset($_POST['fromDate']) && !empty($_POST['fromDate']) &&
        isset($_POST['toDate']) && !empty($_POST['toDate'])
    ) {
        $fromDate = $_POST['fromDate'];
        $toDate = $_POST['toDate'];
        if (isset($_POST['transactionCategory']) && !empty($_POST['transactionCategory']) && $_POST['transactionCategory'] !== 'All') {
            $data = DateCategoryRange($fromDate, $toDate, $pdo, $db, $tableName, $mainParams, $_POST['transactionCategory']);
            $totalSpentByCategory = TotalSpentByCategory($pdo, $db, $tableName, $mainParams, $_POST['transactionCategory']);
        } else {
            $data = DateRange($fromDate, $toDate, $pdo, $db, $tableName, $mainParams);
        }
    } else if (isset($_POST['fromDate']) && !empty($_POST['fromDate'])) { #only from date set
        $fromDate = $_POST['fromDate'];
        $today = date('Y-m-d');
        if (isset($_POST['transactionCategory']) && !empty($_POST['transactionCategory']) && $_POST['transactionCategory'] !== 'All') {
            $data = DateCategoryRange($fromDate, $today, $pdo, $db, $tableName, $mainParams, $_POST['transactionCategory']);
            $totalSpentByCategory = TotalSpentByCategory($pdo, $db, $tableName, $mainParams, $_POST['transactionCategory']);
        } else {
            $data = DateRange($fromDate, $today, $pdo, $db, $tableName, $mainParams);
        }
    } else if (isset($_POST['toDate']) && !empty($_POST['toDate'])) { #only to date set
        $toDate = $_POST['toDate'];
        if (isset($_POST['transactionCategory']) && !empty($_POST['transactionCategory']) && $_POST['transactionCategory'] !== 'All') {
            $data = DateCategoryRange('1970-01-01', $toDate, $pdo, $db, $tableName, $mainParams, $_POST['transactionCategory']);
            $totalSpentByCategory = TotalSpentByCategory($pdo, $db, $tableName, $mainParams, $_POST['transactionCategory']);
        } else {
            $data = DateRange('1970-01-01', $toDate, $pdo, $db, $tableName, $mainParams);
        }
    } else if (isset($_POST['transactionCategory']) && !empty($_POST['transactionCategory']) && $_POST['transactionCategory'] !== 'All') { #category chosen
        $data = TransactionCategory($pdo, $db, $tableName, $mainParams, $_POST['transactionCategory']);
        $totalSpentByCategory = TotalSpentByCategory($pdo, $db, $tableName, $mainParams, $_POST['transactionCategory']);
    }
    $totalSpent = TotalSpent($pdo, $db, $tableName, $mainParams); #for total spent
    $content .= htmlTable($totalSpent);
    if ($totalSpentByCategory !== null) { #for category total spent, doesnt display if no category is chosen
        $content .= htmlTable(idTableRemover($totalSpentByCategory));
    }
    if (isset($_POST['orderByDate'])) { #radio button filtering options
        if ($_POST['orderByDate'] === 'asc') {
            $data = DateAscending($data, $mainParams);
        } elseif ($_POST['orderByDate'] === 'desc') {
            $data = DateDescending($data, $mainParams);
        }
    } elseif (isset($_POST['orderByAmount'])) {
        if ($_POST['orderByAmount'] === 'asc') {
            $data = AmountAscending($data, $mainParams);
        } elseif ($_POST['orderByAmount'] === 'desc') {
            $data = AmountDescending($data, $mainParams);
        }
    }
    $content .= htmlTable(idTableRemover($data));
} else {
    $data = UserDataFetcher($pdo, $db, $tableName);
    $totalSpent = TotalSpent($pdo, $db, $tableName, $mainParams);
    $content .= htmlTable($totalSpent);
    $content .= htmlTable(idTableRemover($data)); 
}
?>