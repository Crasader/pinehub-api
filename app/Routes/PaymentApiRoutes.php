<?php
/**
 * Created by PhpStorm.
 * User: wang
 * Date: 2018/4/28
 * Time: 上午10:11
 */

namespace App\Routes;
use Dingo\Api\Routing\Router as DingoRouter;
use Laravel\Lumen\Routing\Router as LumenRouter;

class PaymentApiRoutes extends ApiRoutes
{
    /**
     * @param LumenRouter|DingoRouter $router
     * */
    protected function subRoutes($router)
    {
        parent::subRoutes($router); // TODO: Change the autogenerated stub
        //$router->get('/aggregate/payment', ['as' => 'aggregate.payment', 'uses' => 'PaymentController@aggregate']);
        $router->post('/ali/aggregate', ['as' => 'aggregate.ali.payment.post', 'uses' => 'AliPaymentController@aggregate']);
        $router->post('/wechat/aggregate', ['as' => 'aggregate.wechat.payment.post', 'uses' => 'WechatPaymentController@aggregate']);
    }
}