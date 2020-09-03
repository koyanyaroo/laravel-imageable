# Laravel Imageable

This package allows you to easily handle image upload in laravel. 

## Installation

You can install the package via composer:

```bash
composer require koyanyaroo/laravel-imageable
```

## Introduction

Introduction here

### Usage
Define your model
```php
    use Koyanyaroo\Imageable;

    ...

   /**
        * Define an array of filter that allowed to use for this model
        * `key` as class name and `value` as field name(s)
        *
        * @var array
        */
       protected $imageableField = [
           'image',
           'image_hero' => [
               'thumb' => [
                   380,
                   253,
               ],
           ],
       ];
```
## Credits

- [koyan](https://github.com/koyanyaroo)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.