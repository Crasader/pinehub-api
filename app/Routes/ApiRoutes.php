<?php
/**
 * Created by PhpStorm.
 * User: wangzaron
 * Date: 2018/5/17
 * Time: 下午3:58
 */

namespace App\Routes;


use App\Http\Middleware\Cross;
use Dingo\Api\Routing\Router;
use Dingo\Api\Http\Request;
use Laravel\Lumen\Application;

class ApiRoutes extends Routes
{
    const VERSIONS = [
        'V1' => 'v1'
    ];
    public function __construct(Application $app, $version = null, $namespace = null, $prefix = null, $domain = null, string $auth = null)
    {
        parent::__construct($app, $version, $namespace, $prefix, $domain, $auth);
        $this->auth = $this->auth ? $this->auth : 'api';
        config(['auth.defaults.guard' => $this->auth]);
        config(['api.domain' => $domain]);
        $this->router = $this->app->make('api.router');
        $this->app->middleware(Cross::class);
    }

    protected function routesRegister($version = null)
    {
        $second = [];
        if($this->prefix){
            $second['prefix'] = $this->prefix;
        }

        if($this->domain){
            $second['domain'] = $this->domain;
        }
        $this->version = $version ? $version : $this->version;

        $second['middleware'] = ['cross'];

        $this->router->version($this->version, $second, function (Router $router){
            $self = $this;

            $router->any('/', function (Request $request) use ($self){
                return 'web api version '.$self->version.', host domain '.$request->getHost();
            });

            $router->get('/version', function (Request $request) use ($self){
                return 'web api version '.$self->version.', host domain '.$request->getHost();
            });

            $namespace = $this->namespace;
            if($this->namespace){
                $namespace = $this->namespace.($this->subNamespace ? $this->subNamespace : '');
            }

            $router->group(['namespace' => $namespace], function () use($router){
                $this->subRoutes($router);
            });

            $namespace = $this->namespace;
            $router->group(['namespace' => $namespace], function () use($router) {
                $this->routes($router);
            });
        });
    }

    /**
     * @param DingoRouter|LumenRouter $router
     * */
    protected function routes($router)
    {

    }

    protected function boot()
    {

    }
}