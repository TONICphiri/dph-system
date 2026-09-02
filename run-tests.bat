@echo off
REM Digital Health Passport System - Test Runner Script
REM Always clear cache before running tests

echo Clearing application cache...
C:\xampp\php\php.exe artisan config:clear
C:\xampp\php\php.exe artisan cache:clear

echo Running full test suite...
C:\xampp\php\php.exe artisan test tests/Feature/AdminManagementTest.php tests/Feature/Feature/WorkflowConsistencyTest.php tests/Feature/Feature/ProductionDeploymentTest.php tests/Feature/Feature/PilotValidationTest.php --no-coverage

echo Test suite complete!
pause