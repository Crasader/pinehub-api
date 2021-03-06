<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/10
 * Time: 18:44
 */

namespace App\Http\Controllers\MiniProgram;


use App\Ali\Payment\AliChargeContext;
use App\Entities\ActivityMerchandise;
use App\Entities\Card;
use App\Entities\CustomerTicketCard;
use App\Entities\MemberCard;
use App\Entities\Merchandise;
use App\Entities\MpUser;
use App\Entities\Order;
use App\Entities\OrderItem;
use App\Entities\Shop;
use App\Entities\ShopMerchandise;
use App\Entities\ShoppingCart;
use App\Exceptions\UnifyOrderException;
use Carbon\Carbon;
use Dingo\Api\Http\Request;
use App\Repositories\AppRepository;
use App\Http\Requests\MiniProgram\OrderCreateRequest;
use App\Repositories\OrderRepository;
use App\Repositories\CardRepository;
use App\Repositories\ShoppingCartRepository;
use App\Repositories\MerchandiseRepository;
use App\Repositories\OrderItemRepository;
use App\Repositories\MemberCardRepository;
use App\Repositories\CustomerTicketCardRepository;
use App\Transformers\Mp\OrderTransformer;
use App\Transformers\Mp\OrderStoreBuffetTransformer;
use App\Transformers\Mp\OrderStoreSendTransformer;
use App\Transformers\Mp\StatusOrdersTransformer;
use App\Transformers\Mp\StoreOrdersSummaryTransformer;
use App\Transformers\Mp\UsuallyStoreAddressTransformer;
use App\Repositories\ShopRepository;
use App\Http\Response\JsonResponse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Application;
use App\Exceptions\UserCodeException;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\MiniProgram\StoreOrdersSummaryRequest;
use App\Http\Requests\MiniProgram\StoreSendOrdersRequest;
use App\Http\Requests\MiniProgram\StoreBuffetOrdersRequest;
use Payment\Charge\Ali\AliWapCharge;

/**
 * @property CardRepository cardRepository
 */
class OrderController extends Controller
{
    /**
     * @var OrderRepository|null
     */
    protected $orderRepository = null;

    /**
     * @var null
     */
    protected $userTicketRepository = null;

    /**
     * @var ShoppingCartRepository|null
     */
    protected $shoppingCartRepository = null;

    /**
     * @var MerchandiseRepository|null
     */
    protected $merchandiseRepository = null;

    /**
     * @var ShopRepository|null
     */
    protected $shopRepository = null;

    /**
     * @var OrderItemRepository|null
     */
    protected $orderItemRepository = null;

    /**
     * @var Application|null
     */
    protected $app = null;

    /**
     * @var MemberCardRepository|null
     */
    protected $memberCardRepository = null;

    /**
     * @var CustomerTicketCardRepository|null
     */
    protected $customerTicketCardRepository = null;

    /**
     * OrderController constructor.
     * @param AppRepository $appRepository
     * @param CustomerTicketCardRepository $customerTicketCardRepository
     * @param MemberCardRepository $memberCardRepository
     * @param Application $app
     * @param OrderItemRepository $orderItemRepository
     * @param ShopRepository $shopRepository
     * @param MerchandiseRepository $merchandiseRepository
     * @param CardRepository $cardRepository
     * @param ShoppingCartRepository $shoppingCartRepository
     * @param OrderRepository $orderRepository
     * @param Request $request
     */
    public function __construct(AppRepository $appRepository,
                                CustomerTicketCardRepository $customerTicketCardRepository,
                                MemberCardRepository $memberCardRepository,
                                Application $app,
                                OrderItemRepository $orderItemRepository ,
                                ShopRepository $shopRepository,
                                MerchandiseRepository $merchandiseRepository ,
                                CardRepository $cardRepository,
                                ShoppingCartRepository $shoppingCartRepository,
                                OrderRepository $orderRepository ,
                                Request $request)
    {
        parent::__construct($request, $appRepository);

        $this->appRepository                = $appRepository;
        $this->orderRepository              = $orderRepository;
        $this->cardRepository               = $cardRepository;
        $this->shoppingCartRepository       = $shoppingCartRepository;
        $this->merchandiseRepository        = $merchandiseRepository;
        $this->shopRepository               = $shopRepository;
        $this->orderItemRepository          = $orderItemRepository;
        $this->app                          = $app;
        $this->memberCardRepository         = $memberCardRepository;
        $this->customerTicketCardRepository = $customerTicketCardRepository;
    }

    /**
     * 重新支付订单
     * @param string $type
     * @param int $orderId
     * @return mixed
     */

    public function payByOrderId(string $type = 'wx', int $orderId){
        $order = $this->orderRepository->findWhere(['id'=>$orderId])->first();
        return $this->order($order, $type);
    }

    protected function order(Order $order, string $type){
        return DB::transaction(function () use(&$order, $type){
            if($order->paymentAmount === 0) {
                $order->status = Order::PAID;
                $order->save();
                return $this->response()->item($order, new OrderTransformer());
            }
            //跟微信打交道生成预支付订单
            if ($type === 'wx') {
                $result = app('wechat')->unify($order, $order->wechatAppId, app('tymon.jwt.auth')->getToken());
                Log::info("------- unify result ------\n", $result);
                $order->prepayId = $result['prepay_id'];
                $order->payType = Order::WECHAT_PAY;
                $order->save();
                if($result['return_code'] === 'SUCCESS'){
                    $order->status = Order::MAKE_SURE;
                    $order->save();
                    $sdkConfig  = app('wechat')->jssdk($result['prepay_id'], $order->wechatAppId);
                    $result['sdk_config'] = $sdkConfig;
                    return $this->response(new JsonResponse($result));
                }else{
                    throw new UnifyOrderException($result['return_msg']);
                }
            } else {
                /** @var AliChargeContext $charge */
                $charge = app('mp.payment.ali.create');
                $order->payType = Order::ALI_PAY; 
                $order->save();
                $data = $order->buildAliAggregatePaymentOrder();
                $signed = $charge->charge($data);
                return $this->response(new JsonResponse($signed));
            }
        });
    }

    /**
     * 获取购物车
     * @param array $order
     * @param MpUser $user
     * @param string $type
     * @return Collection
     */
    protected function getShoppingCarts(array $order, MpUser $user, string $type = ShoppingCart::USER_ORDER)
    {
        $storeId = $type === ShoppingCart::MERCHANT_ORDER ? $order['receiving_shop_id'] :
            (isset($order['store_id']) ? $order['store_id'] : null);
        //有店铺id就是今日店铺下单的购物车,有活动商品id就是在活动商品里的购物车信息,两个都没有的话就是预定商城下单的购物车
        return $this->shoppingCartRepository->findWhere([
                'customer_id'               => $user->id,
                'activity_id'  => isset($order['activity_id']) ? $order['activity_id'] : null,
                'shop_id'                   => $storeId,
                'type' => $type
            ]);
    }

    protected function useTicket(array &$order, MpUser $user)
    {
        if(isset($order['card_id']) && $order['card_id'] ){
            $condition = [
                'card_id' => $order['card_id'],
                'status'  => CustomerTicketCard::STATUS_ON,
//                'active'  => CustomerTicketCard::ACTIVE_ON,
            ];
            if (isset($order['card_code']) && $order['card_code']) {
                $condition['card_code'] = $order['card_code'];
            }

            /** @var CustomerTicketCard $customerTicketRecord */
            $customerTicketRecord = $user->ticketRecords()->with('card')
                ->where($condition)
                ->orderByDesc('created_at')
                ->first();
            if ($customerTicketRecord){
                $card = $customerTicketRecord['card'];
                with($card, function (Card $card) use(&$order){
                    if ($card->cardType === Card::DISCOUNT) {
                        $order['discount_amount'] = $card->cardInfo['discount']/10 * $order['total_amount'];
                        Log::info('discount amount '.$order['discount_amount'].' (0)');
                    }else if($card->cardType === Card::CASH){
                        $order['discount_amount'] = $card && $card->cardInfo ? (float)$card->cardInfo['reduce_cost'] : 0;
                        Log::info('discount amount '.$order['discount_amount']."\n");
                    }
                });
                $order['card_id'] = $card['card_id'];
                $order['card_code'] = $customerTicketRecord->cardCode;
            }else{
                throw new ModelNotFoundException('使用的优惠券不存在');
            }
        }
        return $order;
    }

    /**
     * 创建自订单
     *
     * @param array $order
     * @param Collection $shoppingCarts
     */
    protected function buildOrderItemsFromShoppingCarts(array &$order, Collection $shoppingCarts)
    {
        $order['order_items'] = [];
        $order['shopping_cart_ids']  = [];
        //取出购物车商品信息组装成一个子订单数组
        $shoppingCarts->map(function (ShoppingCart $cart) use(&$order){
            $orderItem = $cart->only(['activity_id', 'shop_id', 'customer_id', 'merchandise_id', 'quality', 'sku_product_id']);
            $orderItem['total_amount'] = $cart->amount;
            $orderItem['payment_amount'] = $cart->amount;
            $orderItem['discount_amount'] = 0;
            $orderItem['status'] = Order::WAIT;
            if($cart->date) {
                $orderItem['send_date'] = $cart->date;
            }

            if($cart->batch) {
                $orderItem['send_batch'] = $cart->batch;
            }
            array_push($order['order_items'], $orderItem);
            array_push($order['shopping_cart_ids'], $cart->id);
        });
    }

    protected function setCustomerInfoForOrder(array &$order, MpUser $user)
    {
        $order['app_id'] = $user->appId;
        $order['member_id'] = $user->memberId;
        $order['wechat_app_id'] = $user->platformAppId;
        $order['customer_id'] = $user->id;
        $order['open_id']  = $user->platformOpenId;
        $order['app_id'] = $user->appId;
        $order['member_id'] = $user->memberId;
        $order['wechat_app_id'] = $user->platformAppId;
        $order['customer_id'] = $user->id;
        $order['open_id']  = $user->platformOpenId;
    }

    /**
     * 创建订单
     * @param OrderCreateRequest $request
     * @return \Dingo\Api\Http\Response
     */
    public function createOrder(OrderCreateRequest $request)
    {
        $user = $this->mpUser();
        $order = $request->all();
        $now = Carbon::now();
        if (isset($order['receiver_address']) && isset($order['build_num']) && isset($order['room_num'])){
            $address = [
                'receiver_address' => $order['receiver_address'],
                'build_num'        => $order['build_num'],
                'room_num'         => $order['room_num']
            ];
            $order['receiver_address'] = json_encode($address);
        }

        $this->setCustomerInfoForOrder($order, $user);
        $order['discount_amount'] = 0;
        $shop = null;

        if(isset($order['receiving_shop_id']) && $order['receiving_shop_id']) {
            if(!(new Shop)->find($order['receiving_shop_id'])) {
                throw new ModelNotFoundException('站点不存在');
            }
        }
        if(!$shop && isset($order['store_id']) && $order['store_id']) {
            if(!(new Shop)->find($order['store_id'])) {
                throw new ModelNotFoundException('下单店铺不存在');
            }
        }
        if (!isset($order['send_date']) || !$order['send_date']){
            $order['send_date'] = Carbon::now()->addDay(1)->format('Y-m-d');
        }

        if ((int)$order['type'] !== Order::OFF_LINE_PAYMENT_ORDER) {
            /** @var Collection $shoppingCarts */
            $shoppingCartType = $order['type'] === Order::SHOP_PURCHASE_ORDER ? ShoppingCart::MERCHANT_ORDER : ShoppingCart::USER_ORDER;
            $shoppingCarts = $this->getShoppingCarts($order, $user, $shoppingCartType);

            $order['total_amount']    = round($shoppingCarts->sum('amount'),2);
            $order['merchandise_num'] = $shoppingCarts->sum('quality');
            $this->buildOrderItemsFromShoppingCarts($order, $shoppingCarts);
        }


        $this->useTicket($order, $user);

        $order['shop_id'] = isset($order['store_id']) ? $order['store_id'] : null;



        $order['payment_amount']  = round(($order['total_amount'] - $order['discount_amount']),2);

        $order['year'] = $now->year;
        $order['month'] = $now->month;
        $order ['day']   = $now->day;
        $order['week']  = $now->dayOfWeekIso;
        $order['hour']  = $now->hour;
        Log::info("-------------------- order info ---------------------\n", $order);
        //生成提交中的订单
        $order = $this->app
            ->make('order.builder')
            ->setInput($order)
            ->handle();
        return $this->response()->item($order, new OrderTransformer());
    }

    /**
     * 自提订单
     * @param StoreBuffetOrdersRequest $request
     * @return \Dingo\Api\Http\Response
     */
    public function storeBuffetOrders(StoreBuffetOrdersRequest $request){
        $user = $this->shopManager();

        /** @var Shop $shop */
        $shop = $this->shopRepository
            ->findWhere(['user_id'  =>  $user->id])
            ->first();

        if ($shop){
            //查询今日下单和预定商城的所有自提订单
            $items = $this->orderRepository
                ->storeBuffetOrders($request->input('date', null), $shop->id);

            return $this->response()
                ->paginator($items,new OrderStoreBuffetTransformer());
        }else{
            throw new ModelNotFoundException('您不是店铺老板无权查询此接口');
        }
    }

    /**
     * 配送订单
     * @param StoreSendOrdersRequest $request
     * @return \Dingo\Api\Http\Response
     */
    public function storeSendOrders(StoreSendOrdersRequest $request)
    {
        $user = $this->mpUser();

        /** @var Shop $shop */
        $shop = $this->shopRepository
            ->findWhere(['user_id'   =>  $user['member_id']])
            ->first();

        if ($shop) {
           //查询今日下单和预定商城的所有配送订单
            $items = $this->orderRepository
                ->storeSendOrders($request->input('date', null),
                    $request->input('batch'), $shop->id);
            return $this->response()->paginator($items,new OrderStoreSendTransformer());
        }else{
            throw new ModelNotFoundException('您不是店铺老板无权查询此接口');
        }
    }

    /**
     * 所有订单信息
     * @param string $status
     *
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */

    public function userOrders(string  $status, Request $request){
        $customer   = $this->mpUser();

        $items = $this->orderRepository
            ->userOrders($status,   $customer->id, $request->input('limit', PAGE_LIMIT));
        return $this->response()
            ->paginator($items, new StatusOrdersTransformer());
    }

    /**
     * 销售订单汇总
     * @param StoreOrdersSummaryRequest $request
     * @return \Dingo\Api\Http\Response
     */
    public function storeOrdersSummary(StoreOrdersSummaryRequest $request){
        $user = $this->mpUser();


        /** @var Shop $shop */
        $shop = $this->shopRepository
            ->findWhere(['user_id'  =>  $user['member_id']])
            ->first();

        if ($shop) {
            $items = $this->orderRepository
                ->storeOrdersSummary(
                    $request->input('paid_date'),
                    $request->input('type'),
                    $request->input('status'),
                    $shop->id
                );
            return $this->response()
                ->paginator($items, new StoreOrdersSummaryTransformer());
        } else {
            throw new ModelNotFoundException('无权访问');
        }
    }

    public function storeOrders(int $storeId, Request $request) {

        $user = $this->shopManager();
        if($user) {
            $shop = $user->shops()->find($storeId);
            if($shop) {
                $order = new Order();
                $order = $order->with(['customer'])->where('shop_id', $shop->id);
                if (($type = $request->input('type', null))) {
                    $order = $order->where('type', $type);
                }
                if (($date = $request->input('paid_date', date('Y-m-d')))) {
                    if(is_string($date)) {
                        $date = [
                            $date.' 00:00:00',
                            $date.'23:59:59'
                        ];
                    }
                    $start = Carbon::createFromFormat('Y-m-d H:i:s', $date[0]);
                    $end = Carbon::createFromFormat('Y-m-d H:i:s', $date[1]);
                    $order = $order->where('paid_at', '>=', $start)
                        ->where('paid_at', '<', $end);
                }

                if (($payType = $request->input('pay_type', null))) {
                    $order = $order->where('pay_type', $payType);
                }
                $order = $order->whereIn('status', [Order::PAID, Order::SEND, Order::COMPLETED])->orderByDesc('paid_at');
                $orders = $order->paginate($request->input('limit', PAGE_LIMIT));
                $totalAmount = $order->sum('total_amount');
                $paymentAmount = $order->sum('payment_amount');
                return $this->response()->paginator($orders, new OrderTransformer())
                    ->addMeta('total_amount', number_format($totalAmount, 2))
                    ->addMeta('payment_amount', number_format($paymentAmount, 2));
            }else{
                throw new ModelNotFoundException('你不是店铺管理员无权访问');
            }
        }else{
            throw new ModelNotFoundException('未登录');
        }
    }

    /**
     * 取消订单
     * @param int $id
     * @return \Dingo\Api\Http\Response
     */
    public function cancelOrder(int $id){
        /** @var Order $order */
        $order = $this->orderRepository->with('orderItems')->find($id);

        if ($order->status === Order::WAIT || $order->status === Order::MAKE_SURE ){
            $order->status = Order::CANCEL;
            $order->save();
            return $this->response()->item($order, new StatusOrdersTransformer());
        }else{
            $errCode = '状态提交错误';
            throw new UserCodeException($errCode);
        }

    }

    /**
     * 确认订单
     * @param int $id
     * @return mixed
     */
    public function confirmOrder(int $id){
        /** @var Order $order */
        $order = $this->orderRepository->find($id);
        if ($order->status === Order::PAID || $order->status === Order::SEND){
            $order->status = Order::COMPLETED;
            $order->save();
            return $this->response()->item($order, new StatusOrdersTransformer());
        }else{
            $errCode = '状态提交错误';
            throw new UserCodeException($errCode);
        }

    }
}
