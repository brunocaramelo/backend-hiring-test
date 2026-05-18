# SIMPLE IMPORTER

Quality Certification Seals

[![Codacy Badge](https://app.codacy.com/project/badge/Grade/6dc1588a6bac417da8ff27e3768a6e2e)](https://app.codacy.com/gh/brunocaramelo/backend-hiring-test/dashboard?utm_source=gh&utm_medium=referral&utm_content=&utm_campaign=Badge_grade)

[![Codacy Badge](https://app.codacy.com/project/badge/Coverage/6dc1588a6bac417da8ff27e3768a6e2e)](https://app.codacy.com/gh/brunocaramelo/backend-hiring-test/dashboard?utm_source=gh&utm_medium=referral&utm_content=&utm_campaign=Badge_coverage)

## Technical Specifications

This application has the following specifications: 

| Tool | Version |
| --- | --- |
| Docker | 28.1.1 |
| Docker Compose | 2.32.4 |
| PHP | 8.5.6 |

The application is separated into the following containers

| Service | Image | Motivation
| --- | --- | --- |
| app | php-cli | CLI |
| composer | composer:latest | Dependency Installer |

## Requirements
    - Docker
    - Docker Daemon (Service)
    - Docker Compose

## Installation procedures
    Procedures for installing the application for local use

1- Download repository 
    - git clone https://github.com/brunocaramelo/backend-hiring-test.git
       
2 - Enter the application's home directory and run the following commands:
    
    1 - docker compose build;

    2 - docker-compose run --rm composer install
    
    3 - docker-compose run --rm app php import.php
    
    4 - docker-compose run --rm app vendor/bin/phpunit
    
   
## Post Run

Report file created on:

- output/import-results.json

## Tecnical Details

    - PHP 8.5

    - SOLID

    - Unit Tests and Feature Tests

    - Docker and docker-compose

