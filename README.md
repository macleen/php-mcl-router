# MCL-ROUTER
>Lightweight PHP Router package (Version 1.0 patch 1)


## Introduction

**Mcl-Router** is a small sized PHP library intended for use/integration in your application





## Usage Notes

   -  **IMPORTANT**\


   In your root composer.json file please insert these 2 flags:

```json
   {
      "here are the various flags of the":"composer.json file",
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



  -  **MclRouter / file: Router.php**\
     This is the main package class. it takes care of the registration of predefined route groups sitting is seperate files (web.php, api.php, admin.php...etc...) using the RouteServiceProvider. it takes 2 parameters the the roue paths: string|array $route_definition_paths: paths to the route definition files in the form of a string '/path/subpath1/subpath2/web.php' if you have only one route file or in the form of ['/path/subpath1/subpath2/web.php','/path/subpath1/subpath2/api.php'] if you have more, and an array of controller name spaces ( where your controllers are defined ).


```php
    use MclRouter\Router;
    $router = new Router( string|array $route_definition_files, ?array $controllers_name_spaces = [ ]);
```



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



## Route Parameters

   -  **named routes**

   You can name your routes for later retrieval by name, just chain the name function to the Route::{method} like so:


```php

Route::get('/home', 'HomeController@index')->name('home');
Route::post('/data/save', 'DataController@save')->name('save');

```


   -  **Variable parameters**

   Variable parameters are enclosed between "{" and "}" like so:


```php

Route::get('/user/{id}', 'UserController@get')->name('home');
Route::post('/save/{release_id}', 'DataController@save')->name('save');

```

   -  **Optional parameters**

   Optional parameters are enclosed between "{" and "?}" with a question mark appended at the end of the parameter name and before the closing "}":


```php

Route::get('/user/{id?}', 'UserController@get')->name('get_user_by_id');
Route::post('/save/{release_id?}', 'DataController@save')->name('save_release');

```


   -  **RegEx with required parameters**

   Usage of regex validation rules can be appended to the static Route::{method}by chaining the where function


```php

Route::get('/user/{id}', 'UserController@get')
     ->name('get_user_by_id')
     ->where(['id' => [a-zA-Z]{2}[\d]{6}]);

   // id must start with 2 alpha characters followed by 6 digits
```



   -  **RegEx with opional parameters**

   Usage of regex validation rules with optional parameters is allowed. Which means the route is processed if the parameter is omitted but is it is supplied it must match the regex expression:


```php

Route::get('/user/{id?}', 'UserController@get')
     ->name('get_user_by_id')
     ->where(['id' => [a-zA-Z]{2}[\d]{6}]);

   // id is optional but if supplied it must start with 2 alpha characters followed by 6 digits
```


## Controller function call

   Each function supplied with a certain route will be called in case of a route match with 2 parameters injected with the call:


```php

use MclRouter\Requests\AdaptedRequest;
use MclRouter\RouteParams;


Route::get('/user/{id?}', 'UserController@get');
Route::post('/{id}', 'UserController@save');

class UserController {

   function get( RouteParams $parms ){

      $id = $parms->id;
      // some logic here
      return $something;

   }

   function save( RouteParams $parms, AdaptedRequest $req ){

      $id = $parms->id;
      $data = $req->all( ); // this is the post data
      // some logic here
      return $something;

   }

}


// OR USING A CALLABLE INSTEAD OF A CONTROLLER


Route::get('/user/{id?}', function( RouteParams $parms, AdaptedRequest $req ){
   // some logic here
   // response can be an instance of Symphony reqponse or 
   // string
   // array : ( will be json serialized )
   return (mixed) $response;
});

```



## IMPORTANT Usage Notes

   -  **Post requests to the base url**\

   This is a PHP/Http issue. Suppose your domain is hosted at https://pizza.com/ingredients/classes ( the base url ), if you invoke a POST request ( outside of a form submission) against that url be sure to close it with a slah like so https://pizza.com/ingredients/classes/. This applies only for the base url.

```php
//And the route defnition must also respect that rule

Route::post('/ngredients/classes/', 'UploadController@upload_files');

```






## Kick off


   -  **Main index file**
       this is an example of the index.php file. Assuming your main namespace is app and a viewer class is present to display the final response

    
```php

    require __DIR__ . '/vendor/autoload.php';
    #------------------------------------------------------------------------
    $routes = [ __DIR__ . '/routes/api.php', __DIR__ . '/routes/web.php' ];

    (new App\Render\Viewer( ))
       ->render(( new MclRouter\Router( $routes, [ '\\App\\Controllers\\']))
       ->dispatch( ));

```



## Installation

   composer require macleen/mcl-router


---------------------------------------------------------
 >Author: C. Mahmoud / MacLeen 2023 v 1.0.1 / email: **acutclub@gmail.com**\
 >For bugs, suggestions or any other info please contact me on my email.
