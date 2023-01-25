# MCL-ROUTER
>Lightweight PHP Router package (Version 1.0 patch 1)


## Introduction

**Mcl-Router** is a small sized PHP library intended for use/integration in your application


## Usage Notes


   -  **IMPORTANT**\

   In your root composer.json file please insert these 2 flags:

```json
   {
      "here are the various flags of the":"composer.jsn file",
      "minimum-stability": "dev",
      "prefer-stable": true
   }
```



## General Notes

There are few things that I dont like about existing (non-framework based) php routers

   -  **Layout**\
    Most existing library use layouts that are somewhat different than the comon layout used in popular frameworks like slim or laravel. This means if you are used to using frameworks but need a fast light router for non framework packages you are stuck with the layout imposed by the selected package which is mostly different than what you are used to...so you must dig into the source code to learn how to use it.


   -  **Use of @ in controller methods**\
    If you chose to integrate packages like FastRoute you must use router call back as fully qualified PHP functions like get('/home', function(){....}) or 
    get('/home', [controller, method]) and have to declare the use of the controller namespace on top of your code. You can not use the folloing notaion controller@method
    

   -  **Route service provider**\
    If your package containes route files such as ( api.php, web.php, admin.php...etc) those files are processed automatically by the route service provider (RouteServiceProvider.php) in the form of groups taking the file name as prefix (on the eception of web.php => defaults to '/'). This service provider must be instanciated early on in your package (via the service container or manually if your package does not use one).



## Package External Dependencies

The library is depending on the following external packages:


   -  **Illulintate/Collections/**\
    Lightly used for a better handling of arrays

   -  **Illulintate/Support/**\
    Lightly used for a better handling of strings


  -  **Symphony/HttpFoundation**\
    Any PSR7 compliant response/request package will do. I went for symphony since it is one of the most used out there
  



## Package files (content)

The package contains the following folders:

   -  **src/**\
    Containing the main package files

  -  **src/Requests**\
    This folder contains an AdaptedRequest class extending Symphony Request class for ease of use
  

## Instantiation


  -  **Routes definition**\
     In case your app contains multiple sections (ex. web section, api section, admin section) use seperate route files for each section. route prefixes will be automatically assigned to the individuel routes depending on the file in which they are contained. As an example lets assume that you have an api route file defined in '/path/subpath1/subpath2/api.php', all the http requests must start with /api/path/... to land in this file. The deinitions do not have to be written with the prefix in the api.php file:


```php
//This is the api.php routes file

use MclRouter\Route;
use MclRouter\RouteGroup;


Route::get('/', 'HomeController@index');
Route::post('/provider/upload', 'UploadController@upload_files');

#------------------------------------------------------------------

Route::group('/user/{id}', function( RouteGroup $group ){
        $group->get('/', 'UserListController@get');
        $group->get('/state', 'UserStatesController@index');
        $group->delete('/', 'UserMainController@delete');
});

```




  -  **Router.php**\
     This is the main package class. it takes care of the registration of predefined route groups sitting is seperate files (web.php, api.php, admin.php...etc...) using the RouteServiceProvider. it takes 2 parameters the the roue paths: string|array $route_definition_paths: paths to the route definition files in the form of a string '/path/subpath1/subpath2/web.php' if you have only one route file or in the form of ['/path/subpath1/subpath2/web.php','/path/subpath1/subpath2/api.php'] if you have more, and an array of controller name spaces ( where your controllers are defined ).


```php
    use MclRouter\Router;
    $router = new Router( string|array $route_definition_files, ?array $controllers_name_spaces = [ ]);
```


   -  **Illustration**

    
```php

    // this is my main index.php file
    // Assuming your main namespace is app and a viewer
    // class is present to display the final response

    require __DIR__ . '/vendor/autoload.php';
    #------------------------------------------------------------------------
    $routes = [ __DIR__ . '/routes/api.php', __DIR__ . '/routes/web.php' ];

    (new App\Render\Viewer( ))
       ->render(( new MclRouter\Router( $routes, [ '\\App\\Controllers\\']))
       ->dispatch( ));

```

 
   -  **Installation**

   composer require macleen/mcl-router


---------------------------------------------------------
 >Author: C. Mahmoud / MacLeen 2023 v 1.0.1 / email: **acutclub@gmail.com**\
 >For bugs, suggestions or any other info please contact me on my email.
