<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vehicle;

class VehicleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->all();

        if( request('id') != null ) {

            if($data['type'] == 'all') {
                $data['city_ids'] = null;
            }
        }
        $result = Vehicle::updateOrCreate(['id' => $request->id], $data);
        uploadMediaFile($result, $request->vehicle_image, 'vehicle_image');

        $message = __('message.update_form',[ 'form' => __('message.vehicle') ] );
		if($result->wasRecentlyCreated){
			$message = __('message.save_form',[ 'form' => __('message.vehicle') ] );
		}

        if($request->is('api/*')) {
            return json_message_response($message);
		}
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $vehicle = Vehicle::find($id);
        $message = __('message.msg_fail_to_delete',['item' => __('message.vehicle')] );
        
        if($vehicle != '') {
            $vehicle->delete();
            $message = __('message.msg_deleted',['name' => __('message.vehicle')] );
        }
        if(request()->is('api/*')){
            return json_custom_response(['message'=> $message , 'status' => true]);
        }
    }

    public function action(Request $request)
    {
        $id = $request->id;
        $vehicle = Vehicle::withTrashed()->where('id',$id)->first();
        $message = __('message.not_found_entry',['name' => __('message.vehicle')] );
        if($request->type === 'restore'){
            $vehicle->restore();
            $message = __('message.msg_restored',['name' => __('message.vehicle')] );
        }

        if($request->type === 'forcedelete'){
            $vehicle->forceDelete();
            $message = __('message.msg_forcedelete',['name' => __('message.vehicle')] );
        }

        return json_custom_response(['message'=> $message, 'status' => true]);
    }
}
