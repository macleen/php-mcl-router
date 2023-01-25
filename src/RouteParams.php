<?php namespace MclRouter;

use \Iterator;

class RouteParams implements Iterator {

    private $position = 0;
    private $params = [];

    public function __construct(array $params) {
        $this->params = $params;
    }

    public function __get($key) {
        if (!isset($this->params[$key])) {
            return null;
        }

        return $this->params[$key];
    }

    public function rewind() : void {
        $this->position = 0;
    }

    public function current() : mixed {
        return $this->params[$this->key()];
    }

    public function key(): mixed {
        $keys = array_keys($this->params);
        return $keys[$this->position];
    }

    public function next() : void {
        $this->position++;
    }

    public function valid(): bool {
        return $this->position < count($this->params);
    }
}