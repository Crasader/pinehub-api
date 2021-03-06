<?php

namespace App\Repositories;

use App\Entities\Merchandise;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\MerchandiseCategoryRepository;
use App\Entities\MerchandiseCategory;
use App\Validators\MerchandiseCategoryValidator;

/**
 * Class MerchandiseCategoryRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class MerchandiseCategoryRepositoryEloquent extends BaseRepository implements MerchandiseCategoryRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return MerchandiseCategory::class;
    }

    

    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }

    /**
     * @param int $id
     * @return $MerchandiseCategory
     */
    public function merchandises(int $id){
        $this->scopeQuery(function (MerchandiseCategory $merchandiseCategory) use($id) {
            return $merchandiseCategory->with('merchandise')
                ->whereHas('merchandise', function ($query) {
                    return $query->where('status', Merchandise::UP);
                })
                ->where(['category_id' => $id]);
        });
        return $this->paginate();
    }
    
}
