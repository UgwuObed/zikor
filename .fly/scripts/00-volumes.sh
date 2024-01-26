if [ ! -d "/var/www/html/storage/app" ]; then
	if [ ! -d "/var/www/html/storage_" ]; then
		echo "could not find storage_ dir to copy to volumn"
		exit 1

	else 
		cp -r /var/www/html/storage_/. /var/www/html/storage
		rm -rf /var/www/html/storage_

		fi

		fi  