@echo off
cd C:\laragon\www\mcro-sms-system

:loop
php artisan queue:work --sleep=3 --tries=3 --timeout=90 --max-jobs=100
goto loop