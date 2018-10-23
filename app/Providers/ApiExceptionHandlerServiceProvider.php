<?php

namespace App\Providers;

use App\Exceptions\ApiHttpExceptionHandler;
use App\Exceptions\HttpValidationException;
use App\Exceptions\TokenOverDateException;
use Dingo\Api\Exception\ValidationHttpException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;


class ApiExceptionHandlerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
        if($this->app->has('api.exception')){
            $this->app->make('api.exception')->register(function (\Exception $exception) {
                $responseSender = new Response();
                $responseSender->header('Access-Control-Allow-Origin', '*')
                    ->header('Access-Control-Allow-Headers', 'Origin, Content-Type, Cookie, Accept')
                    ->header('Access-Control-Allow-Methods', 'GET, POST, PATCH, PUT, DELETE, OPTIONS')
                    ->header('Access-Control-Allow-Credentials', 'true');
                if(Request::method() === HTTP_METHOD_OPTIONS) {
                    return $responseSender->send();
                }
                dump($exception);
                if($exception instanceof TokenExpiredException) {
                    $exception = new TokenOverDateException('token已过期，请刷新token或者重新登陆', AUTH_TOKEN_EXPIRES);
                }elseif ($exception instanceof ValidationHttpException || $exception instanceof ValidationException) {
                    if($exception instanceof ValidationHttpException)
                        $exception = new HttpValidationException($exception->getErrors()->toArray(), HTTP_REQUEST_VALIDATE_ERROR);
                    else
                        $exception = new HttpValidationException($exception->errors(), HTTP_REQUEST_VALIDATE_ERROR);
                }
                return $this->app->make('api.http.handler')->handle($exception);
            });
        }
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
        $this->app->singleton('api.http.handler', function ($app) {
            return new ApiHttpExceptionHandler($app['Illuminate\Contracts\Debug\ExceptionHandler'], config('api.errorFormat'), config('api.debug'));
        });
    }
}
