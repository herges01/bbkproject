<?php
$headTitle = "Output View";
$viewHeading = htmlHeading("Output View",2);
require_once 'includes/config.php';
require_once 'includes/functions.php';

$userName = $_SESSION['uname'];
$tableName = "{$userName}TransactionsTable";
$mainParams = ['id' =>'Transaction ID','date' =>'Date','transaction' =>'Transaction','amount' =>'Amount','category' =>'Category'];
$content = '';
$form = ''; 
$data = UserDataFetcher($pdo,$db,$tableName);

// Prepare data points for chart
$dataPoints = [];
$categoryTotals = [];

// Calculate totals for each category
foreach($data as $row) {
    $category = $row['Category'];
    $amount = floatval($row['Amount']);
    
    if(!isset($categoryTotals[$category])) {
        $categoryTotals[$category] = 0;
    }
    $categoryTotals[$category] += $amount;
}

// Convert to chart data format
foreach($categoryTotals as $category => $total) {
    $dataPoints[] = array(
        "label" => $category,
        "y" => $total
    );
}

$template = file_get_contents('html/outputForm.html');
$form = str_replace('[+form]',$form, $template);
$content .= $form;
if(isset($_POST['dateSubmitted']) && !empty($_POST['dateSubmitted'])) {
    if(isset($_POST['fromDate']) && !empty($_POST['fromDate']) && 
       isset($_POST['toDate']) && !empty($_POST['toDate'])) {
        // Both dates provided
        $fromDate = $_POST['fromDate'];
        $toDate = $_POST['toDate'];
        $data = DateRange($fromDate,$toDate,$pdo,$db,$tableName,$mainParams);   
    }
    else if(isset($_POST['fromDate']) && !empty($_POST['fromDate'])) {
        // Only fromDate provided
        $fromDate = $_POST['fromDate'];
        $data = DateRange($fromDate,date('Y-m-d'),$pdo,$db,$tableName,$mainParams);
    }
    else if(isset($_POST['toDate']) && !empty($_POST['toDate'])) {
        // Only toDate provided
        $toDate = $_POST['toDate'];
        $data = DateRange('1970-01-01',$toDate,$pdo,$db,$tableName,$mainParams);
    }
    $content .= htmlTable($data);
}
else if(isset($_POST['ascSubmitted']) && !empty($_POST['ascSubmitted'])){
    $data = DateAscending($pdo,$db,$tableName,$mainParams);
    $content .= htmlTable($data);
}
else if(isset($_POST['descSubmitted']) && !empty($_POST['descSubmitted'])){
    $data = DateDescending($pdo,$db,$tableName,$mainParams);
    $content .= htmlTable($data);
}   
else if(isset($_POST['transactionCategory']) && !empty($_POST['transactionCategory']) && $_POST['transactionCategory'] !== 'All'){
    $data = TransactionCategory($pdo,$db,$tableName,$mainParams,$_POST['transactionCategory']);
    $content .= htmlTable($data);
}       
else{
    $content .= htmlTable($data);
}   

// Add chart HTML and JavaScript
$content .= '
<!DOCTYPE HTML>
<html>
<head>
<style>
#chartContainer {
    margin-left: auto;
    margin-right: 0;
    width: 80%;
    float: right;
}
</style>
<script>
window.onload = function() {
    var chart = new CanvasJS.Chart("chartContainer", {
        animationEnabled: true,
        title: {
            text: "Transaction Categories Distribution"
        },
        subtitles: [{
            text: "Amount by Category"
        }],
        data: [{
            type: "pie",
            yValueFormatString: "#,##0.00\"%\"",
            indexLabel: "{label} ({y})",
            dataPoints: ' . json_encode($dataPoints, JSON_NUMERIC_CHECK) . '
        }]
    });
    chart.render();
}
</script>
</head>
<body>
<div id="chartContainer" style="height: 370px;"></div>
<script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
</body>
</html>';

print_r($_POST);
?>