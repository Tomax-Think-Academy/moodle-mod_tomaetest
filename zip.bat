del TET-activity-moodle.zip
mkdir tomaetest
copy * tomaetest
Xcopy  /S /I /E db  tomaetest\db
Xcopy  /S /I /E lang  tomaetest\lang
Xcopy  /S /I /E classes  tomaetest\classes
Xcopy  /S /I /E misc  tomaetest\misc
Xcopy  /S /I /E pix  tomaetest\pix
rmdir tomaetest\tomaetest /s /q
tar.exe -a -c -f TET-activity-moodle.zip tomaetest
rmdir tomaetest /s /q