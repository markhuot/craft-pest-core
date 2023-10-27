#!/bin/bash

php craft plugin/install keystone
php ./bin/create-default-fs.php > /dev/null 2>&1
