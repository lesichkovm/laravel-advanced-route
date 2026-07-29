<?php

namespace Tests;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Route;
use Tests\Stubs\UserController;
use Tests\Stubs\WikiController;
use Tests\Stubs\ParameterController;

class AdvancedRouteTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }

    private function getRegisteredRoutes()
    {
        return collect(Route::getRoutes()->getRoutes())->keyBy(function ($route) {
            return $route->methods()[0] . ' ' . $route->uri();
        });
    }

    public function test_controller_registers_get_routes()
    {
        \AdvancedRoute::controller('/users', UserController::class);

        $routes = $this->getRegisteredRoutes();

        $this->assertTrue($routes->has('GET users/show/{id}'), 'GET users/show/{id} should be registered');
        $this->assertTrue($routes->has('GET users/admin-profile'), 'GET users/admin-profile should be registered');
    }

    public function test_controller_registers_post_routes()
    {
        \AdvancedRoute::controller('/users', UserController::class);

        $routes = $this->getRegisteredRoutes();

        $this->assertTrue($routes->has('POST users/profile'), 'POST users/profile should be registered');
    }

    public function test_controller_registers_put_routes()
    {
        \AdvancedRoute::controller('/users', UserController::class);

        $routes = $this->getRegisteredRoutes();

        $this->assertTrue($routes->has('PUT users/update/{id}'), 'PUT users/update/{id} should be registered');
    }

    public function test_controller_registers_delete_routes()
    {
        \AdvancedRoute::controller('/users', UserController::class);

        $routes = $this->getRegisteredRoutes();

        $this->assertTrue($routes->has('DELETE users/destroy/{id}'), 'DELETE users/destroy/{id} should be registered');
    }

    public function test_controller_registers_any_index_route()
    {
        \AdvancedRoute::controller('/users', UserController::class);

        $routes = $this->getRegisteredRoutes();

        $anyIndexRoute = $routes->filter(function ($route, $key) {
            return str_starts_with($key, 'ANY') && str_contains($route->uri(), 'users');
        });

        $this->assertTrue($routes->has('GET users') || $routes->has('ANY users'),
            'anyIndex should map to the base /users path');
    }

    public function test_controller_skips_getMiddleware_method()
    {
        \AdvancedRoute::controller('/users', UserController::class);

        $routes = $this->getRegisteredRoutes();

        $hasMiddlewareRoute = $routes->contains(function ($route) {
            return str_contains($route->uri(), 'middleware');
        });

        $this->assertFalse($hasMiddlewareRoute, 'getMiddleware should not be registered as a route');
    }

    public function test_controller_skips_methods_without_http_verb_prefix()
    {
        \AdvancedRoute::controller('/users', UserController::class);

        $routes = $this->getRegisteredRoutes();

        $hasNonRoute = $routes->contains(function ($route) {
            return str_contains($route->uri(), 'non-route-method')
                || str_contains($route->uri(), 'nonroutemethod');
        });

        $this->assertFalse($hasNonRoute, 'Methods without HTTP verb prefix should not be registered');
    }

    public function test_controller_assigns_named_routes()
    {
        \AdvancedRoute::controller('/users', UserController::class);

        $routes = Route::getRoutes();

        $namedRoute = null;
        foreach ($routes as $route) {
            if ($route->getName() === 'user.get.show') {
                $namedRoute = $route;
                break;
            }
        }

        $this->assertNotNull($namedRoute, 'Route should be named user.get.show');
    }

    public function test_controller_registers_missing_method_catch_all()
    {
        \AdvancedRoute::controller('/wiki', WikiController::class);

        $routes = $this->getRegisteredRoutes();

        $hasMissingRoute = $routes->contains(function ($route) {
            return str_contains($route->uri(), '{_missing}');
        });

        $this->assertTrue($hasMissingRoute, 'missingMethod should register a {_missing} catch-all route');
    }

    public function test_controller_without_missing_method_does_not_register_catch_all()
    {
        \AdvancedRoute::controller('/users', UserController::class);

        $routes = $this->getRegisteredRoutes();

        $hasMissingRoute = $routes->contains(function ($route) {
            return str_contains($route->uri(), '{_missing}');
        });

        $this->assertFalse($hasMissingRoute, 'No {_missing} route should be registered without missingMethod');
    }

    public function test_controllers_batch_registration()
    {
        \AdvancedRoute::controllers([
            '/users' => UserController::class,
            '/wiki' => WikiController::class,
        ]);

        $routes = $this->getRegisteredRoutes();

        $this->assertTrue($routes->has('GET users/show/{id}'), 'UserController routes should be registered');
        $this->assertTrue($routes->has('GET wiki/create'), 'WikiController routes should be registered');
    }

    public function test_slug_converts_camel_case_to_kebab_case()
    {
        \AdvancedRoute::controller('/users', UserController::class);

        $routes = $this->getRegisteredRoutes();

        $this->assertTrue($routes->has('GET users/admin-profile'),
            'getAdminProfile should map to users/admin-profile (kebab-case)');
    }

    public function test_index_maps_to_base_path()
    {
        \AdvancedRoute::controller('/wiki', WikiController::class);

        $routes = $this->getRegisteredRoutes();

        $hasBaseRoute = $routes->contains(function ($route) {
            return $route->uri() === 'wiki';
        });

        $this->assertTrue($hasBaseRoute, 'getIndex should map to the base controller path');
    }

    public function test_optional_parameters_get_question_mark()
    {
        \AdvancedRoute::controller('/params', ParameterController::class);

        $routes = $this->getRegisteredRoutes();

        $hasOptional = $routes->contains(function ($route) {
            return str_contains($route->uri(), '{id?}');
        });

        $this->assertTrue($hasOptional, 'Optional parameters should be registered as {id?}');
    }

    public function test_required_parameters_without_type_hint()
    {
        \AdvancedRoute::controller('/users', UserController::class);

        $routes = $this->getRegisteredRoutes();

        $this->assertTrue($routes->has('GET users/show/{id}'),
            'Required parameter $id should be registered as {id}');
    }

    public function test_parameterless_routes_sorted_before_parameterized()
    {
        \AdvancedRoute::controller('/params', ParameterController::class);

        $routes = Route::getRoutes();

        $aboutPosition = null;
        $catchAllPosition = null;
        $position = 0;

        foreach ($routes as $route) {
            $uri = $route->uri();
            if (str_contains($uri, 'params/about')) {
                $aboutPosition = $position;
            }
            if (str_contains($uri, 'params/catch-all') || str_contains($uri, '{path}')) {
                $catchAllPosition = $position;
            }
            $position++;
        }

        $this->assertNotNull($aboutPosition, 'About route should exist');
        $this->assertNotNull($catchAllPosition, 'Catch-all route should exist');
        $this->assertLessThan($catchAllPosition, $aboutPosition,
            'Parameterless routes should be registered before parameterized routes');
    }

    public function test_controller_accepts_short_class_name()
    {
        class_alias(UserController::class, 'App\Http\Controllers\UserController');

        \AdvancedRoute::controller('/users', 'UserController');

        $routes = $this->getRegisteredRoutes();

        $hasRoute = $routes->contains(function ($route) {
            return str_contains($route->uri(), 'users');
        });

        $this->assertTrue($hasRoute, 'Controller should be resolved via app namespace when short name is given');
    }

    public function test_route_action_points_to_correct_controller_method()
    {
        \AdvancedRoute::controller('/users', UserController::class);

        $routes = Route::getRoutes();

        $found = false;
        foreach ($routes as $route) {
            $action = $route->getActionName();
            if (str_contains($action, 'UserController@getShow')) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, 'Route action should point to UserController@getShow');
    }

    public function test_patch_method_supported()
    {
        \AdvancedRoute::controller('/users', UserController::class);

        $routes = $this->getRegisteredRoutes();

        $hasPatch = $routes->contains(function ($route) {
            return in_array('PATCH', $route->methods());
        });

        $this->assertTrue($hasPatch, 'PATCH method should be supported (any maps to all verbs including patch)');
    }
}
