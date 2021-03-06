<?php

namespace App\Repositories;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\WechatMaterialRepository;
use App\Entities\WechatMaterial;
use App\Validators\WechatMaterialValidator;

/**
 * Class WechatMaterialRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class WechatMaterialRepositoryEloquent extends BaseRepository implements WechatMaterialRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return WechatMaterial::class;
    }

    

    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }
    
}
