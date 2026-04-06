Set WshShell = CreateObject("WScript.Shell")
WshShell.Run "C:\laragon\www\mcro-sms-system\queue-worker.bat", 0, False
Set WshShell = Nothing