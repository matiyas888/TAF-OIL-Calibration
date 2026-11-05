@echo off
title TAF Oil Petroleum Web Server
color 0B
echo ====================================================================
echo        TAF OIL ETHIOPIA - WEB INTERFACE (PORT 8000)
echo ====================================================================
echo.
echo Opening browser and starting web application...
start http://127.0.0.1:8000
echo.
php artisan serve --port=8000
