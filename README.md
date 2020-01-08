# Printify test task 

## Installation instruction

- Insert line into local hosts file

```bash
127.0.0.1       symfony.local
```

- Clone repository

- Run in rest-api-test directory

```bash
docker-compose up -d --build
```

- Run after Docker build

```bash
docker exec -it rest-api-test_php_1 bash
```

-  Run in docker container 

```bash
composer install
```

- (optional - to perform PHPunit tests) Run in current docker container
 
 ```bash
./vendor/bin/phpunit tests/
```

- For manual testing and documentation use
 ```bash
http://symfony.local/api/doc
```
