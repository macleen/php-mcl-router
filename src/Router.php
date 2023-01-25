<?php namespace MclRouter;

use MclRouter\RouterBase;
use MclRouter\RouteServiceProvider;
use MclRouter\Exceptions\NotFoundException;
use Symfony\Component\HttpFoundation\Response;



class Router extends RouterBase implements Routable {

    protected ?string $prefix = null;
    protected string|array $route_definition_files;



    public function __construct( string|array $route_definition_files, ?array $controllers_name_spaces = [ ]) { 
        Route::setup( $this,  $controllers_name_spaces );
        RouteServiceProvider::setup( $this, $route_definition_files );
    }

    /**
     * Map a route
     *
     * @param array $verbs
     * @param string $uri
     * @param callable|string $callback
     * @return Router
     */
    public function map( string|array $verbs, string $uri, Callable|string $callback): ?Route {

        $r     = null;
        $verbs = is_string( $verbs ) ? [ $verbs ] : $verbs;
        foreach( $verbs as $verb )
           $r = $this->map_single( $verb, $this->prefix.$uri, $callback );
        return $r;
    }


    /**
     * Create a Route group
     *
     * @param string $prefix
     * @param callable $callback
     * @return RouteGroup
     */
    public function group(string $prefix, string|Callable $callback): RouteGroup {
        $group = new RouteGroup( $this );
        return $group->prefix( $prefix )->register_through( $callback );
    }


    /**
     * enable group mode / used for routes grouped by file
     *
     * @param string $prefix
     * @return Router
     */
    public function simulate_group_mode( string $prefix = '' ) {
        $prefix       = removeLeadingSlash( $prefix );
        $this->prefix = $prefix ? addLeadingSlash( $prefix ) : '';
        return $this;
    }


    /**
     * disable group mode
     *
     * @return Router
     */
    public function disable_group_mode( ) {

        $this->prefix = null;
        return $this;

    }
    #----------------------------------------------------------------------
    #
    #----------------------------------------------------------------------
    public function register_through( Callable $callback ) : Router  {

        call_user_func( $callback, $this );
        return $this;

    }
    #----------------------------------------------------------------------
    #
    #----------------------------------------------------------------------
    /**
     * Attempt to match the current request against the defined routes
     *
     * If a route matches the Response will be sent to the client and PHP will exit.
     *
     */
    public function dispatch( ) {

        $response = $this->match( );
        return $response && ( $response instanceof Response ) ? $response
             : throw new NotFoundException('Route not found', 404);
    }
    #----------------------------------------------------------------------
    #
    #----------------------------------------------------------------------
}