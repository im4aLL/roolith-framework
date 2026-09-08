<?php
namespace App\Core;

use Throwable;

/**
 * Route handler safety validator.
 *
 * Validates string-based Controller@method dispatch before runtime so
 * misconfigured routes fail fast with context instead of a vendor 404
 * or 500 at request time. Also documents the preferred callable
 * syntax [$class, $method] for docs and generators.
 */
final class RouteValidator
{
    /**
     * Validate a Controller@method string reference.
     *
     * Checks the Class@method shape, class_exists, and method_exists.
     * Returns null when valid, otherwise a human-readable error.
     *
     * @param string $reference Handler string like App\Controllers\WelcomeController@index.
     * @return string|null Null when valid, error message otherwise.
     */
    public static function validateStringHandler(string $reference): ?string
    {
        if (!str_contains($reference, '@')) {
            return "Invalid controller reference '{$reference}' (expected 'Class@method'). Prefer [Class::class, 'method'] callable syntax.";
        }

        [$className, $methodName] = explode('@', $reference, 2);

        if (trim($className) === '' || trim($methodName) === '') {
            return "Invalid controller reference '{$reference}' (empty class or method). Prefer [Class::class, 'method'] callable syntax.";
        }

        if (!class_exists($className)) {
            return "Route handler class '{$className}' does not exist for reference '{$reference}'.";
        }

        if (!method_exists($className, $methodName)) {
            return "Route handler method '{$methodName}' does not exist on '{$className}' for reference '{$reference}'.";
        }

        return null;
    }

    /**
     * Validate a route handler of any supported shape.
     *
     * Strings go through validateStringHandler(). Closures, callables,
     * and [$class, $method] arrays pass when callable or when the
     * class plus method exist. Anything else is invalid.
     *
     * @param mixed $handler Route handler value.
     * @return string|null Null when valid, error message otherwise.
     */
    public static function validateHandler(mixed $handler): ?string
    {
        if (is_string($handler)) {
            if (str_contains($handler, '@')) {
                return self::validateStringHandler($handler);
            }

            return "Invalid route handler string '{$handler}' (expected 'Class@method' or a closure). Prefer [Class::class, 'method'] callable syntax.";
        }

        if (is_array($handler) && count($handler) === 2 && isset($handler[0], $handler[1])) {
            $className = $handler[0];
            $methodName = $handler[1];

            if (is_string($className) && is_string($methodName)) {
                if (trim($className) === '' || trim($methodName) === '') {
                    return 'Invalid route handler array (empty class or method).';
                }

                if (!class_exists($className)) {
                    return "Route handler class '{$className}' does not exist.";
                }

                if (!method_exists($className, $methodName)) {
                    return "Route handler method '{$methodName}' does not exist on '{$className}'.";
                }

                return null;
            }

            if (is_callable($handler)) {
                return null;
            }

            return 'Invalid route handler array (expected [Class::class, \'method\'] or a callable).';
        }

        if (is_callable($handler)) {
            return null;
        }

        return 'Invalid route handler (expected closure, [Class::class, \'method\'], or \'Class@method\').';
    }

    /**
     * Validate all handlers in a route list.
     *
     * Iterates the vendor getRouteList() shape and collects errors for
     * every execute handler via validateHandler(). Every handler shape
     * (string, array, closure, callable) is routed through
     * validateHandler() so array handlers are linted the same as
     * strings. Used by the route:list lint command and bootstrap
     * assertions.
     *
     * @param array<int, array<string, mixed>> $routes Route list from Router::getRouteList().
     * @return array<int, string> Error messages, empty when all valid.
     */
    public static function validateRouteList(array $routes): array
    {
        $errors = [];

        foreach ($routes as $index => $route) {
            try {
                $handler = $route['execute'] ?? null;
                $error = self::validateHandler($handler);

                if ($error !== null) {
                    $path = isset($route['path']) && is_string($route['path']) ? (string) $route['path'] : '#'.$index;
                    $errors[] = "[{$path}] {$error}";
                }
            } catch (Throwable) {
                $errors[] = "[#{$index}] Unable to validate handler.";
            }
        }

        return $errors;
    }
}
