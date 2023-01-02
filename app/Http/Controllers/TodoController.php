<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Todo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
class TodoController extends Controller
{
    public function __construct()
    {
    	//Applying middleware auth (verify JWT) to this whole class
        $this->middleware('auth:api');
    }

    public function index(Request $request)
    {
    	//Getting logged in user
    	$user=Auth::user();
    	//Getting user's all todos if search is null
    	if(empty($request->json('search'))){
        	$todos = $user->todos()->paginate(10);
    	}
    	//Getting user's all todos by applying filter to title
    	if(!empty($request->json('search'))){
        	$todos = $user->todos()->where('title',$request->get('search'))->paginate(10);
    	}
    	//Returning all todos
        return response()->json([
            'status' => 'success',
            'todos' => $todos
        ]);
    }

    public function store(Request $request)
    {
    	//Applying validation for title and description 
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'description' => 'required|string',
        ]);

        //Returning with error messages if vaildation fails
        if($validator->fails()){
                //return response()->json($validator->errors()->toJson(), 400);
                return response()->json(['messages'=>$validator->errors(),'status'=>'validation-error']);
        }
        //Getting logged in user for associating todo with logged in user
        $user=Auth::user();

        //Creating user's todo 
        $todo = Todo::create([
            'title' => $request->title,
            'description' => $request->description,
            'user_id' => $user->id,
        ]);
        //Returning created todo with error message
        return response()->json([
            'status' => 'success',
            'message' => 'Todo created successfully',
            'todo' => $todo,
        ]);
    }

    public function show($id)
    {
    	//Getting todo with the help of id 
        $todo = Todo::find($id);
        //Returning todo 
        return response()->json([
            'status' => 'success',
            'todo' => $todo,
        ]);
    }

    public function update(Request $request, $id)
    {
    	//Applying validation for title and description
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'description' => 'required|string',
        ]);

        //Returning with error messages if vaildation fails
        if($validator->fails()){
                //return response()->json($validator->errors()->toJson(), 400);
                return response()->json(['messages'=>$validator->errors(),'status'=>'validation-error']);
        }
        //Finding todo with the help of Id and updating with new title and description 
        $todo = Todo::find($id);
        $todo->title = $request->title;
        $todo->description = $request->description;
        $todo->save();
        //Returning todo with message
        return response()->json([
            'status' => 'success',
            'message' => 'Todo updated successfully',
            'todo' => $todo,
        ]);
    }

    public function destroy($id)
    {
    	//Finding todo with the help of Id and deleting
        $todo = Todo::find($id);
        $todo->delete();
        //Returning with message
        return response()->json([
            'status' => 'success',
            'message' => 'Todo deleted successfully'
        ]);
    }
}
