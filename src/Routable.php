<?php namespace MclRouter;

use MclRouter\Route;
use MclRouter\RouteGroup;

interface Routable  {

    public function map( string|array $verbs, string $uri, Callable|string $callback): ?Route;
    public function group(string $prefix, string|Callable $callback): RouteGroup;

}