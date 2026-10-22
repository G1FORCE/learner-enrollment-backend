# Learner Enrollment Backend

## Project Description
A REST API built with Laravel and PHP for managing learner enrollment.
It supports learner registration, JWT authentication, enrollment
applications, and administrator management of learner records.

## Technologies
- PHP and Laravel
- SQL database supported by Laravel
- JWT Bearer token authentication
- PHPUnit / Laravel automated tests

## Requirements
- PHP and required Laravel extensions
- Composer
- A database configured in the environment file

## Installation
1. Extract the project ZIP.
2. Open a terminal in the project folder.
3. Install dependencies:

   composer install

4. Create your environment file:

   cp .env.example .env

5. Generate the Laravel application key:

   php artisan key:generate

6. Configure your database settings in `.env`.
7. Set a secure JWT secret of at least 32 characters in `.env` as `JWT_SECRET`.
8. Run database migrations:

   php artisan migrate

9. Start the local server:

   php artisan serve

10. Open `http://127.0.0.1:8000/api/test` to check the API.

## Main API Features
- Register and log in
- JWT-protected logout
- Submit an enrollment application
- View the authenticated learner's own application
- Administrator learner listing, search, and pagination
- Update enrollment details and status
- Delete learner records
- Role-based access control

## Automated Tests
Run:

   php artisan test

The verified test suite currently covers the API health check,
registration, unauthenticated access, and learner role restrictions.

## API Documentation
See `API_DOCUMENTATION.md` for endpoints, access requirements,
example requests, and response codes.

## Security
Never distribute `.env` or real authentication tokens.
Use a strong private JWT secret and HTTPS in production.
