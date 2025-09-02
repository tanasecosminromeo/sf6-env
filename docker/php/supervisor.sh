#!/bin/bash

# This script provides shortcuts for managing supervisor processes
# Usage: ./supervisor-manage.sh [reload-php|reload-messenger|status]

case "$1" in
  reload-php)
    echo "Reloading PHP-FPM..."
    supervisorctl restart php-fpm
    ;;
  reload-messenger)
    echo "Reloading Messenger consumers..."
    supervisorctl restart messenger-consume:*
    ;;
  reload-all)
    echo "Reloading all processes..."
    supervisorctl restart all
    ;;
  status)
    echo "Checking status of all processes..."
    supervisorctl status
    ;;
  *)
    echo "Usage: $0 [reload-php|reload-messenger|reload-all|status]"
    exit 1
    ;;
esac

exit 0
