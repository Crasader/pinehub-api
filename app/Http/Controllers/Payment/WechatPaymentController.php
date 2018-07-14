<?php

namespace App\Http\Controllers\Payment;

use App\Entities\Order;
use App\Http\Controllers\Payment\PaymentController as Controller;
use Dingo\Api\Http\Request as DingoRequest;
use Illuminate\Http\Request as LumenRequest;
use Dingo\Api\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Payment\NotifyContext;
use App\Transformers\Api\PaymentSignedTransformer as WechatPaymentSigned;
use App\Entities\PaymentSigned as WechatPayment;

class WechatPaymentController extends Controller
{
    /**
     * 聚合支付
     * @param LumenRequest|DingoRequest $request
     * @return Response| null
     * @throws
     * */
    public function aggregate(LumenRequest $request)
    {
        $request->merge(['pay_type' => Order::WECHAT_PAY, 'type' => Order::OFF_LINE_PAY]);
        $order = $this->app->make('order.builder')->handle();
        Log::debug('header', $request->headers->all());
        exit();
        //$order->openId = $openId;
        $charge = app('wechat.payment.aggregate');
        return $this->response()->item( new WechatPayment($this->preOrder($order->buildWechatAggregatePaymentOrder(), $charge)),
            new WechatPaymentSigned());
    }

    public function aggregatePage(LumenRequest $request)
    {
        $openId= null;
        $paymentApi = paymentApiUriGenerator('/wechat/aggregate');
        $accept = "application/vnd.pinehub.v0.0.1+json";
        $config = app('wechat')->officeAccount()->jssdk->buildConfig(['chooseWXPay']);
        $openId = $request->input('open_id', null);
        $token = md5($openId);
        Cache::set($token, $openId);
        try{
            $shop = $this->shopModel->find($request->input('shop_id'));
            return view('payment.aggregate.wechatpay')->with([
                'type' => Order::WECHAT_PAY,
                'token' => $token,
                'shop' => $shop,
                'paymentApi' => $paymentApi,
                'config' => $config,
                'accept' => $accept,
                'app_id' => $request->input('selected_appid', null)
            ]);
        }catch (\Exception $exception) {
            return view('payment.aggregate.wechatpay')->with([
                'type' => Order::WECHAT_PAY,
                'token' => $token,
                'paymentApi' => $paymentApi,
                'config' => $config,
                'accept' => $accept,
                'app_id' => $request->input('selected_appid', null)
            ]);
        }
    }


    public function notify(string $type = 'wechat', NotifyContext $notify = null)
    {
        $notify = $this->app->make('payment.wechat.notify');
        parent::notify($type, $notify); // TODO: Change the autogenerated stub
    }
}
