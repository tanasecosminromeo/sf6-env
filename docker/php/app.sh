#!/bin/bash

case "$1" in
    'clean')
        rm -rf /code/var/*
        chown -R app:app /code
        ;;
    'composer')
        shift
        sudo -u app composer $@
        ;;
    *)
        sudo -u app php bin/console $@
        ;;
esac