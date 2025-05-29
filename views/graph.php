<?php
ob_start();
$headTitle = "Graph View";
$viewHeading = htmlHeading("Graph View", 2);
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
if (isset($_POST['dateSubmitted']) && !empty($_POST['dateSubmitted'])) {
    if (
        isset($_POST['fromDate']) && !empty($_POST['fromDate']) &&
        isset($_POST['toDate']) && !empty($_POST['toDate'])
    ) {
        $fromDate = $_POST['fromDate'];
        $toDate = $_POST['toDate'];
        $data = DateRange($fromDate, $toDate, $pdo, $db, $tableName, $mainParams);
    } else if (isset($_POST['fromDate']) && !empty($_POST['fromDate'])) {
        $fromDate = $_POST['fromDate'];
        $today = date('Y-m-d');
        $data = DateRange($fromDate, $today, $pdo, $db, $tableName, $mainParams);
    } else if (isset($_POST['toDate']) && !empty($_POST['toDate'])) {
        $toDate = $_POST['toDate'];
        $data = DateRange('1970-01-01', $toDate, $pdo, $db, $tableName, $mainParams);
    } else if (isset($_POST['transactionCategory']) && !empty($_POST['transactionCategory']) && $_POST['transactionCategory'] !== 'All') {
        $data = TransactionCategory($pdo, $db, $tableName, $mainParams, $_POST['transactionCategory']);
        $totalSpentByCategory = TotalSpentByCategory($pdo, $db, $tableName, $mainParams, $_POST['transactionCategory']);
    }
    $totalSpent = TotalSpent($pdo, $db, $tableName, $mainParams);
    $content .= htmlTable($totalSpent);
    if ($totalSpentByCategory !== null) {
        $content .= htmlTable(idTableRemover($totalSpentByCategory));
    }
    if (isset($_POST['orderByDate'])) {
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
} else {
    $data = UserDataFetcher($pdo, $db, $tableName);
    $totalSpent = TotalSpent($pdo, $db, $tableName, $mainParams);
    $content .= htmlTable($totalSpent);
}

$dataPoints = [];
foreach ($data as $info) {
    $date = $info['Date'];
    $transaction = $info['Transaction']; // Assuming key matches DB field
    $label = "{$date}\n({$transaction})";
    $dataPoints[] = array("y" => $info['Amount'], "label" => $label);
}

?>
<!DOCTYPE HTML>
<html>

<head>
    <script>
        window.onload = function () {

            var chart = new CanvasJS.Chart("chartContainer", {
                animationEnabled: true,
                theme: "light2",
                title: {
                    text: "Amount Spent"
                },
                axisY: {
                    title: "Amount Spent"
                },
                data: [{
                    type: "column",
                    yValueFormatString: "#,##0.## Spent",
                    dataPoints: <?php echo json_encode($dataPoints, JSON_NUMERIC_CHECK); ?>
                }]
            });
            chart.render();

        }
    </script>
</head>

<body>
    <div id="chartContainer" style="height: 370px; width: 100%;"></div>
    <script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
</body>

</html>
<?php
$content .= ob_get_clean();
?>