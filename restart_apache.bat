@echo off
echo ========================================
echo   Restarting Apache (XAMPP)
echo ========================================
echo.
echo Stopping Apache...
C:\xampp\apache\bin\httpd.exe -k stop
timeout /t 3 /nobreak > nul
echo.
echo Starting Apache...
C:\xampp\apache\bin\httpd.exe -k start
timeout /t 2 /nobreak > nul
echo.
echo ========================================
echo   Apache Restarted Successfully!
echo ========================================
echo.
echo Now press Ctrl+F5 in your browser
echo and try again: /admin/mentors.php
echo.
pause
