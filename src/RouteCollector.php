<?php namespace MclRouter;

use MclRouter\Route;
use MclRouter\Exceptions\RuntimeException;
use Symfony\Component\HttpFoundation\Request;


class RouteCollector {

    const OPTIONAL_DISPATCH_REGEX = '(/?[^/]*$)';
    const REQUIRED_DISPATCH_REGEX = '([^/]+)';
    const VARIABLE_REGEX = <<<'REGEX'
~\{
    \s* (\w+\??) \s*
\}~x
REGEX;


    /**
     * @var array Array of all routes (incl. named routes).
     */
    protected $routes = [];

    /**
     * @var array Array of all named routes.
     */
    protected $namedRoutes = [];

    /**
     * @var string Can be used to ignore leading part of the Request URL (if main file lives in subdirectory of host)
     */
    protected $basePath = '';

    /**
     * @var string Can be used to ignore leading part of the Request URL (if main file lives in subdirectory of host)
     */

    private ?string $processed_route;
    private ?array $parms = [];
    private ?string $uri;

    /**
     * @var array Array of default match types (regex helpers)
     */
    protected $matchTypes = [
        'i'  => '[0-9]++',
        'a'  => '[0-9A-Za-z]++',
        'h'  => '[0-9A-Fa-f]++',
        '*'  => '.+?',
        '**' => '.++',
        ''   => '[^/\.]++'
    ];

    /**
     * Create object in one call from config.
     *
     * @param array $routes
     * @param string $basePath
     * @param array $matchTypes
     * @throws Exception
     */
    public function __construct(array $routes = [], $basePath = '', array $matchTypes = []) {
        $this->addRoutes($routes);
        $this->setBasePath($basePath);
        $this->addMatchTypes($matchTypes);
    }

    /**
     * Retrieves all routes.
     * Useful if you want to process or display routes.
     * @return array All routes.
     */
    public function getRoutes() {
        return $this->routes;
    }

    /**
     * Add multiple routes at once from array in the following format:
     *
     *   $routes = [
     *      [$method, $route, $target, $name]
     *   ];
     *
     * @param array $routes
     * @return void
     * @author Koen Punt
     * @throws Exception
     */
    public function addRoutes($routes) {
        if (!is_array($routes) && !$routes instanceof \Traversable) {
            throw new RuntimeException('Routes should be an array or an instance of Traversable');
        }
        array_walk( $routes, fn( $route ) => call_user_func_array([$this, 'map'], $route));        
        // foreach ($routes as $route) {
        //     call_user_func_array([$this, 'map'], $route);
        // }
    }

    /**
     * Set the base path.
     * Useful if you are running your application from a subdirectory.
     * @param string $basePath
     */
    public function setBasePath($basePath) {
        $this->basePath = $basePath;
    }

    /**
     * Add named match types. It uses array_merge so keys can be overwritten.
     *
     * @param array $matchTypes The key is the name and the value is the regex.
     */
    public function addMatchTypes(array $matchTypes) {
        $this->matchTypes = array_merge($this->matchTypes, $matchTypes);
    }

    /**
     * Map a route to a target
     *
     * @param string $method One of 5 HTTP Methods, or a pipe-separated list of multiple HTTP Methods (GET|POST|PATCH|PUT|DELETE)
     * @param string $route_template The route regex, custom regex must start with an @. You can use multiple pre-set regex filters, like [i:id]
     * @param mixed $target The target where this route should point to. Can be anything.
     * @param string $name Optional name of this route. Supply if you want to reverse route this url in your application.
     * @throws Exception
     */
    public function map( Route $route ) {

        $this->routes[] = $route;
        $name           = $route->getName( );

        if ($name) {
            if (isset($this->namedRoutes[$name])) {
                throw new RuntimeException("Can not redeclare route '{$name}'");
            }
            $this->namedRoutes[$name] = $name;
        }

        return;
    }

    /**
     * Reversed routing
     *
     * Generate the URL for a named route. Replace regexes with supplied parameters
     *
     * @param string $routeName The name of the route.
     * @param array @params Associative array of parameters to replace placeholders with.
     * @return string The URL of the route with named parameters in place.
     * @throws Exception
     */
    public function generate($routeName, array $params = []) {

        // Check if named route exists
        if (!isset($this->namedRoutes[$routeName])) {
            throw new RuntimeException("Route '{$routeName}' does not exist.");
        }

        $route = $this->namedRoutes[$routeName];  // Replace named parameters
        $url = $this->basePath . $route;          // prepend base path to route url again 

        if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $route, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $index => $match) {

                list($block, $pre, $type, $param, $optional) = $match;
                $block = $pre ? $block = substr($block, 1) : $block;

                if (isset($params[$param])) // Part is found, replace for param value                    
                    $url = str_replace($block, $params[$param], $url);
                elseif ($optional && $index !== 0) // Only strip preceding slash if it's not at the base                    
                    $url = str_replace($pre . $block, '', $url);
                else // Strip match block
                    $url = str_replace($block, '', $url);
            }
        }

        return $url;
    }

    /**
     * Match a given Request Url against stored routes
     * @param string $requestUrl
     * @param string $requestMethod
     * @return array|boolean Array with route information on success, false on failure (no match).
     */
    public function match( ?Request $request = null ) : array|bool {

        // set Request Url if it isn't passed as parameter
        $requestUrl = stripslashes($request->getRequestUri());
        $requestMethod = $request->getMethod();


        // strip base path from request url
        $requestUrl = substr($requestUrl, strlen($this->basePath));
        // Strip query string (?a=b) from Request Url
        if (($strpos = strpos($requestUrl, '?')) !== false) {
            $requestUrl = substr($requestUrl, 0, $strpos);
        }

        $this->uri = addLeadingSlash($requestUrl);

        // // set Request Method if it isn't passed as a parameter
        // if ($requestMethod === null) {
        //     $requestMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        // }
        

        

        foreach ($this->routes as $route) {

            $methods         = $route->getMethod();
            $uri_template    = $route->getUri();
            $target          = $route->getAction();

            $method_match    = (stripos($methods, $requestMethod) !== false);            
            $route_templates = [
                                addTrailingSlash($uri_template),
                                removeTrailingSlash($uri_template),
                               ]; 

            if ( $method_match ) {
                 $match = false;                
                 foreach( $route_templates as $route_template ) {
                    if ( !$match ) {
                          $this->processed_route = $route_template;
                          $match = $this->match_route( $route_template, $route->getRegEx( ));
                    }
                 }


                if ($match) {
                    if ( !empty( $this->parms )) {
                        foreach ($this->parms as $key => $v) {
                            if (is_numeric($key)) {
                                unset($this->parms[$key]);
                            }
                        }
                    }

                    return [
                        'target' => $target,
                        'params' => $this->parms,
                        'name'   => $route->getName()
                    ];
                }
            }
        }

        return false;
    }


    /**
     * Compile the regex for a given route (EXPENSIVE)
     * @param $route
     * @return string
     */
    protected function match_route( $route_template, ?array $reg_x = null ) {

        $is_dynamic_route = strpos( $route_template, '{') !== false;
        return match ( $is_dynamic_route ) {
            true    => $this->match_compiled_route( $route_template, $reg_x ),
            default => $this->match_static_route( $route_template, $reg_x ),
        };

    }

    protected function match_static_route( $route_template ) {
        // var_dump($this->uri);
        // var_dump($route_template);
        return \strcmp( $this->uri, $route_template) === 0;
    }
        
    /**
     * Compile the regex for a given route (EXPENSIVE)
     * @param $route
     * @return string
     */
    protected function match_compiled_route( $route_template, ?array $reg_x = null ) {

        $varibale_names = [];        
        if ( preg_match_all(self::VARIABLE_REGEX, $route_template, $matches )) {

            $res = $matches[1];
            $last_stage = count($matches[1])-1;
            foreach( $res as $k => $parm ) {

                $parm      = trim( $parm );
                $pure_name = str_replace('?', '', $parm);
                $regex     = $reg_x[ $pure_name ] ?? '';

                $optional = (int) str_ends_with( $parm, '?');
                if ( $optional && $k < $last_stage )
                     throw new \Exception('Optional route parameters are only allowed at the end of the route path');


                $substitution = $optional ? self::OPTIONAL_DISPATCH_REGEX : 
                                ($regex   ? '('.$regex.')' : self::REQUIRED_DISPATCH_REGEX);
                $matches[0][$k] = $optional ? '/'.$matches[0][$k] : $matches[0][$k];
                $varibale_names[] = ['name' => $pure_name, 'optional' => $optional, 'regex' => $regex ];
                $this->processed_route = str_replace( $matches[0][$k], $substitution, $this->processed_route);
            }
            return $this->build_route_variables( $varibale_names );
        }
    }
    
    
    private function build_route_variables( ?array $varibale_names ) {


        $match = preg_match_all('~'.$this->processed_route.'~', $this->uri, $m);
        if ( $match ) {

            for( $i = 1; $i < count( $m ); $i++ ) {
                 $vn    = $varibale_names[$i - 1];
                 $value = trim($m[$i][0], '/');
                 if ( $vn['optional'] && !empty( $value ) && !empty( $vn['regex'] )){
                        if ( !preg_match( '~/'.$vn['regex'].'$~', '/'.$value )) {
                            return false;
                      }                           
                 }      
                 $this->parms[ $varibale_names[$i - 1]['name']] = $value;
            } 
       }

       return $match;
    }


}