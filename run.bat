@echo off
setlocal
cd /d "%~dp0"
start "BookFlow Server" php -S 127.0.0.1:8080 router.php
start "BookFlow Login" http://127.0.0.1:8080/login
