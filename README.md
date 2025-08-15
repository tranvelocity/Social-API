
# Social API

## Overview
**Social API** is a modular, RESTful backend platform developed by the Tranvelocity. Its primary goal is to provide a scalable, maintainable, and extensible API for managing social features such as users, posts, comments, likes, media, roles, sessions, and more. The project is built on Laravel 10, leverages Docker for local development, and uses a modular architecture for clean separation of concerns.

### Project Goals
- Provide a robust, well-documented API for social applications
- Support modular development for easy feature extension and maintenance
- Ensure high code quality with automated testing and static analysis
- Enable rapid local development and deployment using Docker

## Project Structure

```
├── composer.json           # Project dependencies
├── docker-compose.yml      # Docker services configuration
├── Dockerfile              # PHP-FPM build for Laravel
├── README.md               # Project documentation
├── docs/                   # API documentation (OpenAPI/Swagger)
│   └── api/api.yaml        # OpenAPI spec
├── nginx/                  # Nginx configuration
│   └── conf/app.conf       # Main Nginx site config
├── scripts/                # Helper scripts for setup, testing, etc.
├── src/                    # Main Laravel application
│   ├── app/                # Core Laravel app code
│   ├── Modules/            # Feature modules (Admin, User, Member, etc.)
│   ├── config/             # Laravel and module configs
│   ├── database/           # Migrations and seeders
│   ├── public/             # Web entrypoint (index.php)
│   └── ...
└── ...
```

### Example Module Structure (e.g., `src/Modules/User`)
```
User/
├── app/
│   ├── Models/
│   ├── Repositories/
│   ├── Providers/
│   └── ...
├── config/
├── database/
│   ├── migrations/
│   ├── factories/
│   └── seeders/
├── lang/
├── routes/
├── composer.json
├── module.json
└── ...
```



## Getting Started
Follow these steps to set up the project locally for development and testing.


### Prerequisites
Install the following dependencies:
- [Git](https://git-scm.com/)
- [Docker](https://www.docker.com/)
- [AWS CLI](https://aws.amazon.com/cli/)

#### Quick Install (macOS)
```bash
# Homebrew (if not installed)
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

# Git
brew install git

# Docker
brew install docker
# Or download Docker Desktop: https://docs.docker.com/desktop/mac/install/

# AWS CLI
brew install awscli
```
For other platforms, see the [AWS CLI docs](https://docs.aws.amazon.com/cli/latest/userguide/getting-started-install.html).



## Installation & Local Setup

Clone the repository and run the setup script:
```bash
git clone git@github.com:tranvelocity/social-api.git
cd social-api
sh ./scripts/local_setup.sh
```
This will build Docker containers, install dependencies, and prepare the environment.


## Running Tests
To run tests, use the provided script:
```bash
sh ./scripts/test.sh
```
You will be prompted to select the type of test:
1. PHPStan (static analysis)
2. PHPUnit (unit/integration tests)
3. PHP artisan test
4. Parallel test
5. PHP Coding Standards Check


## API Documentation
- The API is documented using OpenAPI (Swagger) in `docs/api/api.yaml`.
- **Edit**: http://localhost:8001/ (Swagger Editor)
- **View**: http://localhost:8002/ (Swagger UI)
- **Convert to HTML**: Run the following to generate a static HTML doc:
	```bash
	npx @redocly/cli build-docs docs/api/api.yaml && mv redoc-static.html docs/api/api.html
	```

## Key Technologies
- [Laravel 10](https://laravel.com/) - PHP framework
- [Composer](https://getcomposer.org/) - Dependency management
- [nwidart/laravel-modules](https://nwidart.com/laravel-modules/v6/introduction/) - Modular architecture
- [Docker](https://www.docker.com/) - Containerization
- [Swagger/OpenAPI](https://swagger.io/) - API documentation
- [PHPUnit](https://phpunit.de/) - Testing
- [PHPStan](https://phpstan.org/) - Static analysis
- [PHP-CS-Fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer/) - Coding standards



## License
This project is proprietary software of HocTran.





---
### Troubleshooting

**Database Access**
- Ensure the database container is running.
- To access MySQL from your host:
	```bash
	docker exec -it social_db mysql -u root -p
	# password: root
	```
- If you have connection issues, run:
	```sql
	ALTER USER 'socialapi'@'%' IDENTIFIED WITH mysql_native_password BY 'socialapi';
	FLUSH PRIVILEGES;
	```
- Restart the database container:
	```bash
	docker restart social_db
	```

**Common Endpoints**
- API base URL (local): `http://localhost/`
- Swagger Editor: `http://localhost:8001/`
- Swagger UI: `http://localhost:8002/`

---

## Contributing
Please open issues or pull requests for improvements, bug fixes, or new features. For major changes, discuss them with the Tranvelocity first.
