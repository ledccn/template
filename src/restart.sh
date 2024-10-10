#!/bin/bash
pwd_dir=$(cd $(dirname $0); pwd)
echo $pwd_dir
cd $(dirname $0)
php83 start.php restart -d
#php83 gateway_worker.php restart -d
