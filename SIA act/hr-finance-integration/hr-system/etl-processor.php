<?php
require_once 'config.php';

class HREtlProcessor {
    private $pdo;
    
    public function __construct() {
        $this->connectDatabase();
        $this->createSampleData();
    }
    
    private function connectDatabase() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8";
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Create sample HR database and data
     */
    private function createSampleData() {
        // Create employees table if not exists
        $createTable = "
        CREATE TABLE IF NOT EXISTS employees (
            id INT AUTO_INCREMENT PRIMARY KEY,
            emp_id VARCHAR(20) UNIQUE NOT NULL,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            department VARCHAR(50),
            basic_pay DECIMAL(10,2) NOT NULL,
            overtime_rate DECIMAL(5,2) DEFAULT 1.5,
            tax_rate DECIMAL(5,2) DEFAULT 0.15,
            health_insurance DECIMAL(8,2) DEFAULT 200.00,
            retirement_contribution DECIMAL(8,2) DEFAULT 300.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->pdo->exec($createTable);
        
        // Insert sample data if table is empty
        $checkCount = $this->pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
        
        if ($checkCount == 0) {
            $sampleData = [
                ['EMP001', 'john', 'doe', 'Engineering', 5000.00],
                ['EMP002', 'jane', 'smith', 'Marketing', 6000.00],
                ['EMP003', 'bob', 'johnson', 'Sales', 4500.00],
                ['EMP004', 'alice', 'williams', 'HR', 5500.00]
            ];
            
            $stmt = $this->pdo->prepare("
                INSERT INTO employees (emp_id, first_name, last_name, department, basic_pay) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            foreach ($sampleData as $data) {
                $stmt->execute($data);
            }
        }
    }
    
    /**
     * Extract: Get employee data from MySQL database
     */
    public function extractEmployeeData() {
        $sql = "
            SELECT 
                emp_id, first_name, last_name, department,
                basic_pay, overtime_rate, tax_rate,
                health_insurance, retirement_contribution
            FROM employees 
            ORDER BY emp_id
        ";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Transform: Process raw HR data into payroll format
     */
    public function transformPayrollData($employeeData) {
        $transformedData = [];
        
        foreach ($employeeData as $employee) {
            // Simulate overtime hours (random between 0-20)
            $overtimeHours = rand(0, 20);
            
            $transformedEmployee = [
                'employee_id' => $employee['emp_id'],
                'full_name' => $this->capitalizeName($employee['first_name']) . ' ' . 
                              $this->capitalizeName($employee['last_name']),
                'department' => $employee['department'],
                'basic_salary' => (float)$employee['basic_pay'],
                'overtime_hours' => $overtimeHours,
                'overtime_pay' => $this->calculateOvertimePay($employee['basic_pay'], $overtimeHours, $employee['overtime_rate']),
                'gross_pay' => $this->calculateGrossPay($employee['basic_pay'], $overtimeHours, $employee['overtime_rate']),
                'tax_deduction' => $this->calculateTax($employee['basic_pay'], $employee['tax_rate']),
                'insurance_deduction' => (float)$employee['health_insurance'],
                'retirement_deduction' => (float)$employee['retirement_contribution'],
                'net_pay' => $this->calculateNetPay($employee, $overtimeHours)
            ];
            
            $transformedData[] = $transformedEmployee;
        }
        
        return $transformedData;
    }
    
    private function capitalizeName($name) {
        return ucwords(strtolower(trim($name)));
    }
    
    private function calculateOvertimePay($basicPay, $overtimeHours, $overtimeRate) {
        $hourlyRate = $basicPay / 160; // 160 working hours per month
        return round($overtimeHours * $hourlyRate * $overtimeRate, 2);
    }
    
    private function calculateGrossPay($basicPay, $overtimeHours, $overtimeRate) {
        $overtimePay = $this->calculateOvertimePay($basicPay, $overtimeHours, $overtimeRate);
        return round($basicPay + $overtimePay, 2);
    }
    
    private function calculateTax($basicPay, $taxRate) {
        return round($basicPay * $taxRate, 2);
    }
    
    private function calculateNetPay($employee, $overtimeHours) {
        $grossPay = $this->calculateGrossPay($employee['basic_pay'], $overtimeHours, $employee['overtime_rate']);
        $tax = $this->calculateTax($employee['basic_pay'], $employee['tax_rate']);
        
        $totalDeductions = $tax + $employee['health_insurance'] + $employee['retirement_contribution'];
        
        return round($grossPay - $totalDeductions, 2);
    }
    
    /**
     * Load: Export transformed data to JSON file
     */
    public function exportToFinanceSystem() {
        try {
            // Extract data from MySQL
            $rawData = $this->extractEmployeeData();
            
            // Transform data
            $transformedData = $this->transformPayrollData($rawData);
            
            // Prepare export structure
            $exportData = [
                'export_date' => date('Y-m-d H:i:s'),
                'export_source' => 'XAMPP HR System',
                'total_employees' => count($transformedData),
                'payroll_period' => date('F Y'),
                'payroll_data' => $transformedData
            ];
            
            // Save to JSON file
            $jsonData = json_encode($exportData, JSON_PRETTY_PRINT);
            
            if (file_put_contents(PAYROLL_EXPORT_FILE, $jsonData) === false) {
                throw new Exception("Failed to write JSON file");
            }
            
            return [
                'status' => 'success',
                'message' => 'ETL process completed successfully',
                'file_path' => PAYROLL_EXPORT_FILE,
                'records_processed' => count($transformedData),
                'total_payroll' => array_sum(array_column($transformedData, 'net_pay'))
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'ETL process failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get payroll data via API (for real-time access)
     */
    public function getPayrollData() {
        if (file_exists(PAYROLL_EXPORT_FILE)) {
            return json_decode(file_get_contents(PAYROLL_EXPORT_FILE), true);
        }
        return null;
    }
}
?>