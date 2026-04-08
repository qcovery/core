#!/bin/bash

export VUFIND_HOME="/var/www/html"
export VUFIND_LOCAL_DIR="/var/www/beluga-config/local/local_hcu/local_eWW"
export VUFIND_LOCAL_MODULES="CleanUpUserData"

if [ -n "$1" ]; then
    php $VUFIND_HOME/public/index.php util/cleanup_user_data --hours $1
else
    php $VUFIND_HOME/public/index.php util/cleanup_user_data
fi