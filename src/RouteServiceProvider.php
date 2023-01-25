<?php namespace MclRouter;

use MclRouter\Exceptions\NotFoundException;
use MclRouter\Router;


class RouteServiceProvider {


    public static function setup( Router &$router, string|array $route_definition_files ){
        $routes = self::get_routes(  $route_definition_files );
        new static( $router, $routes ); 
    }

    protected static function get_routes( string|array $route_definition_files ){

        $route_definition_files = is_string( $route_definition_files )
                                ? [ $route_definition_files ] : $route_definition_files;
        $routes = [];
        collect( $route_definition_files )->map( 
            function( $path ) use ( &$routes ) {
                if ( \file_exists( $path )) {

                      $prefix = \basename(\basename( $path ), '.php');
                      $routes[ $prefix] = $path;
                } else throw new NotFoundException('Route file [ '.$path.' ] not found', 404);
         });
         return $routes;
    }



    protected function __construct( protected Router &$router, protected array $route_paths = [ ]){
        $this->register_RouteGroups();
    }



    public function register_RouteGroups() : void {

        collect( $this->route_paths )->map( 
            function( $path, $prefix ) {
                $prefix = $prefix == 'web' ? '' : $prefix;
                $this->router
                     ->simulate_group_mode( $prefix )
                     ->register_through( fn( ) => require( $path ))
                     ->disable_group_mode( );
            }
        );
   }

}