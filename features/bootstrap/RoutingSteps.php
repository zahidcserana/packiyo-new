<?php

trait RoutingSteps
{
    /**
     * Used to rewrite the global request with specified route and inject the parameters
     *
     * @param string $method
     * @param string $routeName
     * @param array $parameters
     * @return void
     */
    public function mockRequest(string $method, string $routeName, array $parameters = [])
    {
        $uri = Route::getRoutes()->getByName($routeName)->uri();

        $request = Request::create(
            route($routeName, $parameters),
            $method,
            $parameters
        );

        $this->app->instance(Request::class, $request);

        request()->setRouteResolver(function () use ($parameters, $uri, $method, $request) {
            $route = new Illuminate\Routing\Route(
                $method,
                $uri,
                $parameters
            );

            return $route->bind($request);
        });
    }
}
