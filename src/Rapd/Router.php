<?php

namespace Rapd;

use \Rapd\Router\Route;

class Router {
	use Prototype;

	protected static $routes = [];
	protected static $urlPrefix = "";

	public static function add(Route $route){
		self::$routes[$route->name] = $route;
	}

	public static function match(string $uri){
		foreach(self::$routes as $route){
			if($route->match($uri)){
				return $route;
			}
		}

		return false;
	}

	public static function makeUrlTo(string $name, array $data = []){
		if(array_key_exists($name, self::$routes)){
			$routeUrl = self::$routes[$name]->makeUrl($data);
			return self::$urlPrefix.$routeUrl;
		} else {
			return "#no-such-route:{$name}";
		}
	}

	public static function redirectTo(string $name, array $data = []){
		header("Location: ".self::makeUrlTo($name, $data));
		exit;
	}

	public static function setBasePath(string $base){
		self::$urlPrefix = $base;
	}

	public static function getRouteByName(string $name) : Route {
		return self::$routes[$name];
	}

	public static function getAllRoutes() : array {
		return self::$routes;
	}
}
