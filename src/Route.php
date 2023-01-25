<?php namespace MclRouter;

use MclRouter\Router;
use Illuminate\Support\Str;
use MclRouter\Exceptions\RouteClassStringControllerNotFoundException;
use MclRouter\Exceptions\RouteNameRedefinedException;


class Route {


    private $route_template;
    private $method;
    private $action;
    private $name;
    private $regex;
    private static $router;
    private static ?array $controllers_name_spaces;


    public static function setup( Router &$router, array $controllers_name_spaces = [ ]) {
        self::$router = $router; 
        self::$controllers_name_spaces = $controllers_name_spaces;
    }

    public function __construct( string $method, string $route_template, string|Callable $action ) {

        $this->method = $method;
        $this->route_template    = \rtrim( $route_template, ' /');
        $this->setAction( $action );

    }


    private function setAction( string|Callable $action) {

        $this->validate_input( $this->route_template, $this->method, $action );
        $this->action = \is_callable( $action )
                      ? $action : $this->resolve_via_controller( $action );
    }


    public function where( ?array $regex_parms_definition = null ) : self {

        $this->regex = $regex_parms_definition;
        return $this;

    }


    private function resolve_via_controller( $action ) {

        $fqc    = null;
        $class  = Str::before( $action, '@' );
        $method = Str::after ( $action, '@' );

        foreach ( self::$controllers_name_spaces as $namespace ) {
             if ( \class_exists( $namespace . $class )) {
                  $fqc = $namespace . $class; // fully qualified class name 
                  break;
             }
        }


        if ( $fqc )
             return [ new $fqc( ), $method ];

        throw new RouteClassStringControllerNotFoundException('Could not find route controller class: `' . $class . '`');
    }



    /**
     * Create a Route group
     *
     * @param string $prefix
     * @param callable $callback
     * @return RouteGroup
     */
    public static function group(string $prefix, string|Callable $callback): RouteGroup {
        return self::$router->group( $prefix, $callback );
    }



   /**
     * Map a route using the requested method
     *
     * @param array [ string route_template, callable|string callable callback]
     * @return Route
     */
    public static function __callStatic( $method, $args ) : ?Route {

        [ $route_template, $callback ] = $args;
        return self::$router->map( $method, $route_template, $callback );
    }


    public function getUri() {
        return $this->route_template;
    }

    public function getRegEx() {
        return $this->regex;
    }

    public function getMethod() {
        return $this->method;
    }

    public function getAction(){
        return $this->action;
    }

    public function name(string $name) {
        if ( isset($this->name ))
             throw new RouteNameRedefinedException( 'Route name is already defined' );

        $this->name = $name;
        return $this;

    }

    public function getName() {
        return $this->name;
    }

    protected function validate_input( $route, $verb, $action ) {

        $exception = "Unresolvable Route Callback/Controller action";
        $context   = \json_encode(compact('route', 'action', 'verb'));
        $fails     = !(( is_callable( $action )) || ( is_string( $action ) && Str::is( "*@*", $action )));

        return !$fails ? true
             : throw new RouteClassStringControllerNotFoundException( $exception . $context, 400 );
    }
}