# Social API
## Summary
The Social API is a RESTFul interface developed and maintained by the Tranvelocity team. It enables users to access and manage various social-related functionalities.


## Getting Started
These instructions will guide you through setting up the project locally for development and testing purposes.

## Prerequisites
Ensure that you have the following dependencies installed on your local machine:

- Git
- Docker
- AWS CLI

```
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
```

- Install Git (with Homebrew):

```bash
$ brew install git
```

- Install Docker (with Homebrew)

```bash
$ brew install docker
```
Or just [download the installer online](https://docs.docker.com/desktop/mac/install/), whatever you prefer.

Install the AWS CLI on MacOS (using pip3, Python needs to be installed):

```
$ brew install awscli
```
For other options and detailed information see the [AWS Docs](https://docs.aws.amazon.com/cli/latest/userguide/getting-started-install.html).


## Installing API Setup
A step by step series of examples that tell you have to get a development env running

```bash
$ git clone git@github.com:tranvelocity/social-api.git
$ cd social-api
$ sh ./scripts/local_setup
```

## Test
- Execute the following command, you will be asked to choose which type of test you want
```bash
$ sh ./scripts/test.sh
```
- There are 4 types of tests are provided in this project
```angular2html
1. PHPStan Test
2. PHPUnit Test
3. PHP artisan Test
4. Parallel Test
4. PHP Coding Standards Check
```

## API Documentation
- The API documentation is implemented using Swagger and can be found in the /docs directory.
- To edit the documentation, access http://localhost:8001/.
- To view the documentation, access http://localhost:8002/.
## Built With

* [Laravel 10](https://laravel.com/) - Modern PHP framework
* [Composer](https://getcomposer.org/) - PHP package manager
* [Laravel Modules Package](https://nwidart.com/laravel-modules/v6/introduction/) - A Laravel package which was created to manage the Laravel app using modules
* [Docker](https://www.docker.com/) - Container Engine
* [Swagger](https://swagger.io/) - API Doc with swagger
* [PHPUnit](https://phpunit.de/) - PHP Testing framework
* [PHPStan](https://phpstan.org/) - A tool to automatically find bugs
* [PHP-CS-Fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer/) - A tool to automatically fix PHP Coding Standards issues


## License

This project is proprietary software of HocTran.




### NOTE
if you cannot access the database via tools such as SeqelPro, DBeaver, etc., please check the following:
- Make sure the database is running
- Run the following command to log into the database container:
```bash
docker exec -it social_db mysql -u root -p

password: root
```
- Run the following command to create a new user and grant privileges:
```sql
ALTER USER 'socialapi'@'%' IDENTIFIED WITH mysql_native_password BY 'socialapi';
FLUSH PRIVILEGES;


- Restart the database container:
```bash
docker restart social_db
```


### Convert api.yaml to HTML
```bash
npx @redocly/cli build-docs docs/api/api.yaml && mv redoc-static.html docs/api/api.html
```
