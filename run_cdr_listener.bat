@echo off
echo Starting CDR Real-time Listener...
echo Listening on port 8087 for UCM CDR data...
echo Press Ctrl+C to stop
echo.
C:\wamp64\bin\php\php8.3.14\php.exe C:\wamp64\www\cdr_realtime_listener.php
pause