# Balancer

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue)](https://php.net)

Бэкенд приложение на Symfony. 


## Возможности приложения
- принмает запрос на размещение/удаление процесса/рабочей-машины
- распределяет процессы между рабочими-машинами, обеспечивая равномерную нагрузку, выполняет ребалансировку, в случае добавления/удаления
машин/процессов
- процесс размещается полностью на одной машине
- показывает текущее состояние сервиса

* [описание алгоритмов](ALGORITHMS.md)

## Требования
- PHP >= 8.4
- Composer


## Установка

```bash
git clone https://github.com/lozhka2281337/balancer.git
cd balancer
composer update
cp .env.example .env
```


## Конфигурация

Основные переменные окружения (`.env`):
необходимо создать .env файл:
1) заполнить DB_HOST, DATABASE_URL для вашей бд.
2) заполнить DATABASE_URL для тестовой бд в .env.test (опционально)

```env
APP_ENV=dev
APP_SHARE_DIR=var/share

DB_HOST={host}
DATABASE_URL="{database}://{username}:{password}@127.0.0.1:5432/{database_name}?charset=utf8"
```


## Быстрый старт 

```bash
php -S localhost:8000 -t public
```


## Запуск через Docker

```bash
cd docker
sudo docker-compose build --no-cache
sudo docker-compose up -d
```


### тестирование

Для тестировония некоторых модулей требуется тестовая бд (см. конфигурацию)

Запуск всех тестов
```bash 
./vendor/bin/phpunit 
```

через docker
```bash 
sudo docker exec -it php-container bash
./vendor/bin/phpunit 
```


## Использование

* Для запросов рекомендуется использовать postman

### Эндпоинты

```bash
GET http://localhost:8000/status

POST http://localhost:8000/process
POST http://localhost:8000/machine

DELETE http://localhost:8000/process
DELETE http://localhost:8000/machine

```

Примеры запросов через curl:

```bash
curl -X GET http://localhost:8000/status
```

Пример ответа:

```json
{
  "состояние сервиса": [
    {
      "id машины": 4,
      "неиспользовано cpu": 23,
      "неиспользовано memory": 67,
      "процессы": [
        {
          "id": 22,
          "cpu": 10,
          "memory": 1
        }
      ]
    },
  ]
}
```


```bash
curl -X POST -H "Content-Type: application/json" -d "{'memory': 12, 'cpu': 23}" http://localhost:8000/process
```

Пример ответа:

```json
{
  "id процесса": 35,
  "расположен на машине с id": 2
}
```


```bash
curl -X DELETE -H "Content-Type: application/json" -d "{'id': 22}" http://localhost:8000/machine
```

Пример ответа:

```json
{
  "данные": "машина успешно удалена"
}
```


## Лицензия
MIT