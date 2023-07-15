<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MastersController extends Controller
{
    //
    public function create(Request $request)
    {
        try {
            // $validator = Validator::make($request->all(), [
            //     'name' => 'required',
            // ]);
            // $name = $request->name;
            // $id = $request->id;
            // if ($validator->fails()) :
            //     $errors = implode(" ", array_flatten(array_values($validator->errors()->getMessages())));
            //     $response = ['type' => "error", 'errors' => $errors];
            //     return response()->json($response, 200);
            // endif;

            // $data = $request->all();
            // if ($id) {
            //     \Log::useDailyFiles(storage_path() . env('ACTION_LOG_FOLDER').env('ACTION_LOG_FILE_NAME'));
            //     \Log::info($this->master." Update Start for ID:".$id);
            //     $user = $forProducts = $this->master::withTrashed()->findOrFail($id);
            //     $data['updated_by'] = 2;
            //     $nameCheck = $this->master::where('name', $name)->withTrashed()->where('id', '!=', $id)->first();
            //     $this->deleteLog(null, [$forProducts], "update");

            // } else {
            //     $user = new $this->master();
            //     $data['created_by'] = 1;
            //     $data['updated_by'] = 1;
            //     $nameCheck = $this->master::where('name', $name)->withTrashed()->first();
            // }

            // if ($nameCheck) {
            //     $response = ['type' => "error", 'errors' => "Name already exist"];
            //     return response()->json($response, 200);
            // }

            // $user->fill($data);
            // $user->save();
            // if($id){
            //     $this->deleteLog(null, [$user], "new");
            //     \Log::info($this->master." Update End for ID:".$id);
            // }
            $response = ['type' => "success", 'result' => "user", 'msg' => "Master Data saved successfully"];
            return response()->json($response, 200);
        } catch (\Exception $e) {
            $response = ['type' => "error", 'msg' => "Something Went Wrong"];
            return response()->json($response, 422);
        }
    }
}
