<?php

namespace Ceremonies\Core;

class Router
{

    private array $routes = [];
    private string $api_namespace = 'sc/v1';

    public static function init(): Router
    {
	    header("Access-Control-Allow-Origin: *");
        return new self();
    }

    /**
     * Loads a router file.
     *
     * @param $file string
     * @return Router
     */
    public function load($file): static
    {
        include CEREMONIES_ROOT . '/app/' . $file;
        return $this;
    }

	/**
	 * Adds an API route to the plugin.
	 *
	 * @param string $route
	 * @param string $request - Must be an HTTP verb
	 * @param array $callback - Class, Method
	 * @param array $middleware
	 *
	 * @return void
	 */
    public function add($route = '', $request = 'GET', $callback = [], $middleware = []): void
    {
        $resolver = new Resolver($request, $route, $callback, $middleware);
        $this->routes[] = $resolver;
    }

    /**
     * Registers all added $this->routes within the WordPress REST API.
     *
     * @return void
     */
    public function register() {
        foreach ($this->routes as $route) {
            $route_args = $route->toArray();
            register_rest_route( $this->api_namespace, $route_args['path'], $route_args['args']);
        }
    }

}