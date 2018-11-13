<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Entities\ShopMerchandise;

/**
 * Class ShopMerchandiseTransformer.
 *
 * @package namespace App\Transformers;
 */
class ShopMerchandiseTransformer extends TransformerAbstract
{
    /**
     * Transform the ShopMerchandise entity.
     *
     * @param \App\Entities\ShopMerchandise $model
     *
     * @return array
     */
    public function transform(ShopMerchandise $model)
    {
        return [
            'id'=>$model->id,
            'merchandise_id'=>$model->merchandiseId,
            'name' => $model->merchandise->name,
            'sell_price' => $model->merchandise->sellPrice,
            'origin_price' => $model->merchandise->originPrice,
            'stock_num' => $model->stockNum,
            'sell_num' => $model->sellNum,
            'main_image' => $model->merchandise->mainImage,
        ];
    }
}