hello people of the future:

Thanks to vers for some of the HTML / CSS coding

Setup info:
upload the latest SQL file to your sql db of choice. 
Full backups aren't supposed to be included.

upload folders: cron, css, discord, img, Login, Other, Public. Do not upload backup folders.
upload index.html and image.
setup config at include->config.php and Login->private_folder_authentication->cheat.php.

Cron setup:
go to ur cron section (crontab / cron / cronjob)
create a task to run every 15 min: /usr/local/bin/php72 /home/versaceh/public_html/cron/check_order.php >/dev/null
also /usr/local/bin/ea-php72 /home/versaceh/public_html/cron/check_order.php >/dev/null
also edit the config file in the cron folder

Done.

Regards, 
Null