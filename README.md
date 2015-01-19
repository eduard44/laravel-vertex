# laravel-vertex

This is a simple Laravel 5 command for serving the current application on a HHVM server running inside a Docker container.

It's main purpose is to function as a replacement of `php artisan serve`, which is no longer available in Laravel 5.

The default docker image used is `eduard44/vertex` (hence the package name). It is possible to customize the image used and run arguments by extending the `ServeCommand` class and overriding the `getImageName` and `getRunArguments` methods.

## Requirements

- Docker (Boot2docker if on OSX)
- Composer and PHP

This utility expects `docker` to be a command that can be launched without sudo permissions or any special configuration

## Usage

Add the package to your composer.json:

```json
"require": {
    "chromabits/laravel-vertex": "0.1.x"
}
```

and then add the ServeCommand to your `App\Console\Kernel` class:

```php
protected $commands = [
	//...
	'Chromabits\Vertex\Console\Commands\ServeCommand'
];
```

Starting the server (just like it was on Laravel 4):

```
$ php artisan serve
```
