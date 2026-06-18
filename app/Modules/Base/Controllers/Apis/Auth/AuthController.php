<?php

namespace App\Modules\Base\Controllers\Apis\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    
    public function login(Request $request){

        // $user = User::create([
        //     'name' => $request->input('name'),
        //     'email' => $request->input('email'),
        //     'password' => Hash::make($request->input('password')),
        // ]);

        // return response()->json([
        //     'status' => false,
        //     'message' => 'The provided credentials do not match our records.',
        // ], 200);

        //dd($request);

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if($validator->fails()){

            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 200);
        }

        if(Auth::attempt(['email' => $request->email, 'password' => $request->password])){
                
            $user = Auth::user();

            $token = $user->createToken('auth_token')->plainTextToken;

            unset($user->password);

            return response()->json([
                'status' => true,
                'message' => 'User logged in successfully',
                'user' => $user,
                'account_token' => $token,
                'token_type' => 'Bearer',
            ]);
        }else{

            return response()->json([
                'status' => false,
                'message' => 'The provided credentials do not match our records.',
            ], 200);
        }
    }
}
