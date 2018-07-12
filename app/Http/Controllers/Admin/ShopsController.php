<?php

namespace App\Http\Controllers\Admin;

use App\Entities\App;
use App\Entities\Role;
use App\Entities\ShopManager;
use App\Http\Response\JsonResponse;

use App\Repositories\SellerRepository;
use App\Repositories\ShopManagerRepository;
use App\Services\AppManager;
use App\Utils\GeoHash;
use Dingo\Api\Http\Request;
use Illuminate\Http\Request as IlluminateRequest;
use Exception;
use App\Http\Requests\Admin\ShopCreateRequest;
use App\Http\Requests\Admin\ShopUpdateRequest;
use App\Transformers\ShopTransformer;
use App\Transformers\ShopItemTransformer;
use App\Repositories\ShopRepository;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Http\Response;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Http\Controllers\Controller;

/**
 * Class ShopsController.
 *
 * @package namespace App\Http\Controllers\Admin;
 */
class ShopsController extends Controller
{
    /**
     * @var ShopRepository
     */
    protected $repository;

    protected $sellerRepository;

    protected $shopManagerRepository;


    /**
     * ShopsController constructor.
     *
     * @param ShopRepository $repository
     * @param SellerRepository $sellerRepository
     * @param ShopManagerRepository $shopManagerRepository
     */
    public function __construct(ShopRepository $repository, SellerRepository $sellerRepository, ShopManagerRepository $shopManagerRepository)
    {
        $this->repository = $repository;
        $this->sellerRepository = $sellerRepository;
        $this->shopManagerRepository = $shopManagerRepository;
        parent::__construct();

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $shops = $this->repository->paginate();

        if (request()->wantsJson()) {

            return $this->response()->paginator($shops, new ShopItemTransformer());
        }

        return view('shops.index', compact('shops'));
    }

    /**
     * 获取店铺老板/经理人的用户信息
     * @param string $mobile
     * @param string $name
     * @return ShopManager
     * @throws
     * */
    protected function getManager(string $mobile, string $name)
    {
        $shopManager = $this->shopManagerRepository->findWhere(['mobile' => $mobile])->first();
        if(!$shopManager){
            $shopManager = $this->shopManagerRepository->create([
                'user_name' => $mobile,
                'password' => password($mobile),
                'real_name' => $name,
                'mobile' => $mobile
            ]);
            $seller = $this->sellerRepository->find($shopManager->id);
            $role = Role::whereSlug(Role::SELLER)->first(['id']);
            $seller->roles()->attach($role->id);
        }
        return $shopManager;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  ShopCreateRequest $request
     *
     * @return \Illuminate\Http\Response
     *
     * @throws Exception
     */
    public function store(ShopCreateRequest $request)
    {
        $data = $request->only(['name', 'country_id', 'province_id', 'city_id', 'county_id', 'address', 'description', 'status']);
        $appManager = app(AppManager::class);
        $data['app_id'] = $appManager->currentApp->id;
        $data['user_id'] = $this->getManager($request->input('manager_mobile'), $request->input('manager_name'))->id;
        if($request->input('lat', null) && $request->input('lng', null)){
            $data['position'] = new Point($request->input('lat'), $request->input('lng'));
            $data['geo_hash'] = (new GeoHash())->encode($request->input('lat'), $request->input('lng'));
        }
        $data['wechat_app_id'] = $appManager->officialAccount->appId;
        $data['ali_app_id'] = $appManager->aliPayOpenPlatform->config['app_id'];

        $shop = $this->repository->create($data);
        $response = [
            'message' => 'Shop created.',
            'data'    => $shop->toArray(),
        ];

        if ($request->wantsJson()) {

            return $this->response()->item($shop, new ShopTransformer());
        }

        return redirect()->back()->with('message', $response['message']);
    }


    public function paymentQRCode( int $id, IlluminateRequest $request)
    {
        $shop = $this->repository->find($id);
        $size = $request->input('size', 200);
        if($shop  && $size !== null && $size > 0) {
           $url  = webUriGenerator('/aggregate.html', env('WEB_PAYMENT_PREFIX'), env('WEB_DOMAIN'));
           $url .= "?shop_id={$shop->id}";
           $url .= "&selected_appid={$shop->appId}";
           $qrCode = QrCode::format('png')->size($size)->generate($url);
           if($request->wantsJson()) {
                $qrCode = base64_encode($qrCode);
                return $this->response(new JsonResponse([
                    'qr_code' => 'data:image/png;base64, '.$qrCode
                ]));
            }else{
                return Response::create($qrCode)->header('Content-Type', 'image/png');
            }
        }else{
            if($request->wantsJson()) {
                return $this->response(new JsonResponse([
                    'message' => '失败 '
                ]));
            }else{
                return Response::create(['message' => '失败 ']);
            }
        }
    }

    public function officialAccountQRCode(int $id, IlluminateRequest $request)
    {
        $shop = $this->repository->find($id);
        if($shop) {
            $url = $shop->wechatParamsQrcodeUrl;
            if(!$url) {
                $data = [
                    'app_id' => $shop->appId,
                    'shop_id' => $shop->id
                ];
                $currentApp = App::find($shop->appId);
                $result = app('wechat')->openPlatform()->officialAccount($currentApp->wechatAppId, $currentApp->officialAccount->authorizerRefreshToken)
                    ->qrcode->forever(base64_encode(json_encode($data)));
                if($result['errcode'] !== 0) {
                    throw new Exception('无法生成参数二维码');
                }
                $url = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket={$result['ticket']}";
                $shop->wechatParamsQrcodeUrl = $url;
                $shop->save();
            }

            if($request->wantsJson()) {
                return $this->response(new JsonResponse(['url' => $url]));
            }else{
                return redirect($url);
            }
        }else{
            return new Response('错误');
        }
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
        $shop = $this->repository->find($id);

        if (request()->wantsJson()) {

            return $this->response()->item($shop, new ShopTransformer());
        }

        return view('shops.show', compact('shop'));
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
        $shop = $this->repository->find($id);

        return view('shops.edit', compact('shop'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  ShopUpdateRequest $request
     * @param  string            $id
     *
     * @return Response
     *
     * @throws Exception
     */
    public function update(ShopUpdateRequest $request, $id)
    {
        $data = $request->only(['name', 'country_id', 'province_id', 'city_id', 'county_id', 'address', 'description', 'status', 'user_id']);
        $appManager = app(AppManager::class);
        $data['app_id'] = $appManager->currentApp->id;
        if(isset($data['user_id']) && $data['user_id'] && $request->input('manager_mobile', null) && $request->input('manager_name', null))
            $data['user_id'] = $this->getManager($request->input('manager_mobile'), $request->input('manager_name'))->id;
        if($request->input('lat', null) && $request->input('lng', null)){
            $data['position'] = new Point($request->input('lat'), $request->input('lng'));
            $data['geo_hash'] = (new GeoHash())->encode($request->input('lat'), $request->input('lng'));
        }

       $shop = $this->repository->update($data, $id);

       $response = [
           'message' => 'Shop updated.',
           'data'    => $shop->toArray(),
       ];

       if ($request->wantsJson()) {

           return $this->response()->item($shop, new ShopTransformer());
       }

       return redirect()->back()->with('message', $response['message']);
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

        if (request()->wantsJson()) {

            return $this->response(new JsonResponse([
                'message' => 'Shop deleted.',
                'deleted' => $deleted,
            ]));
        }

        return redirect()->back()->with('message', 'Shop deleted.');
    }

    public function __destruct()
    {
        $this->repository = null;
        $this->shopManagerRepository = null;
        $this->sellerRepository = null;
        parent::__destruct(); // TODO: Change the autogenerated stub
    }
}
