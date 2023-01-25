<?php namespace MclRouter\Requests;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;


class AdaptedRequest {

    protected array $meta;
    protected ?array $attributes;

    public function __construct( protected SymfonyRequest &$request, ?string $route_name = '') {

        $this->meta = [
            'name'           => $route_name,
            'method'         => $this->request->getMethod(),
            'arguments'      => [],
            'currentUri'     => $this->request->getRequestUri(),
            'originalRequest'=> $request,
        ];

        $this->attributes    =  preg_match('~/json~i', (string) $request->headers->get('Content-Type'))
                             ? \json_decode($request->getContent(), true)
                             :  $this->request->request->all( );
    }

    public function all() {
        return $this->attributes;
    }

    public function __set( $property, $value ) {
        $this->attributes[ $property ] = $value;
    }

    public function __get($property) {
        if ( !isset($this->attributes[$property]))
             throw new \Exception( "{$property} does not exist on request input");
        return $this->attributes[ $property ];
    }

    public function __invoke( $property ) {
        return data_get($this->attributes, $property);
    }

    public function forget($property) {
        unset($this->attributes[$property]);
        return $this;
    }

    public function merge($array) {
        array_walk($array, fn ($value, $key) => data_set($this->attributes, $key, $value));
        return $this;
    }

    public function getRouteName() {
        return data_get( $this->meta, 'name' );
    }

    public function getArguments() {
        return data_get($this->meta, 'arguments');
    }
    public function getCurrentUri() {
        return data_get($this->meta, 'currentUri');
    }
    public function getOriginalRequest() {
        return data_get($this->meta, 'originalRequest');
    }
 
    public function getMethod() {
        return data_get($this->meta, 'method');
    }    

}