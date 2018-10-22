<?php
/**
 * Created by PhpStorm.
 * User: wang
 * Date: 2018/4/13
 * Time: 下午12:40
 */
namespace App\Routes;
use Dingo\Api\Routing\Router as DingoRouter;
use Dingo\Api\Routing\Router;
use Laravel\Lumen\Routing\Router as LumenRouter;
class BackendApiRoutes extends ApiRoutes
{

    protected function routes($router)
    {
        parent::routes($router); // TODO: Change the autogenerated stub
        tap($router, function (Router $router) {
            $router->get('/open-platform/auth/sure', ['as' => 'open-platform.auth.sure', 'uses' => 'Wechat\OpenPlatformController@openPlatformAuthMakeSure']);
        });
    }

    /**
     * @param DingoRouter|LumenRouter $router
     * */
    protected function subRoutes($router)
    {
        tap($router, function (Router $router) {
            parent::subRoutes($router); // TODO: Change the autogenerated stub
            $attributes = [];

            if($this->app->environment() !== 'local') {
                $attributes['middleware'] = ['api.auth'];
            }

            $router->group([], function($router) {
              /**
               * @var  LumenRouter|DingoRouter $router
               * */
              $router->post('/register', ['as' => 'administrator.register', 'uses' => 'AuthController@register']);
              $router->post('/login', ['as' => 'administrator.login', 'uses' => 'AuthController@authenticate']);
            });

            $router->group($attributes, function ($router) {
                $router->get('/logout', ['as' => 'administrator.logout', 'uses' => 'AuthController@logout']);
                $router->get('/users',['as' => 'users.list', 'uses' => 'UsersController@getUsers']);
                $router->get('/user/{id}',['as' => 'user.detail', 'uses' => 'UsersController@getUserDetail']);

                //登录用户信息路由
                $router->get('/self/info', ['as' => 'self.info', 'uses' => 'MySelfController@selfInfo']);
                $router->put('/change/password', ['as' => 'change.password', 'uses' => 'MySelfController@changePassword']);

                $router->post('/shop', ['as' => 'shop.create', 'uses' => 'ShopsController@store']);
                $router->get('/shops', ['as' => 'shop.list', 'uses' => 'ShopsController@index']);
                $router->get('/shop/{id}', ['as' => 'shop.detail', 'uses' => 'ShopsController@show']);
                $router->put('/shop/{id}', ['as' => 'shop.update', 'uses' => 'ShopsController@update']);

                $router->get('/orders', ['as' => 'orders', 'uses' => 'OrdersController@index']);
                $router->put('/order/{id}/sent', ['as' =>'order.sent', 'uses' => 'OrdersController@orderSent']);
                $router->get('/order/{id}', ['as' => 'order.show', 'uses' => 'OrdersController@show']);

                $router->post('/app/logo/{driver?}', ['as' => 'app.logo.upload', 'uses' => 'AppController@uploadLogo']);
                $router->get('/apps', ['as' => 'app.list', 'uses' => 'AppController@index']);
                $router->post('/app', ['as' => 'app.create', 'uses' => 'AppController@store']);
                $router->put('/app/{id}', ['as' => 'app.update', 'uses' => 'AppController@update']);
                $router->get('/app/{id}', ['as' => 'app.show', 'uses' => 'AppController@show']);
                $router->delete('/app/{id}', ['as' => 'app.delete', 'uses' => 'AppController@destroy']);

                $router->get('/customers', ['as'=> 'customers', 'uses' => 'CustomersController@index']);

                $router->get('/members', ['as'=> 'members', 'uses' => 'MembersController@index']);

                $router->get('/member/cards',['as' => 'member.cards', 'uses' => 'MemberCardsController@index']);
                $router->post('/member/card', ['as' => 'member.card.create', 'uses' => 'MemberCardsController@store']);
                $router->get('/member/card/{id}', ['as' => 'member.card.show', 'uses' => 'MemberCardsController@show']);
                $router->put('/member/card/{id}', ['as' => 'member.card.update', 'uses' => 'MemberCardsController@update']);
                $router->delete('/member/card/{id}', ['as' => 'member.card.delete', 'uses' => 'MemberCardsController@destroy']);

                $router->post('/groupon/ticket', ['as' => 'groupon-ticket.create', 'middleware' => ['ticket:groupon'],  'uses' => 'CardsController@store']);
                $router->post('/discount/ticket', ['as' => 'discount-ticket.create', 'middleware' => ['ticket:discount'], 'uses' => 'CardsController@store']);
                $router->post('/coupon/ticket', ['as' => 'coupon-ticket.create', 'middleware' => ['ticket:coupon_card'],  'uses' => 'CardsController@store']);
                $router->post('/gift/ticket', ['as' => 'gift-ticket.create', 'middleware' => ['ticket:gift'],  'uses' => 'CardsController@store']);

                $router->get('/tickets', ['as' => 'tickets', 'middleware' => ['ticket'], 'uses' => 'CardsController@index']);
                $router->get('/ticket/{id}', ['as' => 'ticket.show', 'uses' => 'CardsController@show']);

                $router->put('/ticket/{id}', ['as' => 'ticket.update', 'uses' => 'CardsController@update']);

                $router->get('/score-rules', ['as' => 'score-rules', 'uses' => 'ScoreRulesController@index']);
                $router->get('/{type}/score-rules', ['as' => 'score-rules', 'uses' => 'ScoreRulesController@index']);
                $router->get('/score-rule/{id}', ['as' => 'score-rules', 'uses' => 'ScoreRulesController@show']);
                $router->post('/score-rule', ['as' => 'score-rule.create', 'uses' => 'ScoreRulesController@store']);
                $router->put('/score-rule/{id}', ['as' => 'score-rule.update', 'uses' => 'ScoreRulesController@store']);

                $router->group(["prefix" => "wechat", "namespace" => "Wechat"], function ($router) {
                    /**
                     * @var LumenRouter|DingoRouter $router
                     * */
                    $router->post("config", ['as' => 'wechat.config.create', 'uses' => 'ConfigController@store']);
                    $router->get("configs", ['as' => 'wechat.config.list', 'uses' => 'ConfigController@index']);
                    $router->get("config/{id}", ['as' => 'wechat.config.show', 'uses' => 'ConfigController@show']);
                    $router->put("config/{id}", ['as' => 'wechat.config.update', 'uses' => 'ConfigController@update']);
                    $router->delete("configs", ['as' => 'wechat.config.delete.bat', 'uses' => 'ConfigController@destroy']);
                    $router->delete("config/{id}", ['as' => 'wechat.config.delete', 'uses' => 'ConfigController@destroy']);

                    //menus
                    $router->post("menu", ['as' => 'wechat.menu.create', 'uses' => 'MenuController@store']);
                    $router->get("menus", ['as' => 'wechat.menu.list', 'uses' => 'MenuController@index']);
                    $router->get("{appId}/menus", ['as' => 'wechat.app.menus', 'uses' => 'MenuController@index']);
                    $router->get("menu/{id}", ['as' => 'wechat.menu.show', 'uses' => 'MenuController@show']);
                    $router->put("menu/{id}", ['as' => 'wechat.menu.update', 'uses' => 'MenuController@update']);
                    $router->delete("menu/{id}", ['as' => 'wechat.menu.delete', 'uses' => 'MenuController@destroy']);
                    $router->delete("menus", ['as' => 'wechat.menu.delete.bat', 'uses' => 'MenuController@destroy']);
                    $router->get("menu/{id}/sync", ['as' => 'wechat.menu.sync', 'uses' => 'MenuController@sync']);

                    //material api

                    $router->post("media/temporary", ['as' => 'wechat.temporary.media.create', 'uses' => 'MaterialController@storeTemporaryMedia']);
                    $router->post("material/article", ['as' => 'wechat.article.create', 'uses' => 'MaterialController@storeForeverNews']);
                    $router->post("{type}/material", ['as' => 'wechat.material.create', 'uses' => 'MaterialController@uploadForeverMaterial']);
                    $router->get("material/stats", ['as' => 'wechat.material.stats', 'uses' => 'MaterialController@materialStats']);
                    $router->get("materials", ['as' => 'wechat.materials', 'uses' => 'MaterialController@materialList']);
                    $router->get("material", ['as' => 'wechat.material.view', 'uses' => 'MaterialController@materialView']);
                    $router->get("material/{mediaId}", ['as' => 'wechat.material.forever.detail', 'uses' => 'MaterialController@material']);
                    $router->get("material/{mediaId}/{type}", ['as' => 'wechat.material.temporary.detail', 'uses' => 'MaterialController@material']);
                    $router->put("material/article/{mediaId}", ['as' => 'wechat.article.update', 'uses' => 'MaterialController@materialNewsUpdate']);
                    $router->delete("material/{mediaId}", ['as' => 'wechat.material.delete', 'uses' => 'MenuController@deleteMaterial']);

                    //auto reply message
                    $router->post("auto_reply_message", ['as' => 'wechat.auto_reply_message.create', 'uses' => 'AutoReplyMessagesController@store']);
                    $router->get("auto_reply_messages", ['as' => 'wechat.auto_reply_message.list', 'uses' => 'AutoReplyMessagesController@index']);
                    $router->get("auto_reply_message/{id}", ['as' => 'wechat.auto_reply_message.show', 'uses' => 'AutoReplyMessagesController@show']);
                    $router->put("auto_reply_message/{id}", ['as' => 'wechat.auto_reply_message.update', 'uses' => 'AutoReplyMessagesController@update']);
                    $router->delete("auto_reply_message/{id}", ['as' => 'wechat.auto_reply_message.delete', 'uses' => 'AutoReplyMessagesController@destroy']);
                    $router->delete("auto_reply_messages", ['as' => 'wechat.auto_reply_message.delete.bat', 'uses' => 'AutoReplyMessagesController@destroy']);
                });

                $router->get('categories', ['as' => 'categories.list', 'uses' => 'CategoriesController@index']);
                $router->post('category', ['as' => 'category.create', 'uses' => 'CategoriesController@store']);

                $router->get('merchandises', ['as' => 'merchandises.list', 'uses' => 'MerchandisesController@index']);
                $router->post('merchandise', ['as' => 'merchandise.create', 'uses' => 'MerchandisesController@store']);
                $router->put('merchandise/{id}', ['as' => 'merchandise.update', 'uses' => 'MerchandisesController@update']);
                $router->get('merchandise/{id}', ['as' => 'merchandise.show', 'uses' => 'MerchandisesController@show']);
                $router->post('merchandise/image/{driver?}', ['as' => 'merchandise.image.upload', 'uses' => 'MerchandisesController@uploadMerchandiseImage']);

                $router->post('order-gift', ['as' => 'order-gift.create', 'uses' => 'OrderGiftsController@store']);
                $router->put('order-gift/{id}', ['as' => 'order-gift.update', 'uses' => 'OrderGiftsController@update']);
                $router->get('order-gifts/{type}', ['as' => 'order-gift.list', 'uses' => 'OrderGiftsController@index']);
                $router->get('order-gift/{id}', ['as' => 'order-gift.show', 'uses' => 'OrderGiftsController@show']);
            });


            $router->get('/countries', ['as' => 'country.list', 'uses' => 'CountriesController@index']);
            $router->get('/country/{id}', ['as' => 'country.detail', 'uses' => 'CountriesController@show']);
            $router->post('/country', ['as' => 'country.create', 'uses' => 'CountriesController@store']);
            $router->put('/country/{id}', ['as' => 'country.update', 'uses' => 'CountriesController@update']);
            $router->delete('/country/{id}', ['as' =>'country.delete', 'uses' => 'CountriesController@destory']);

            $router->get('/country/{countryId}/provinces', ['as' => 'province.list.country', 'uses' => 'ProvincesController@index']);
            $router->get('/provinces', ['as' => 'province.list', 'uses' => 'ProvincesController@index']);
            $router->get('/province/{id}', ['as' => 'province.detail', 'uses' => 'ProvincesController@show']);
            $router->post('/country/{countryId}/province', ['as' => 'province.create.country', 'uses' => 'ProvincesController@store']);
            $router->post('/province', ['as' => 'province.create', 'uses' => 'ProvincesController@store']);
            $router->put('/province/{id}', ['as' => 'province.update', 'uses' => 'ProvincesController@update']);

            //$router->get('/country/{countryId}/cities', ['as' => 'city.list.country', 'uses' => 'CitiesController@index']);
            $router->get('/country/{id}/cities', ['as' => 'city.list.country', 'uses' => 'CitiesController@index']);
            $router->get('/province/{id}/cities', ['as' => 'city.list.province', 'uses' => 'CitiesController@index']);
            $router->get('/cities', ['as' => 'city.list.all', 'uses' => 'CitiesController@index']);
            $router->get('/city/{id}', ['as' => 'city.detail', 'uses' => 'CitiesController@show']);
            $router->post('/city', ['as' => 'city.create', 'uses' => 'CitiesController@store']);
            $router->post('/province/{provinceId}/cities', ['as' => 'city.create.province', 'uses' => 'CitiesController@store']);
            $router->put('/city/{id}', ['as' => 'city.update', 'uses' => 'CitiesController@update']);

            $router->get('/country/{id}/counties', ['as' => 'county.list.country', 'uses' => 'CountiesController@index']);
            $router->get('/province/{id}/counties', ['as' => 'county.list.province', 'uses' => 'CountiesController@index']);
            $router->get('/city/{id}/counties', ['as' => 'county.list.city', 'uses' => 'CountiesController@index']);
            $router->get('/counties', ['as' => 'county.list', 'uses' => 'CountiesController@index']);
            $router->get('/county/{id}', ['as' => 'county.show', 'uses' => 'CountiesController@show']);
            $router->post('/city/{cityId}/county', ['as' => 'county.create.city', 'uses' => 'CountiesController@store']);
            $router->post('/county', ['as' => 'county.create', 'uses' => 'CountiesController@store']);
            $router->put('/county/{id}', ['as' => 'county.update', 'uses' => 'CountiesController@update']);

        });
    }
}
