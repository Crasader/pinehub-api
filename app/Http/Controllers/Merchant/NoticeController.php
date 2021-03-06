<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/3
 * Time: 14:47
 */

namespace App\Http\Controllers\Merchant;

use App\Jobs\RemoveOrderPaidVoice;
use App\Repositories\ShopRepository;
use App\Transformers\Merchant\ShopTransformer;
use Carbon\Carbon;
use Dingo\Api\Http\Request;
use function GuzzleHttp\Psr7\str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Jormin\BaiduSpeech\BaiduSpeech;


class NoticeController extends Controller
{


    /**
     * @param int $id
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     * @throws
     */
    public function notice(int $id, Request $request) {
        $shop = app(ShopRepository::class)->todayOrderInfo($id);
        $token = Auth::getToken();
        Log::info('======== token =======', [$token]);
        $manager = Auth::user();
        $tokenMeta = null;
        if(cache((string)$token) - time() < 10) {
            $token = Auth::login($manager);
            $tokenMeta = [
                'value' => $token,
                'ttl' => Carbon::now(config('app.timezone'))->addMinute(config('jwt.ttl')),
                'refresh_ttl' => Carbon::now(config('app.timezone'))->addMinute(config('jwt.refresh_ttl'))
            ];
            cache([$token => $tokenMeta['ttl']->getTimestamp()], $tokenMeta['ttl']);
        }
        $key = "shop.{$id}.order.paid";
        $messages = cache($key);
        $hasNotice = !!$messages && !empty($messages);
        if($hasNotice) {
            Log::info('-------- order paid voice play -------');
            cache()->delete($key);
        }
        return $this->response->item($shop, new ShopTransformer($hasNotice))
            ->addMeta('token', $tokenMeta)
            ->addMeta('voices', $messages);
    }
}
