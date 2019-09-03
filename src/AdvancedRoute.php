<?php

class AdvancedRoute {

    // If EMIT_ROUTE_STATEMENTS, create the directory /tmp/controllerRoutes and
    // write one file each time we are called with the Route command that can
    // be used to replace the AdvancedRoute command in the routes file.
    //
    // Since Laravel has officially deprecated the Route::controller, if we want
    // to do the same, let's have this class emit the route statements we need.
    const EMIT_ROUTE_STATEMENTS = false;

    private static $httpMethods = ['any', 'get', 'post', 'put', 'patch', 'delete'];
    private static $methodNameAtStartOfStringPattern = null;

    public static function controller($path, $controllerClassName) {
        if( class_exists($controllerClassName) ) {
            $class = new ReflectionClass($controllerClassName);
        } else {
            $class = new ReflectionClass(app()->getNamespace() . 'Http\Controllers\\' . $controllerClassName);
        }

        $routes = [];

        $publicMethods = $class->getMethods(ReflectionMethod::IS_PUBLIC);

        // The methods from each class will be in some random order, but mostly likely in the
        // order they were defined in the original file.  However, when issuing route commands we
        // need to ensure that more specific routes take precedences over ones with a parameter.
        // So for example if we have both the routes
        // Route::get('foo/{any}', 'FooController@anyIndex');
        // Route::get('foo/about', 'FooController@getAbout');
        // We need to ensure that the '/foo/about' route is issued first or it will never be invoked
        // as the anyIndex will be will be called instead.
        $methods = [];
        foreach ($publicMethods as $method) {
            if ($method->name == 'getMiddleware') {
                continue;
            }

            $method->slug = self::slug($method);
            $methods[] = $method;
        }

        // Sort the routes so that the routes without any parameters, one with no {, come first.
        usort($methods, function($a, $b) {
            $aHasParam = false !== strpos($a->slug, '{');
            $bHasParam = false !== strpos($b->slug, '{');

            if (!$aHasParam && $bHasParam) {
                return -1;
            }
            if (!$bHasParam && $aHasParam) {
                return 1;
            }
            return (strcmp($a->slug, $b->slug));
        });

        $addMissingMethod = false;
        foreach ($methods as $method) {
            $slug = $method->slug;
            $methodName = $method->name;
            $slug_path = $path . '/' . $slug;

            if ($methodName == 'missingMethod') {
                $addMissingMethod = true;
                continue;
            }

            $httpMethod = null;
            foreach (self::$httpMethods as $httpMethod) {
                if (self::stringStartsWith($methodName, $httpMethod)) {
                    Route::$httpMethod($slug_path, $controllerClassName . '@' . $methodName);

                    $route = new \stdClass();
                    $route->httpMethod = $httpMethod;
                    $route->prefix = sprintf("Route::%-4s('%s',", $httpMethod, $slug_path);
                    $route->target = $controllerClassName . '@' . $methodName;
                    $routes[] = $route;
                    break;
                }
            }
        }

        // add the _missing route last otherwise it will be hit by routes whose
        // method names start with a letter greater than the letter 'm'
        if ($addMissingMethod) {
            $methodName = 'missingMethod';
            $slug_path = str_replace('//', '/', $path.'/'.'{_missing}');
            Route::any($slug_path, $controllerClassName . '@' . $methodName);

            $route = new \stdClass();
            $route->httpMethod = 'any';
            $route->prefix = sprintf("Route::%-4s('%s',", 'any', $slug_path);
            $route->target = $controllerClassName . '@' . $methodName;
            $routes[] = $route;
        }

        if (self::EMIT_ROUTE_STATEMENTS) {
            self::emitRoutes($routes);
        }
    }

    /**
     * Ability to use several path-controller pairs
     *
     * Example:
     * [
     *     '/personal' => 'PersonalController',
     *     '/news'     => 'NewsController',
     *     ...
     * ]
     *
     * @param array $routes
     */
    public static function controllers(array $routes) {
        foreach ($routes as $path => $controllerClassName) {
            static::controller($path, $controllerClassName);
        }
    }

    protected static function stringStartsWith($string, $match) {
        return (substr($string, 0, strlen($match)) == $match) ? true : false;
    }

    protected static function slug($method) {
        // Can't use str_replace to remove the httpMethod from the method name because this removes als strings whereever
        // they appear in the string, so for example, getCompanyname, becomes "compname" instead of companyname because "any"
        // is removed from the word company in addition to the removal of "get" from the front of the string. Use a preg
        // to anchor the search to the front of the method name.
        if (!self::$methodNameAtStartOfStringPattern) {
            self::$methodNameAtStartOfStringPattern = '/^(' . implode('|', self::$httpMethods) . ')/';
        }

        $cleaned = preg_replace(self::$methodNameAtStartOfStringPattern, '', $method->name);
        $snaked = \Illuminate\Support\Str::snake($cleaned, ' ');
        $slug = \Illuminate\Support\Str::slug($snaked, '-');

        if ($slug == "index") {
            $slug = "";
        }

        foreach ($method->getParameters() as $parameter) {
            if (self::hasType($parameter)) {
                continue;
            }
            $slug .= sprintf('/{%s%s}', strtolower($parameter->getName()), $parameter->isDefaultValueAvailable() ? '?' : '');
        }

        if ($slug != null && $slug[0] == '/') {
            return substr($slug, 1);
        }

        return $slug;
    }

    protected static function hasType(ReflectionParameter $param) {
        //TODO: if php7 use the native method

        preg_match('/\[\s\<\w+?>\s([\w]+)/s', $param->__toString(), $matches);
        return isset($matches[1]) ? true : false;
    }

    protected static function emitRoutes($routes) {
        // Compute max length of the "prefix" of each route command so that when we issue the strings
        // we can make all the 2nd parameters line up verticaly.  Easier to read and thus easier to check
        // to ensure we are emitting the right routes.
        $maxPrefixLen = 0;
        array_walk($routes, function($route) use (&$maxPrefixLen) {
            $l = strlen($route->prefix);
            if ($l > $maxPrefixLen) {
                $maxPrefixLen = $l;
            }
        });

        if (!is_dir('/tmp/controllerRoutes')) {
            mkdir('/tmp/controllerRoutes');
        }

        $routeList = sprintf("<?php\n// %s \"Controller\" Routes\n", $controllerClassName);
        foreach ($routes as $route) {
            $routeList .= sprintf("%-{$maxPrefixLen}s '%s');\n", $route->prefix, $route->target);
        }

        file_put_contents("/tmp/controllerRoutes/{$controllerClassName}.php", $routeList . PHP_EOL);
    }

}
