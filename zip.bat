del TET-activity-moodle.zip
mkdir tomaetest
copy * tomaetest
@REM Xcopy  /S /I /E classes  tomaetest\classes
Xcopy  /S /I /E db  tomaetest\db
Xcopy  /S /I /E lang  tomaetest\lang
@REM Xcopy  /S /I /E misc  tomaetest\misc
rmdir tomaetest\tomaetest /s /q
tar.exe -a -c -f TET-activity-moodle.zip tomaetest
rmdir tomaetest /s /q