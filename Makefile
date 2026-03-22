run:
	cd docker && sudo docker-compose build --no-cache
	cd docker && sudo docker-compose up -d 
	sudo docker exec php-container composer install
	sudo docker exec php-container ./vendor/bin/phpunit