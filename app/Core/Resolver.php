<?php

namespace Ceremonies\Core;

class Resolver
{

    /**
     * @var array
     */
    protected $args = [];

    /**
     * @var callable
     */
    protected $resolver;

	/**
	 * @param string $method
	 * @param string $path
	 * @param mixed $callback
	 * @param mixed $middleware
	 */
    public function __construct(
        protected string $method,
        protected string $path,
        protected mixed $callback,
	    protected mixed $middleware
    ) {}

    /**
     * {@inheritdoc}
     */
    public function setArg(string $name, array $config): Route
    {
        $this->args[$name] = $config;
        return $this;
    }


    /**
     * Sets up the arguments array for a route, adds
     * check_origin to ensure requests come from the
     * site.
     */
    public function toArray(): array
    {
        $args = [
            'methods' => $this->method,
            'callback' => $this->getCallback(),
            'args' => $this->args,
            'show_in_index' => false,
            'permission_callback' => '__return_true',
            'check_origin' => function( $request ) {
                return parse_url( $request->get_header( 'origin' ), PHP_URL_HOST ) === parse_url( home_url(), PHP_URL_HOST );
            },
        ];
        return [
            'path' => $this->path,
            'args' => $args,
        ];
    }

    /**
     * @return callable
     */
    protected function getCallback(): callable
    {
        // Add controllers
        if (is_callable($this->callback)) {
            return $this->callback;
        }
        if (is_array($this->callback)) {
            return $this->makeMethodClosure();
        }
        throw new \Exception("failed to create REST callback", [
            'callback' => $this->callback,
        ]);
    }

    /**
     * @return \Closure
     */
    protected function makeMethodClosure(): \Closure
    {
        [$class, $method] = $this->callback;
        if (!class_exists($class)) {
            throw new \Exception(
                "failed to create REST callback because class $class is non-existent"
            );
        }
        return function (\WP_REST_Request $req) use ($class, $method) {
            $instance = $this->resolveClass($class);
            if (!method_exists($instance, $method)) {
                throw new \Exception(
                    "failed to create REST callback because method $method on class $class is non-existent"
                );
            }
			$this->runMiddleware();
            return $instance->$method($req);
        };
    }

	/**
	 * Run through each middleware item attached
	 * to the route.
	 *
	 * @return void
	 * @throws \DI\DependencyException
	 * @throws \DI\NotFoundException
	 */
	protected function runMiddleware() {
		foreach ($this->middleware as $middlewareClass) {
			$item = Bootstrap::container()->get($middlewareClass);
			// Note: ALL middleware classes must have a load and fail method that throws a 4XX or 5XX error.
			$item->load($this);
		}
	}

    /**
     * @return Object
     */
    protected function resolveClass(string $class)
    {
        return Bootstrap::container()->get($class);
    }

}