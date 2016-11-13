<?php

class AdvancedRoute {

    public static function controller($path, $controllerClassName) {
        $class = new ReflectionClass('App\Http\Controllers\\' . $controllerClassName);

        $publicMethods = $class->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($publicMethods as $method) {
            $methodName = $method->name;
            if ($methodName == 'getMiddleware') {
                continue;
            }
            if (self::stringStartsWith($methodName, 'any')) {
                $slug = self::slug($method);
                //var_dump($slug);
                Route::any($path . '/' . $slug, $controllerClassName . '@' . $methodName);
            }
            if (self::stringStartsWith($methodName, 'get')) {
                $slug = self::slug($method);
                //var_dump($slug);
                Route::get($path . '/' . $slug, $controllerClassName . '@' . $methodName);
            }
            if (self::stringStartsWith($methodName, 'post')) {
                $slug = self::slug($method);
                //var_dump($slug);
                Route::post($path . '/' . $slug, $controllerClassName . '@' . $methodName);
            }
        }

        Route::get($path, $controllerClassName . '@anyIndex');
    }

    protected static function stringStartsWith($string, $match) {
        return (substr($string, 0, strlen($match)) == $match) ? true : false;
    }

    protected static function slug($method) {
        $methodName = $method->name;
        $cleaned = str_replace(['any', 'get', 'post', 'delete'], '', $methodName);
        $snaked = \Illuminate\Support\Str::snake($cleaned, ' ');
        $slug = str_slug($snaked, '-');
        foreach ($method->getParameters() as $parameter) {
            if ($parameter->hasType()) {
                continue;
            }
            $slug .= sprintf('/{%s%s}', $parameter->getName(), $parameter->isDefaultValueAvailable() ? '?' : '');
        }
        return $slug;
    }

}
