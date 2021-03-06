<?php /** @noinspection PhpUndefinedFieldInspection */

namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Entities\Member as MemberItem;

/**
 * Class MemberItemTransformer.
 *
 * @package namespace App\Transformers;
 */
class MemberItemTransformer extends TransformerAbstract
{
    /**
     * Transform the MemberItem entity.
     *
     * @param MemberItem $model
     *
     * @return array
     */
    public function transform(MemberItem $model)
    {
        if($model->orderCount !== $model->ordersCount) {
            $model->orderCount = $model->ordersCount;
        }
        return array(
            'id'         => (int) $model->id,
            'nickname'   => $model->nickname,
            'mobile'     => $model->mobile,
            'official_account' => !$model->officialAccountCustomer() ? null : $model->officialAccountCustomer()->only(array('nickname', 'avatar', 'sex', 'country', 'province', 'city')),
            'mini_program' => !$model->miniProgramCustomer() ? null : $model->miniProgramCustomer()->only(array('nickname', 'avatar', 'sex', 'country', 'province', 'city')),
            /* place your other model properties here */
            'app_id' => $model->appId,
            'channel' => $model->channel,
            'register_channel' => $model->registerChannel,
            'orders_count' => $model->ordersNum(),
            'tags'  => $model->tags,
            'total_score' => $model->totalScore,
            'can_use_score' => $model->canUseScore,
            'score' => $model->score,
            'card' => '待开发',
            'status' => $model->status,
            'country' => $model->country ? $model->country : null,
            'province' => $model->province? $model->province : null,
            'city' => $model->city? $model->city : null,
            /* place your other model properties here */

            'created_at' => $model->createdAt,
            'updated_at' => $model->updatedAt
        );
    }
}
