<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../hr-system/etl-processor.php';

$etl = new HREtlProcessor();
$payrollData = $etl->getPayrollData();

if ($payrollData) {
    echo json_encode([
        'status' => 'success',
        'data' => $payrollData
    ], JSON_PRETTY_PRINT);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'No payroll data available'
    ], JSON_PRETTY_PRINT);
}
?>