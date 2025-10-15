using System;
using System.Collections.Generic;
using System.IO;
using System.Text.Json;
using System.Timers;

namespace FinanceSystem
{
    public class PayrollRecord
    {
        public string EmployeeId { get; set; }
        public string FullName { get; set; }
        public string Department { get; set; }
        public decimal BasicSalary { get; set; }
        public decimal OvertimePay { get; set; }
        public int OvertimeHours { get; set; }
        public decimal GrossPay { get; set; }
        public decimal TaxDeduction { get; set; }
        public decimal InsuranceDeduction { get; set; }
        public decimal RetirementDeduction { get; set; }
        public decimal NetPay { get; set; }
    }

    public class PayrollExport
    {
        public string ExportDate { get; set; }
        public string ExportSource { get; set; }
        public string PayrollPeriod { get; set; }
        public int TotalEmployees { get; set; }
        public List<PayrollRecord> PayrollData { get; set; }
    }

    public class FinancePayrollLoader
    {
        private string dataFilePath;
        private FileSystemWatcher fileWatcher;

        public FinancePayrollLoader(string filePath)
        {
            dataFilePath = filePath;
            SetupFileMonitoring();
        }

        private void SetupFileMonitoring()
        {
            string directory = Path.GetDirectoryName(dataFilePath);
            string fileName = Path.GetFileName(dataFilePath);

            fileWatcher = new FileSystemWatcher
            {
                Path = directory,
                Filter = fileName,
                NotifyFilter = NotifyFilters.LastWrite | NotifyFilters.Size
            };

            fileWatcher.Changed += OnPayrollFileChanged;
            fileWatcher.EnableRaisingEvents = true;

            Console.WriteLine($"Monitoring file: {dataFilePath}");
        }

        private void OnPayrollFileChanged(object source, FileSystemEventArgs e)
        {
            Console.WriteLine($"\n*** Payroll file updated: {e.FullPath} ***");
            Console.WriteLine("New data detected! Reloading payroll information...\n");
            
            // Wait a moment for file write to complete
            System.Threading.Thread.Sleep(1000);
            
            var payrollData = LoadPayrollData();
            if (payrollData != null)
            {
                DisplayPayrollReport(payrollData);
            }
        }

        public PayrollExport LoadPayrollData()
        {
            try
            {
                if (!File.Exists(dataFilePath))
                {
                    Console.WriteLine("Payroll data file not found. Waiting for HR system export...");
                    return null;
                }

                string jsonData = File.ReadAllText(dataFilePath);
                PayrollExport payrollExport = JsonSerializer.Deserialize<PayrollExport>(jsonData);

                Console.WriteLine($"✓ Payroll data loaded successfully");
                Console.WriteLine($"  Export Date: {payrollExport.ExportDate}");
                Console.WriteLine($"  Employees: {payrollExport.TotalEmployees}");
                Console.WriteLine($"  Period: {payrollExport.PayrollPeriod}");

                return payrollExport;
            }
            catch (Exception ex)
            {
                Console.WriteLine($"✗ Error loading payroll data: {ex.Message}");
                return null;
            }
        }

        public void DisplayPayrollReport(PayrollExport payrollData)
        {
            if (payrollData == null)
            {
                Console.WriteLine("No payroll data to display.");
                return;
            }

            Console.WriteLine("\n" + new string('=', 80));
            Console.WriteLine("FINANCE DEPARTMENT - PAYROLL REPORT");
            Console.WriteLine(new string('=', 80));
            Console.WriteLine($"Export Date: {payrollData.ExportDate}");
            Console.WriteLine($"Payroll Period: {payrollData.PayrollPeriod}");
            Console.WriteLine($"Total Employees: {payrollData.TotalEmployees}");
            Console.WriteLine(new string('-', 80));

            decimal totalGrossPay = 0;
            decimal totalNetPay = 0;
            decimal totalDeductions = 0;
            decimal totalOvertime = 0;

            foreach (var record in payrollData.PayrollData)
            {
                Console.WriteLine($"ID: {record.EmployeeId}");
                Console.WriteLine($"Name: {record.FullName,-20} Department: {record.Department,-15}");
                Console.WriteLine($"Basic Salary: ${record.BasicSalary,10:F2}");
                Console.WriteLine($"Overtime: {record.OvertimeHours,2} hours = ${record.OvertimePay,8:F2}");
                Console.WriteLine($"Gross Pay: ${record.GrossPay,12:F2}");
                Console.WriteLine($"Deductions:");
                Console.WriteLine($"  Tax: ${record.TaxDeduction,10:F2}");
                Console.WriteLine($"  Insurance: ${record.InsuranceDeduction,6:F2}");
                Console.WriteLine($"  Retirement: ${record.RetirementDeduction,5:F2}");
                Console.WriteLine($"NET PAY: ${record.NetPay,14:F2}");
                Console.WriteLine(new string('-', 40));

                totalGrossPay += record.GrossPay;
                totalNetPay += record.NetPay;
                totalDeductions += (record.TaxDeduction + record.InsuranceDeduction + record.RetirementDeduction);
                totalOvertime += record.OvertimePay;
            }

            Console.WriteLine(new string('=', 80));
            Console.WriteLine("SUMMARY");
            Console.WriteLine(new string('-', 80));
            Console.WriteLine($"Total Gross Pay:    ${totalGrossPay,15:F2}");
            Console.WriteLine($"Total Overtime:     ${totalOvertime,15:F2}");
            Console.WriteLine($"Total Deductions:   ${totalDeductions,15:F2}");
            Console.WriteLine($"TOTAL NET PAYROLL:  ${totalNetPay,15:F2}");
            Console.WriteLine(new string('=', 80));
        }

        public void GenerateAccountingExport(PayrollExport payrollData, string exportPath)
        {
            if (payrollData == null) return;

            try
            {
                var accountingData = new
                {
                    ExportDate = DateTime.Now.ToString("yyyy-MM-dd HH:mm:ss"),
                    Period = payrollData.PayrollPeriod,
                    TotalEmployees = payrollData.TotalEmployees,
                    Employees = payrollData.PayrollData
                };

                string jsonExport = JsonSerializer.Serialize(accountingData, new JsonSerializerOptions { WriteIndented = true });
                File.WriteAllText(exportPath, jsonExport);

                Console.WriteLine($"✓ Accounting export generated: {exportPath}");
            }
            catch (Exception ex)
            {
                Console.WriteLine($"✗ Error generating accounting export: {ex.Message}");
            }
        }
    }

    class Program
    {
        static void Main(string[] args)
        {
            Console.Title = "Finance System - Payroll Loader";
            Console.WriteLine("=== FINANCE SYSTEM - XAMPP INTEGRATION ===");
            Console.WriteLine("Monitoring for HR payroll data updates...\n");

            // Note: Update this path to match your XAMPP installation
            string payrollFile = @"C:\xampp\htdocs\hr-finance-integration\shared-data\payroll_export.json";
            
            FinancePayrollLoader payrollLoader = new FinancePayrollLoader(payrollFile);

            // Initial load
            PayrollExport payrollData = payrollLoader.LoadPayrollData();
            if (payrollData != null)
            {
                payrollLoader.DisplayPayrollReport(payrollData);
                
                // Generate accounting export
                string accountingExport = @"C:\xampp\htdocs\hr-finance-integration\shared-data\accounting_export.json";
                payrollLoader.GenerateAccountingExport(payrollData, accountingExport);
            }

            Console.WriteLine("\nFinance system is running and monitoring for updates...");
            Console.WriteLine("Press 'R' to reload manually, 'Q' to quit.");

            while (true)
            {
                var key = Console.ReadKey(true);
                if (key.Key == ConsoleKey.Q)
                    break;
                if (key.Key == ConsoleKey.R)
                {
                    Console.WriteLine("\nManual reload triggered...");
                    payrollData = payrollLoader.LoadPayrollData();
                    if (payrollData != null)
                    {
                        payrollLoader.DisplayPayrollReport(payrollData);
                    }
                }
            }
        }
    }
}