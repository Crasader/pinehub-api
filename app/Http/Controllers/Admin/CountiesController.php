<?php

namespace App\Http\Controllers\Admin;

use App\Entities\County;
use App\Repositories\CityRepository;
use Dingo\Api\Http\Request;
use App\Http\Response\JsonResponse;
use Exception;
use App\Http\Requests\Admin\CountyCreateRequest;
use App\Http\Requests\Admin\CountyUpdateRequest;
use App\Transformers\CountyTransformer;
use App\Transformers\CountyItemTransformer;
use App\Repositories\CountyRepository;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class CountiesController.
 *
 * @package namespace App\Http\Controllers\Admin;
 */
class CountiesController extends Controller
{
    /**
     * @var CountyRepository
     */
    protected $repository;

    protected $cityRepository;


    /**
     * CountiesController constructor.
     *
     * @param CountyRepository $repository
     */
    public function __construct(CountyRepository $repository,CityRepository $cityRepository)
    {
        $this->repository = $repository;
        $this->cityRepository = $cityRepository;
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, int $id = null)
    {
        $routeName = $request->route()[1]['as'];
        switch ($routeName) {
            case 'county.list.country': {
                $this->repository->scopeQuery(function (Builder $county) use($id){
                    return $county->where('country_id', $id);
                });
                break;
            }
            case 'county.list.province': {
                $this->repository->scopeQuery(function (Builder $county) use($id){
                    return $county->where('province_id', $id);
                });
                break;
            }
            case 'county.list.county': {
                $this->repository->scopeQuery(function (Builder $county) use($id){
                    return $county->where('city_id', $id);
                });
                break;
            }
        }
        $counties = $this->repository->with(['city', 'province', 'country'])->paginate();
        return $this->response()->paginator($counties, new CountyItemTransformer());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  CountyCreateRequest $request
     * @param int $cityId
     * @return \Illuminate\Http\Response
     *
     * @throws Exception
     */
    public function store(CountyCreateRequest $request, int $cityId = null)
    {
        $data = $request->all();
        if($cityId) {
            $city = $this->cityRepository->find($cityId);
            $data['city_id'] = $city->id;
            $data['country_id'] = $city->countryId;
            $data['province_id'] = $city->provinceId;
        }
        $county = $this->repository->create($data);

        return $this->response()->item($county, new CountyTransformer());
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $county = $this->repository->find($id);
        return $this->response()->item($county, new CountyTransformer());
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $county = $this->repository->find($id);

        return view('counties.edit', compact('county'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  CountyUpdateRequest $request
     * @param  string            $id
     *
     * @return \Illuminate\Http\Response
     *
     * @throws Exception
     */
    public function update(CountyUpdateRequest $request, $id)
    {
       $county = $this->repository->update($request->all(), $id);
       return $this->response()->item($county, new CountyTransformer());
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $deleted = $this->repository->delete($id);

        if (request()->wantsJson()) {

            return $this->response(new JsonResponse([
                'message' => 'County deleted.',
                'deleted' => $deleted,
            ]));
        }

        return redirect()->back()->with('message', 'County deleted.');
    }
}
