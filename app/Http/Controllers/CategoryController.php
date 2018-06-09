<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Category;
use Mockery\Exception;

class CategoryController extends Controller
{
    protected $model;

    public function __construct(Category $model)
    {
        $this->model = $model;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $count = $this->model->count();
        $data = $this->model->where('previous_id', '=',null)->get();
        $i= 1;
        while ($data->last() && $i<$count)
        {
            $parent = $this->model->where('previous_id', '=', $data->last()->id)->get();
            $data = $data->merge($parent);
            $i++;
        }

        return $data;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $categoryId = $this->model->insertGetId($request->all());
        $this->setInsertedCategoryReference($categoryId,$request->previous_id);

        return $this->model->findOrFail($categoryId);
    }

    protected function setInsertedCategoryReference($categoryId, $previousId)
    {
        if ($previousId === null) {
            $condition = null;
        } else {
            $condition = $previousId;
        }
        $model = $this->model->where('id','<>',$categoryId)->where('previous_id',$condition)
                ->update(['previous_id'=>$categoryId]);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        if ($id === $request->previous_id) {
            throw new Exception('Category cannot reference themselves');
        }

        $model = $this->model->findOrFail($id);

        if ($request->previous_id !== $model->previous_id) {
            $this->mergeDeleteItemGap($id, $model->previous_id);
            $this->setInsertedCategoryReference($id, $request->previous_id);
        }
        $model->name = $request->name;
        $model->previous_id = $request->previous_id;
        $model->save();
        return $model;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $model = $this->model->findOrFail($id);
        $this->mergeDeleteItemGap($id, $model->previous_id);
        $model->delete();
        return 'okay';


    }

    protected function mergeDeleteItemGap($id, $previousId)
    {
       $this->model->where('previous_id',$id)->update([
            'previous_id'=> $previousId
        ]);
    }
}
