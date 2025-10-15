<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR System - ETL Processor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card { margin-bottom: 20px; }
        .success { color: #198754; }
        .error { color: #dc3545; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">HR System - Payroll ETL Processor</h1>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">ETL Process Control</h5>
                    </div>
                    <div class="card-body">
                        <p>Run the ETL process to extract employee data from MySQL, transform it into payroll format, and export to JSON for the Finance system.</p>
                        
                        <form method="POST">
                            <button type="submit" name="run_etl" class="btn btn-primary">Run ETL Process</button>
                            <a href="../shared-data/payroll_export.json" target="_blank" class="btn btn-outline-secondary">View Export File</a>
                        </form>
                        
                        <?php
                        if (isset($_POST['run_etl'])) {
                            require_once 'etl-processor.php';
                            $etl = new HREtlProcessor();
                            $result = $etl->exportToFinanceSystem();
                            
                            echo '<div class="mt-3 alert ' . ($result['status'] == 'success' ? 'alert-success' : 'alert-danger') . '">';
                            echo '<h6>ETL Process Result:</h6>';
                            echo '<pre>' . print_r($result, true) . '</pre>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Current Employee Data (MySQL)</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        require_once 'etl-processor.php';
                        $etl = new HREtlProcessor();
                        $employees = $etl->extractEmployeeData();
                        
                        if ($employees) {
                            echo '<div class="table-responsive">';
                            echo '<table class="table table-striped">';
                            echo '<thead><tr><th>ID</th><th>Name</th><th>Department</th><th>Basic Pay</th></tr></thead>';
                            echo '<tbody>';
                            foreach ($employees as $emp) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($emp['emp_id']) . '</td>';
                                echo '<td>' . htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) . '</td>';
                                echo '<td>' . htmlspecialchars($emp['department']) . '</td>';
                                echo '<td>$' . number_format($emp['basic_pay'], 2) . '</td>';
                                echo '</tr>';
                            }
                            echo '</tbody></table></div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">System Information</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>XAMPP Components:</strong></p>
                        <ul>
                            <li>Apache Web Server</li>
                            <li>MySQL Database</li>
                            <li>PHP <?php echo phpversion(); ?></li>
                        </ul>
                        
                        <p><strong>Integration Points:</strong></p>
                        <ul>
                            <li>MySQL Data Extraction</li>
                            <li>PHP Data Transformation</li>
                            <li>JSON File Export</li>
                            <li>C# Finance System Import</li>
                        </ul>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Quick Links</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li><a href="http://localhost/phpmyadmin" target="_blank">phpMyAdmin</a></li>
                            <li><a href="../shared-data/payroll_export.json" target="_blank">Payroll Export</a></li>
                            <li><a href="api/get-payroll.php" target="_blank">Payroll API</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>