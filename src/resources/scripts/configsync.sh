#!/bin/bash

#
# Run this from cron with the first parameter as the network name and the second parameter as the password
#


wget -q "http://localhost/mesh/sync-config.php?action=dosync&network=$1&usepass=$2" -O /tmp/syncconf.tmp &> /dev/null


