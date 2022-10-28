#!/bin/bash
sudo chown -R mysql /var/lib/mysql
sudo chgrp -R mysql /var/lib/mysql
sudo chmod 755 /var/lib/mysql
sudo chown -R mysql /var/log/mysql
sudo chgrp -R mysql /var/log/mysql
sudo chmod 755 /var/log/mysql

supervisord --nodaemon --configuration /etc/supervisor/conf.d/supervisord.conf