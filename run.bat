@echo off
setlocal
cd /d "%~dp0"
php seed.php
start "BookFlow Server" php -S 127.0.0.1:8000 router.php
start "BookFlow Login" http://127.0.0.1:8000/login
