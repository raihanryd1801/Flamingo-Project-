#!/bin/bash

while true; do
    echo "? Jalankan sync pada $(date '+%Y-%m-%d %H:%M:%S')" >> /var/www/html/monitoring/logs/loop_sync.log
    php /var/www/html/monitoring/routermonit/sync.php >> /var/www/html/monitoring/logs/loop_sync.log 2>&1
    sleep 15
done

