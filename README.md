# Laravel 8 Mandrill Driver

This package allowed to use Mandrill driver functionality in Laravel 8.

Be careful. Laravel 9 use Swift Mail, which not supported by this Package. 

You can install this package via composer:

```bash
composer require dmytro-o/laravel-8-mandrill-driver
```

Add the Mandrill mailer to your `config\mail.php`:

```php
'mandrill' => [
    'transport' => 'mandrill',
],
```

Add your Mandrill secret key, add the following lines to `config\services.php`

```php
'mandrill' => [
    'secret' => env('MANDRILL_KEY'),
],
```

Set the `MAIL_MAILER` value in your .env to `mandrill` to enable it:

```php
MAIL_MAILER=mandrill
```

And add `MANDRILL_KEY` to your .env file