#/bin/bash

if [ ! -z "$HOST_UID" ]; then
    echo "Changing app user id to $HOST_UID"
    usermod -u $HOST_UID app
fi

if [ ! -z "$HOST_GID" ]; then
    echo "Changing app group id to $HOST_GID"
    groupmod -g $HOST_GID app
fi

sed -i -e "s/user = www-data/user = app/g" /usr/local/etc/php-fpm.d/www.conf
sed -i -e "s/group = www-data/group = app/g" /usr/local/etc/php-fpm.d/www.conf

sed -i -e "s/pm.max_children = 5/pm.max_children = ${FPM_MAX_CHILDREN:-5}/g" /usr/local/etc/php-fpm.d/www.conf
sed -i -e "s/pm.start_servers = 2/pm.start_servers = ${FPM_START_SERVERS:-2}/g" /usr/local/etc/php-fpm.d/www.conf
sed -i -e "s/pm.min_spare_servers = 1/pm.min_spare_servers = ${FPM_MIN_SPARE:-1}/g" /usr/local/etc/php-fpm.d/www.conf
sed -i -e "s/pm.max_spare_servers = 3/pm.max_spare_servers = ${FPM_MAX_SPARE:-3}/g" /usr/local/etc/php-fpm.d/www.conf
sed -i -e "s/;pm.max_requests = 500/pm.max_requests = ${FPM_MAX_REQUESTS:-1000}/g" /usr/local/etc/php-fpm.d/www.conf

php-fpm
