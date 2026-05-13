@echo off
:: ============================================================
:: setup_expiry_cron.bat
:: Run this ONCE as Administrator to register the daily
:: expiry alert task in Windows Task Scheduler
:: ============================================================

:: ── Find PHP executable ──────────────────────────────────────
:: Adjust this path if your PHP version differs
set PHP_EXE=C:\wamp64\bin\php\php8.3.14\php.exe

:: ── Script and log paths ────────────────────────────────────
set SCRIPT=C:\wamp64\www\pos_expiry_alert_cron.php
set LOG_DIR=C:\wamp64\www\logs

:: ── Create log directory if missing ─────────────────────────
if not exist "%LOG_DIR%" mkdir "%LOG_DIR%"

:: ── Task settings ────────────────────────────────────────────
set TASK_NAME=NCC_POS_Expiry_Alert
set RUN_TIME=08:00

echo.
echo Creating scheduled task: %TASK_NAME%
echo PHP:    %PHP_EXE%
echo Script: %SCRIPT%
echo Time:   %RUN_TIME% daily
echo.

:: ── Register the task ────────────────────────────────────────
schtasks /create /tn "%TASK_NAME%" ^
  /tr "\"%PHP_EXE%\" \"%SCRIPT%\" >> \"%LOG_DIR%\expiry_cron.log\" 2>&1" ^
  /sc daily ^
  /st %RUN_TIME% ^
  /ru SYSTEM ^
  /f

if %errorlevel% == 0 (
    echo.
    echo [OK] Task created successfully.
    echo      Runs every day at %RUN_TIME%.
    echo      Log file: %LOG_DIR%\expiry_cron.log
) else (
    echo.
    echo [ERROR] Task creation failed.
    echo         Make sure you are running this as Administrator.
)

echo.
echo To verify: open Task Scheduler and look for "%TASK_NAME%"
echo To test now: schtasks /run /tn "%TASK_NAME%"
echo To remove:   schtasks /delete /tn "%TASK_NAME%" /f
echo.
pause
