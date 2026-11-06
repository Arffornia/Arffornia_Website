<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RouteIntegrityTest extends TestCase
{
    public function test_every_controller_route_points_to_an_existing_method(): void
    {
        $missing = [];

        foreach (Route::getRoutes() as $route) {
            $action = $route->getActionName();

            if ($action === 'Closure') {
                continue;
            }

            [$class, $method] = str_contains($action, '@') ? explode('@', $action, 2) : [$action, '__invoke'];
            if (! class_exists($class) || ! method_exists($class, $method)) {
                $missing[] = $route->methods()[0] . ' ' . $route->uri() . ' -> ' . $action;
            }
        }

        $this->assertSame([], $missing, "Routes pointing to missing controller methods:\n" . implode("\n", $missing));
    }
}
