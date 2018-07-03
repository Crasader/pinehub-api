<?php

namespace App\Entities;

use App\Entities\Traits\ModelAttributesAccess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Prettus\Repository\Contracts\Transformable;
use Prettus\Repository\Traits\TransformableTrait;

/**
 * App\Entities\Card
 *
 * @property int $id
 * @property string|null $cardId 卡券id
 * @property string|null $wechatAppId 微信app id
 * @property string|null $aliAppId 支付宝app id
 * @property string|null $appId 系统app id
 * @property string $cardType 卡券类型
 * @property array $cardInfo 卡券信息
 * @property int|null $sync -1 不需要同步 0 - 同步失败 1-同步中 2-同步成功'
 * @property int $status 0-审核中 1-审核通过 2-审核未通过
 * @property \Carbon\Carbon $beginAt
 * @property \Carbon\Carbon $endAt
 * @property \Carbon\Carbon|null $createdAt
 * @property \Carbon\Carbon|null $updatedAt
 * @property string|null $deletedAt
 * @property-read \App\Entities\App|null $app
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Entities\Card whereAliAppId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Entities\Card whereAppId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Entities\Card whereBeginAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Entities\Card whereCardId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Entities\Card whereCardInfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Entities\Card whereCardType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Entities\Card whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Entities\Card whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Entities\Card whereEndAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Entities\Card whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Entities\Card whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Entities\Card whereSync($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Entities\Card whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Entities\Card whereWechatAppId($value)
 * @mixin \Eloquent
 */
class Card extends Model implements Transformable
{
    use TransformableTrait, ModelAttributesAccess;

    const SYNC_NO_NEED = -1;
    const SYNC_FAILED = 0;
    const SYNC_ING = 1;
    const SYNC_SUCCESS = 2;

    const CHECK_ING = 0;
    const CHECK_SUCCESS = 1;
    const CHECK_FAILED = 2;

    //'member_card','coupon_card','discount','groupon','gift'
    const MEMBER_CARD = 'member_card';
    const COUPON_CARD = 'coupon_card';
    const DISCOUNT = 'discount';
    const GROUPON = 'groupon';
    const GIFT = 'gift';

    protected $casts = [
        'card_info' => 'json',
        'begin_at' => 'date',
        'end_at' => 'date'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'card_id',
        'card_type',
        'card_info',
        'status',
        'sync',
        'app_id',
        'wechat_app_id',
        'ali_app_id',
        'begin_at',
        'end_at'
    ];

    public function app() : BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }

}