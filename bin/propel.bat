@echo off

if "%PHPBIN%" == "" set PHPBIN=php

"%PHPBIN%" "bin\propel" %*
