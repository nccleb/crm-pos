@echo off
title UCM6202 CDR Listener - DO NOT CLOSE
color 0A
echo ========================================
echo UCM6202 CDR LISTENER SERVICE
echo ========================================
echo.
echo Status: RUNNING
echo Port: 8087
echo.
echo This window must stay open!
echo Minimize it, but DO NOT CLOSE!
echo.
echo ========================================
echo.

:loop
C:\wamp64\bin\php\php8.3.14\php.exe C:\wamp64\www\cdr_listener_HYBRID.php

echo.
echo [ERROR] Listener stopped unexpectedly!
echo Restarting in 5 seconds...
echo.
timeout /t 5 /nobreak

goto loop