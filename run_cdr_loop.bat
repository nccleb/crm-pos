@echo off
echo CDR Update System - Running every 2 minutes
echo Press CTRL+C to stop
echo.
pause

:loop
echo.
echo [%time%] Updating call statuses...
C:\wamp64\bin\php\php.exe C:\wamp64\www\cdr_update_optimized.php
echo.
timeout /t 120 /nobreak > nul
goto loop