<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use App\Models\User;
use App\Models\UserRoles;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    //
    public function create(Request $request) {
        // \Log::info($request);
         
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'user_type' => ['required'],
        ]);

        $user = Auth::user();
        // $roles = UserRoles::firstWhere('role_user_id', $user->id);

        // if(! Gate::allows('create-user', $roles)){
        //     return response()->json(['error' => 'You are not authorized to do this opeartion'], 403);
        // }

        $permissions = 0;
    
        if ($request->has('role_read') && $request->role_read) $permissions |= User::PERMISSION_READ;
        if ($request->has('role_edit') && $request->role_edit) $permissions |= User::PERMISSION_EDIT;
        if ($request->has('role_delete') && $request->role_delete) $permissions |= User::PERMISSION_DELETE;
        if ($request->has('role_create') && $request->role_create) $permissions |= User::PERMISSION_CREATE;



        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'user_type'  => $request->user_type,
            'permissions' => $permissions,
            'password' => Hash::make($request->string('password')),
        ]);

        // $user_id = $user->id;

        // $user_roles = new UserRoles;

        // $user_roles->role_user_id = $user_id;
        // $user_roles->role_user_type = $request->role_user_type;
        // $user_roles->role_read = $request->role_read;
        // $user_roles->role_write = $request->role_write;
        // $user_roles->role_edit = $request->role_edit;
        // $user_roles->role_delete = $request->role_delete;

        // $user_roles->save();

        return response()->json(['message' => 'User created successfully']);

    }

    public function getUsers() {
        return User::all();
    }

    public function getUser(Request $request) {
        $request->validate([
            'id' => 'required|numeric'
        ]);

        // \Log::info($request);
        $user = User::findOrFail($request['id']);
        // $user = DB::table('users')
        // ->join('user_roles', 'users.id', '=', 'user_roles.role_user_id')
        // ->where('users.id', $request['id'])
        // ->select('users.id', 'users.name', 'users.email', 'users.created_at', 'users.updated_at', 'user_roles.*')
        // ->first();

                
        return response()->json($user);
    }

    public function editUser(Request $request) {
        // \Log::info($request);
        $message = '';
        $now = date("Y-m-d H:i:s");

        $request->validate([
            'id' => ['required', 'numeric'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'user_type' => ['required'],
        ]);

        $user = User::findOrFail($request->id);
        if($user) {

            $permissions = 0;
    
            if ($request->has('role_read') && $request->role_read) $permissions |= User::PERMISSION_READ;
            if ($request->has('role_edit') && $request->role_edit) $permissions |= User::PERMISSION_EDIT;
            if ($request->has('role_delete') && $request->role_delete) $permissions |= User::PERMISSION_DELETE;
            if ($request->has('role_create') && $request->role_create) $permissions |= User::PERMISSION_CREATE;

            $user->name     =$request->name;
            $user->email    =$request->email;
            $user->permissions = $permissions;
            $user->user_type = $request->user_type;
            
           // check if user is changing password
           if(isset($request->passsword) && !empty($request->password)) {
                $user->password = Hash::make($request->string('password'));
           }

             //    Gate::authorize('update');
            //    \Log::info($request->user());

            // if(! Gate::allows('update-user')){
            //     return response()->json(['error' => 'You are not authorized to modify user details!'], 403);
            // }

           if($user->update()) {

                $message='success';
           }

           return response()->json(['message' => $message]);

        }
        
    }

    public function authUser(Request $request) {
        // \Log::info($request);
        $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'password' => ['required', 'max:255'],
        ]);
        
        // Find the user by email
        $user = User::where('email', $request->email)->first();

        // Check if user exists and the provided password matches the stored hashed password
        if ($user && Hash::check($request->password, $user->password)) {

            if ($user->hasPermission(User::PERMISSION_DELETE)) {
                
            } else {
                return response()->json([
                    'message' => 'not_permitted',
                ], 200);
            }

            return response()->json([
                'message' => 'authorised',
            ], 200);
        }

        return response()->json([
            'message' => 'invalid_creds'
        ]);
        
    }

    public function deleteUser(Request $request) {
        $request->validate([
            'id' => 'required|numeric'
        ]);

        
    
        if (!Gate::allows('delete')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = User::find($request->id);
        if($user) {
            $user->delete();

            return response()->json(['message' => 'success']);
        }

        return response()->json(['message' => 'user details not found']);

    }
    
}
