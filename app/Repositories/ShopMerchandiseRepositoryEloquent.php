<?php

namespace App\Repositories;

use App\Entities\Category;
use App\Entities\Merchandise;
use App\Repositories\Traits\Destruct;
use App\Services\AppManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Entities\ShopMerchandise;


/**
 * Class ShopMerchandiseRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class ShopMerchandiseRepositoryEloquent extends BaseRepository implements ShopMerchandiseRepository
{
    protected $fieldSearchable = [
        'merchandise.name' => 'like',
        'sell_num'=>'*',
        'sell_price' => '*',
        'merchandise.status' => '='
    ];

    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return ShopMerchandise::class;
    }

    

    /**
     * Boot up the repository, pushing criteria
     * @throws
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }

    /**
     * @param int $id
     * @return mixed
     */

    public function storeCategories(int $id){
        return app(Category::class)->whereIn('id', function (\Illuminate\Database\Query\Builder $builder) use($id) {
            return $builder->select(['merchandise_categories.category_id'])
                ->from('merchandise_categories')
                ->join('shop_merchandises', 'merchandise_categories.merchandise_id',
                    '=', 'shop_merchandises.merchandise_id')
                ->whereIn('shop_merchandises.merchandise_id', function (\Illuminate\Database\Query\Builder $builder){
                    return $builder->select(['merchandises.id'])
                        ->from('merchandises')
                        ->where('status', Merchandise::UP);
                })->where('shop_id', $id);
        })
            ->where('app_id', app(AppManager::class)->getAppId())
            ->paginate();
    }

    /**
     * @param int $id
     * @param int $categoryId
     * @return mixed
     */
    public function storeCategoryMerchandises(int $id, int $categoryId){
        $this->scopeQuery(function (ShopMerchandise $shopMerchandise) use($id, $categoryId) {
            return $shopMerchandise->with('merchandise')
                ->whereHas('merchandise', function (Builder $query)use ($categoryId) {
                    return $query->whereHas('categories', function (Builder $query) use ($categoryId) {
                        return $query->where('categories.id', $categoryId);
                    })
                        ->where('status', Merchandise::UP)
                        ->where('app_id', app(AppManager::class)->getAppId());
                })
                ->where(['shop_id' => $id]);
        });
        return $this->paginate();
    }

    /**
     * @param $store
     * @return mixed
     */
    public function storeStockMerchandise($store){
        $this->scopeQuery(function (ShopMerchandise $ShopMerchandise) use($store) {
            return $ShopMerchandise->with('merchandise')
                ->whereHas('merchandise', function ($query) {
                    return $query->where('status', Merchandise::UP);
                })
                ->where(['shop_id'=>$store['store_id'],'category_id'=>$store['category_id']]);
        });
        return $this->paginate();
    }

    /**
     * @param int $shopId
     * @param array $merchandisesIds
     * @return mixed
     */
    public function shopMerchandises(int $shopId,array $merchandisesIds){
        $this->scopeQuery(function (ShopMerchandise $ShopMerchandise) use($shopId,$merchandisesIds) {
            return $ShopMerchandise->where('shop_id',$shopId)
                ->whereHas('merchandise', function (Builder $query) {
                    return $query->where('status', Merchandise::UP)
                        ->where('app_id', app(AppManager::class)->getAppId());
                })
                ->whereIn('id', $merchandisesIds);
        });
        return $this->paginate();
    }
}
