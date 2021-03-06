<?php

namespace App\Entities;

use App\Jobs\TicketUpdateStatus;
use App\Repositories\TicketRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Prettus\Repository\Contracts\Transformable;
use Prettus\Repository\Traits\TransformableTrait;

/**
 * App\Entities\Ticket
 *
 * @property int $id
 * @property string $code 卡卷编号
 * @property string $cardId 卡券id
 * @property string|null $wechatAppId 微信app id
 * @property string|null $aliAppId 支付宝app id
 * @property string|null $appId 系统app id
 * @property string $cardType 卡券类型
 * @property array $cardInfo 卡券信息
 * @property int $issueCount 发行数量
 * @property int $userGetCount 领取数量
 * @property int $status 0-审核中 1-审核通过 2-审核未通过
 * @property int $sync -1 不需要同步 0 - 同步失败 1-同步中 2-同步成功
 * @property \Illuminate\Support\Carbon|null $beginAt 开始日期
 * @property \Illuminate\Support\Carbon|null $endAt 结束时间
 * @property \Illuminate\Support\Carbon|null $createdAt
 * @property \Illuminate\Support\Carbon|null $updatedAt
 * @property string|null $deletedAt
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Entities\CustomerTicketCard[] $customerTickets
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Entities\Order[] $orders
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Entities\Ticket newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Entities\Ticket query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Entities\Ticket whereAliAppId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Entities\Ticket whereAppId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Entities\Ticket whereBeginAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Entities\Ticket whereCardId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Entities\Ticket whereCardInfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Entities\Ticket whereCardType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Entities\Ticket whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Entities\Ticket whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Entities\Ticket whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Entities\Ticket whereEndAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Entities\Ticket whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Entities\Ticket whereIssueCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Entities\Ticket whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Entities\Ticket whereSync($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Entities\Ticket whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Entities\Ticket whereUserGetCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Entities\Ticket whereWechatAppId($value)
 * @mixin \Eloquent
 */
class Ticket extends Card
{
    protected $table = 'cards';

    const UNAVAILABLE = 3;//unavailable

    public static function boot()
    {
        parent::boot(); // TODO: Change the autogenerated stub
        Ticket::creating(function (Ticket $ticket) {
            if($ticket->cardType == self::DISCOUNT) {
                $ticket->code = 'DI'.app('uid.generator')->getUid(TICKET_CODE_FORMAT,
                        TICKET_SEGMENT_MAX_LENGTH);
            }elseif ($ticket->cardType === self::CASH) {
                $ticket->code = 'CA'.app('uid.generator')->getUid(TICKET_CODE_FORMAT,
                        TICKET_SEGMENT_MAX_LENGTH);
            }elseif ($ticket->cardType === self::COUPON_CARD) {
                $ticket->code = 'CO'.app('uid.generator')->getUid(TICKET_CODE_FORMAT,
                        TICKET_SEGMENT_MAX_LENGTH);
            }elseif ($ticket->cardType === self::GIFT) {
                $ticket->code = 'GI'.app('uid.generator')->getUid(TICKET_CODE_FORMAT,
                        TICKET_SEGMENT_MAX_LENGTH);
            }elseif ($ticket->cardType === self::GROUPON) {
                $ticket->code = 'GR'.app('uid.generator')->getUid(TICKET_CODE_FORMAT,
                        TICKET_SEGMENT_MAX_LENGTH);
            }
            if($ticket->platform === Ticket::OWNER_TICKET) {
                $ticket->cardId = app('uid.generator')->getUid(TICKET_CODE_FORMAT,
                    TICKET_SEGMENT_MAX_LENGTH);
            }
        });

        self::saved(function (Ticket $ticket) {
            $nowDate = Carbon::now();
            $beginAfterSeconds = $ticket->beginAt ? $nowDate->diffInRealSeconds($ticket->beginAt, false) : 0;
            if($beginAfterSeconds < 1 && $ticket->status === Ticket::STATUS_OFF){
                $ticket->status = Ticket::STATUS_ON;
                $ticket->save();
            }
            $endAfterSeconds = $ticket->endAt ? $ticket->beginAt->diffInRealSeconds($ticket->endAt, false) : 0;
            if ($ticket->endAt && $endAfterSeconds < 1 && $ticket->status !==  Ticket::STATUS_EXPIRE) {
                $ticket->status = Ticket::STATUS_EXPIRE;
                $ticket->save();
            }
        });
    }

    public function orders() : HasMany
    {
        return $this->hasMany(Order::class, 'card_id', 'id');
    }

    public function customerTickets() : HasMany
    {
        return $this->hasMany(CustomerTicketCard::class, 'card_id', 'id');
    }
}
