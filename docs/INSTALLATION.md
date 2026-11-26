
# CivicHub Installation Guide

## 1. Project Requirements

Before installing CivicHub, make sure you have:

- Git
- Docker Desktop or Docker Engine
- Docker Compose
- PHP 8.2 (provided through Docker)

## 2. Clone Repository

Clone the CivicHub repository:

    git clone https://github.com/Nabilla97/CivicHub-2.git

Enter the project directory:

    cd CivicHub-2

## 3. Configure Environment Variables

Create your local environment file:

    cp .env.example .env

Open `.env` and configure the database variables:

- DB_HOST=db
- DB_NAME=db_civichub
- DB_USER=root
- DB_PASS=your_own_secure_password

Replace the example password with your own secure password.

Do not commit `.env` to GitHub.

## 4. Configure Database Connection

Create the local database configuration:

    cp config/database.example.php config/database.php

The application uses environment variables from Docker Compose to connect to MySQL.

## 5. Build and Start Containers

Start the application:

    docker compose up -d --build

Check container status:

    docker compose ps

Wait until the database container reports a healthy status.

## 6. Access CivicHub

Open the following addresses:

- CivicHub: http://localhost:8080
- phpMyAdmin: http://localhost:8081

## 7. Database Information

CivicHub uses MySQL 8.0.

The database schema is initialized from `civichub-schema.sql` when the database volume is first created.

Existing database volumes are preserved when containers are recreated.

## 8. Stop the Application

Stop containers without deleting database data:

    docker compose down

Do not use `docker compose down -v` unless you intentionally want to delete the database volume.

## 9. Troubleshooting

### Database connection failed

- Check the `.env` configuration.
- Make sure the database container is healthy.
- Check the application logs using:

      docker compose logs web db

### Port already in use

Make sure ports 8080, 8081, and 3307 are available.

### Container status

Check running containers with:

    docker compose ps
