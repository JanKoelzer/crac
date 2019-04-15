#!/bin/bash
# go to the scripts directory
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$DIR"

# start the calc process:
# $1: propagate the first argument to the php script (use "subdir=pics/tests" for different directories)
# 1>&1: propagate script output (1>) to stdout (&1)
# 2>/dev/null: ignore error output
php -f calc.php "mode=1to10&$1" 1>&1 
#2>/dev/null
