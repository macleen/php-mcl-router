<?php namespace MclRouter;


use MclRouter\RouteCollector;
use MclRouter\Requests\AdaptedRequest;
use MclRouter\RouteParams;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use MclRouter\Exceptions\TooLateToAddNewRouteException;



class RouterBase {

    const HTTP_OK                   = 200;
    private array $routes           = [];
    private ?RouteCollector $route_collector = null;
    private string $basePath        = '/';




    public function setBasePath($basePath) {
        $this->basePath = addLeadingSlash(addTrailingSlash($basePath));
        $this->route_collector = null; // Forces the router to rebuild
    }


    private function addRoute( Route $route ) {
        if ( $this->route_collector ) {
             throw new TooLateToAddNewRouteException('Can not add a new route now', 400);
        }
        $this->routes[] = $route;
    }


    /**
     * Map a route
     *
     * @param array $verbs
     * @param string $uri
     * @param callable|string $callback
     * @return Route
     * @throws TooLateToAddNewRouteException
     */
    public function map_single( string $verb, string $uri, $callback): Route {

        $route = new Route( strtoupper( $verb ), $uri, $callback );
        $this->addRoute( $route );
        return $route;

    }

    private function collect_routes( ) {
        
        if ( $this->route_collector) return;

        $this->route_collector = new RouteCollector();
        if (!empty($this->basePath)) {
             $this->route_collector->setBasePath($this->basePath);
        }

        foreach ($this->routes as $route) {
            $this->route_collector->map( $route );
        }
    }

    /**
     * Match the provided Request against the defined routes and return a Response
     *
     * @param Request $request
     * @return Response|bool returns false only if we don't matched anything
     */
    protected function match( ?Request $request = null) {

        $request = $request ?? Request::createFromGlobals();

        $this->collect_routes( );
        $route_collector = @$this->route_collector->match( $request );


        // Return false if we don't find anything
        if (
            !isset($route_collector['target']) || 
            !is_callable($route_collector['target'])) 
            {
                return false;
            }

        // Call the target with any resolved params
        $params = new RouteParams( $route_collector['params']);


        try {
            $output   = \call_user_func( $route_collector['target'], $params, new AdaptedRequest( $request, $route_collector['name']));
            $response = $output && $output instanceof Response ? $output
                      : new Response( is_string( $output ) ? $output : json_encode( $output ), self::HTTP_OK);
        } catch( \Exception $e ){
            $response = new Response( $e->getMessage( ), $e->getCode( ));
        }    

        return $response;
            
    }



    public function has(string $name) {
        $routes = array_filter($this->routes, function ($route) use ($name) {
            return $route->getName() === $name;
        });

        return count( $routes ) > 0;
    }


}