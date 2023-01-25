<?php namespace MclRouter;

use MclRouter\Route;
use MclRouter\Router;


class RouteGroup {

    #----------------------------------------------------------------------
    #
    #----------------------------------------------------------------------
    protected Router $router;
    protected string $prefix;
    #----------------------------------------------------------------------
    #
    #----------------------------------------------------------------------
    public function __construct( Router $router ) {
        $this->router = $router;
    }
    #----------------------------------------------------------------------
    #
    #----------------------------------------------------------------------
    public function prefix( $prefix = '/') {
        $this->prefix = '/'.trim( trim( $prefix ), '/');
        return $this;
    }
    #----------------------------------------------------------------------
    #
    #----------------------------------------------------------------------
    public function map( string|array $verbs, string $uri, Callable|string $callback ) {

        return $this->router->map( $verbs, $this->prefix.$uri, $callback);

    }
    #----------------------------------------------------------------------
    #
    #----------------------------------------------------------------------
    public function __call( $method, $args ) : Route {

        [ $uri, $callback ] = $args;
        return Route::{ $method }( $this->prefix.$uri, $callback );

    }
    #----------------------------------------------------------------------
    #
    #----------------------------------------------------------------------
    public function register_through( Callable $callback ) : RouteGroup  {

        call_user_func( $callback, $this );
        return $this;

    }
    #----------------------------------------------------------------------
    #
    #----------------------------------------------------------------------
}
