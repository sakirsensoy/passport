# passport
Role and permission managament for Laravel

Installation
------------

Install using composer:

    composer require sako/passport:2.0.*

Add the service provider in `app/config/app.php`:

    'Sako\Passport\PassportServiceProvider',

Register the Passport alias:

    'Passport' => 'Sako\Passport\Facades\Passport',

Publish the included configuration file:

    php artisan config:publish sako/passport

Publish the included migration files:

    php artisan migrate:publish sako/passport

Run artisan migrate command for generating tables:

    php artisan migrate

And generate permissions from artisan Passport command:

    php artisan passport:generate-permissions
