<?php

namespace App\Repositories;

use App\Repositories\Traits\Destruct;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\ProvinceRepository;
use App\Entities\Province;
use App\Validators\ProvinceValidator;

/**
 * Class ProvinceRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class ProvinceRepositoryEloquent extends BaseRepository implements ProvinceRepository
{
    use Destruct;
    protected $fieldSearchable = [
        'code' => '=',
        'name' => 'like',
        'country.code'  => '=',
        'country.name'  => 'like'
    ];
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return Province::class;
    }

    

    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }
    
}
