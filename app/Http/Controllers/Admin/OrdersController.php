<?php

namespace App\Http\Controllers\Admin;

use App\Criteria\Admin\OrderCriteria;
use App\Criteria\Admin\OrderSearchCriteria;
use App\Criteria\Admin\SearchRequestCriteria;
use App\Entities\Order;
use App\Http\Requests\OrderSendRequest;
use App\Http\Response\JsonResponse;

use Dingo\Api\Http\Request;
use Exception;
use App\Http\Requests\Admin\OrderCreateRequest;
use App\Http\Requests\Admin\OrderUpdateRequest;
use App\Transformers\OrderTransformer;
use App\Transformers\OrderItemTransformer;
use App\Repositories\OrderRepository;
use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;


class OrdersController extends Controller
{
    /**
     * @var OrderRepository
     */
    protected $repository;


    /**
     * OrdersController constructor.
     *
     * @param OrderRepository $repository
     */
    public function __construct(OrderRepository $repository)
    {
        $this->repository = $repository;

        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
       // $this->repository->pushCriteria(OrderSearchCriteria::class);
        $this->repository->pushCriteria(OrderCriteria::class);
        $this->repository->pushCriteria(SearchRequestCriteria::class);
        $orders = $this->repository
            ->with(['orderItems.merchandise', 'orderItems.shop', 'customer', 'member', 'activity'])
            ->paginate($request->input('limit', PAGE_LIMIT));
        return $this->response()->paginator($orders, new OrderItemTransformer());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  OrderCreateRequest $request
     *
     * @return \Illuminate\Http\Response
     *
     * @throws Exception
     */
    public function store(OrderCreateRequest $request)
    {
        $order = $this->repository->create($request->all());

        $response = [
            'message' => 'Order created.',
            'data'    => $order->toArray(),
        ];

        if ($request->wantsJson()) {

            return $this->response()->item($order, new OrderTransformer());
        }

        return redirect()->back()->with('message', $response['message']);
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $order = $this->repository->with(['orderItems.merchandise', 'orderItems.shop', 'customer', 'member'])->find($id);
        return $this->response()->item($order, new OrderTransformer());
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $order = $this->repository->find($id);

        return view('orders.edit', compact('order'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  OrderUpdateRequest $request
     * @param  string            $id
     *
     * @return \Illuminate\Http\Response
     *
     * @throws Exception
     */
    public function update(OrderUpdateRequest $request, $id)
    {
       $order = $this->repository->update($request->all(), $id);

       $response = [
           'message' => 'Order updated.',
           'data'    => $order->toArray(),
       ];

       if ($request->wantsJson()) {

           return $this->response()->item($order, new OrderTransformer());
       }

       return redirect()->back()->with('message', $response['message']);
    }

    public function orderSent(OrderSendRequest $request, int $id)
    {
        $order = $this->repository->with(['orderItems'])->find($id);
        DB::transaction(function () use($order, $request) {
            tap($order, function (Order $order) use($request){
                $order->status = Order::SEND;
                $order->postType = $request->input('post_type', null);
                $order->postNo = $request->input('post_no', null);
                $order->postName = $request->input('post_name', null);
                $order->consignedAt = Carbon::now();
                $order->orderItems()->update(['status' => Order::SEND, 'consigned_at' => $order->consignedAt]);
                $order->save();
            });
        });

        return $this->response()->item($order, new OrderTransformer());

    }


    public function refund(int $id)
    {
        $order = $this->repository->with(['orderItems'])->find($id);
        if($order) {

        }
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $deleted = $this->repository->delete($id);
        return $this->response(new JsonResponse(['delete_count' => $deleted]));
    }
}
