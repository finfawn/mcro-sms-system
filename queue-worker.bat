@echo off
cd C:\laragon\www\mcro-sms-system

:loop
C:\laragon\bin\php\php-8.3.29-nts-Win32-vs16-x64\php.exe artisan queue:work --sleep=3 --tries=3 --timeout=90 --max-jobs=100 >> storage\logs\queue-worker-task.log 2>&1
timeout /t 3 /nobreak >nul
goto loop
