<?php

namespace App\Criteria\Admin;

use App\Entities\WechatAutoReplyMessage;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;
use App\Services\AppManager;

/**
 * Class WechatAutoReplyMessageCriteria.
 *
 * @package namespace App\Criteria\Admin;
 */
class WechatAutoReplyMessageCriteria implements CriteriaInterface
{
    /**
     * Apply criteria in query repository
     *
     * @param WechatAutoReplyMessage  $model
     * @param RepositoryInterface $repository
     *
     * @return mixed
     */
    public function apply($model, RepositoryInterface $repository)
    {
        $appId = app(AppManager::class)->officialAccount->appId;
        return $model->whereAppId($appId);
    }
}
