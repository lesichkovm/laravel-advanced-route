# Laravel Advanced Route
An advanced route for Laravel 5.3, 5.4, 5.5, 5.6, 5.8, 6.0, 7.0, 8.0, 9.0, 10.0 to support controllers.

## Background ##
In Laravel 5.3 the advanced functionality Route::controller was removed.
This class fixes this shortcoming.

## Reason ##
The default router is sufficient for small projects. Once the project starts to grow, placing all possible route definitions in the router file starts to become harder to understand and follow. Quite often the router file becomes so messy that the developer is afraid to modify/remove routes (even if these might be unused) in order to not break the application unexpectedly.

The AdvancedRoute::controller gives the control to the controller itself, and makes each controller responsible for its own routing (destiny).

Specifying the controller methods with get/post/any prefixes improves readability, and allows to easily understand what HTTP method is being used to call the functionality just by viewing the method.

Does your router file not fit the screen and you have to scroll to see all routes? Have you split your routes in separate router files, and included these in one router file? Do you not feel comfortable removing routes, as these might be used somewhere? Do you use names to "name" your routes? Then it's time to think outside the box and go advanced.

## How it works ##

The advanced route allows you to easily define a single route to handle every action in a controller class. First, define the route using the AdvancedRoute::controller method. The controller method accepts two arguments. The first is the base URI the controller handles, while the second is the class name of the controller. Next, just add methods to your controller. The method names should begin with the HTTP verb they respond to followed by the title case version of the URI.

```php
<?php

namespace App\Http\Controllers;

class UserController extends Controller {
    /**
     * Responds to any (GET,POST, etc) request to /users
     */
    public function anyIndex() {
        //
    }

    /**
     * Responds to requests to GET /users/show/1
     */
    public function getShow($id) {
        //
    }

    /**
     * Responds to requests to GET /users/admin-profile
     */
    public function getAdminProfile() {
        //
    }

    /**
     * Responds to requests to POST /users/profile
     */
    public function postProfile() {
        //
    }
}
```

## Installation ##

### a) via composer (recommended) ###

```
composer require lesichkovm/laravel-advanced-route
```

### b) manually ###

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

### Full Example: ###

```php
Route::group(['prefix' => '/', 'middleware' => []], function () {
    AdvancedRoute::controller('/auth', 'AuthController');
    AdvancedRoute::controller('/cms', 'CmsController');
    AdvancedRoute::controller('/shop', 'ShopController');
    Route::any('/', 'WebsiteController@anyIndex');
});
```

### Multiple controllers mapping: ###

```php
AdvancedRoute::controllers([
    '/auth' => 'AuthController',
    '/cms' => 'CmsController',
    '/shop' => 'ShopController',
]);
```

### Missing method: ###

If you have a controller with a few predefined routes, you can add the missingMethod() to handle all undefined sub-paths for that controller's path.

```php
class WikiController extends Controller
{
    public function getIndex() { /* show main page or list of content */ }
    public function getCreate() { /* a page to add a new wiki-page */ }
    public function postCreate() { /* add a new wiki-page */ }
    public function missingMethod() { /* do anything elselook up the path in the wiki-database */ }
}
```


## Acknowledgements ##

Laravel Advanced Route is only possible thanks to all the awesome [contributors](https://github.com/lesichkovm/laravel-advanced-route/graphs/contributors)!

## Alternatives ##

Is Laravel too bloated and slow? Yes, tell me about it! Do you want to go down the pure PHP route? Well, here are some notable packages which will allow you to keep the niceties of the Laravel routing:

https://github.com/mrjgreen/phroute

