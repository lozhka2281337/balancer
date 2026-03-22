# для  linux
run_l:
	sudo docker-compose -f docker/docker-compose.yml build --no-cache
	sudo docker-compose -f docker/docker-compose.yml up -d
	sudo docker exec php-container composer install
	sudo docker exec php-container ./vendor/bin/phpunit

#  для windows
run_w:
	docker-compose -f docker/docker-compose.yml build --no-cache
	docker-compose -f docker/docker-compose.yml up -d
	docker exec php-container composer install
	docker exec php-container php vendor/bin/phpunit
