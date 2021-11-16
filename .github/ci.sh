#!/usr/bin/env bash

dir=$(dirname $0)

set -ex

cp ${dir}/../.gitlab/settings.local.php ${dir}/../web/sites/default/settings.local.php

sed -ri -e "s!/var/www/html/web!$GITHUB_WORKSPACE/web!g" /etc/apache2/sites-available/*.conf
sed -ri -e "s!/var/www/html/web!$GITHUB_WORKSPACE/web!g" /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

service apache2 start
