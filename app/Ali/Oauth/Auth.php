<?php
/**
 * Created by PhpStorm.
 * User: wangzaron
 * Date: 2018/5/22
 * Time: 下午3:39
 */

namespace App\Ali\Oauth;


use App\Ali\Oauth\Data\AuthData;
use Payment\Common\Ali\AliBaseStrategy;

class Auth extends AliBaseStrategy
{
    // wap 支付接口名称
    protected $method = 'alipay.user.info.auth';

    /**
     * 获取支付对应的数据完成类
     * @return string
     * @author helei
     */
    public function getBuildDataClass()
    {
        $this->config->method = $this->method;
        // 以下两种方式任选一种
        return AuthData::class;
    }

    /**
     * 返回可发起h5支付的请求
     * @param array $data
     * @return array
     * @throws
     */
    protected function retData(array $data)
    {
        $reqData = parent::retData($data);
        dd($reqData);
        Log::debug('ali signed data', $reqData);
        // 发起网络请求
        try {
            $data = $this->sendReq($reqData);
        } catch (PayException $e) {
            throw $e;
        }

        // 检查是否报错
        if ($data['code'] !== '10000') {
            new PayException($data['sub_msg']);
        }
        return $data;
    }
}