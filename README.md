# Laravel Advanced Route
An advanced route for Laravel 5.3, 5.4 and 5.5 to support controllers

## Background ##
In Laravel 5.3 the advanced functionality Route::controller was removed.
This class fixes this shortcoming.

## Installation ##

Add the following to your composer file:

```json
   "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/lesichkovm/laravel-advanced-route.git"
        }
    ],
    "require": {
        "lesichkovm/laravel-advanced-route": "dev-master"
    },
```

## Usage ##

Add the following line to where you want your controller to be mapped:

```php
AdvancedRoute::controller('/{YOUR PATH}', '{YOUR CONTROLLER FULL NAME}');
```

Full Example:

```php
Route::group(['prefix' => '/', 'middleware' => []], function () {
    AdvancedRoute::controller('/auth', 'AuthController');
    AdvancedRoute::controller('/cms', 'CmsController');
    AdvancedRoute::controller('/shop', 'ShopController');
    Route::any('/', 'WebsiteController@anyIndex');
});
```

Multiple controllers mapping:
```php
AdvancedRoute::controllers([
    '/auth' => 'AuthController',
    '/cms' => 'CmsController',
    '/shop' => 'ShopController',
]);
```

## Acknowledgements ##

Laravel Advanced Route is only possible thanks to all the awesome [contributors](https://github.com/lesichkovm/laravel-advanced-route/graphs/contributors)!

