<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Actions\Fortify\PasswordValidationRules;

class UserController extends Controller
{
    use PasswordValidationRules;

    public function login(Request $request){
        try {
            $request->validate([
                'email' => 'email|required',
                'password' => 'required'
            ]);

            $credentials = $request(['email','password']);
            if(!Auth::attempt($credentials)){
                return ResponseFormatter::error([
                    'message' => 'Unauthorized'
                ], 'Authentication Failed','500');
            }

            $user = User::where('email' , $request->email)->first();
            if(!Hash::check($request->password, $user->password, [])){
                throw new \Exception('Invalid Credentials');
            }

            $tokenResult = $user->createToken('authToken')->plaintTextToken;
            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user
            ], 'Aunthenticated');

        }catch(Exception $error){
            return ResponseFormatter::error([
                'message' => 'Something wrong',
                'error' => $error,
            ], 'Aunthentication Failed', 500);
        }
    }

    public function register(Request $request){
        try{
            $request->validate([
                'name' => ['required','string','max:255'],
                'email' => ['required','string','email','max:255','unique:users'],
                'password' => $this->passwordRules()
            ]);

            User::create([
                'name' =>$request->name,
                'email' =>$request->email,
                'address' =>$request->address,
                'houseNumber' =>$request->houseNumber,
                'phoneNumber' =>$request->phoneNumber,
                'city' =>$request->city,
                'password' => Hash::make($request->password)
            ]);

            $user = User::where('email',$request->email)->first();

            $tokenResult = $user->createToken('authToken')->plainTextToken;

            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user,
            ]);

        }catch(Exception $error){
            return ResponseFormatter::error([
                'message' => 'Something wrong',
                'error' => $error,
            ], 'Aunthentication Failed', 500);
        }
    }

    public function logout(Request $request){
        $token = $request->user()->currentAccessToken()->delete();
        return ResponseFormatter::success($token, 'Token Revolved');
    }

    public function updateProfile(Request $request){
        $data = $request->all();

        $user = Auth::user();
        $user->update($data);

        return ResponseFormatter::success($token, 'Profile updated');
    }

    public function fetch(Request $request){
        return ResponseFormatter::success(
            $request->user(),'Data profile berhasil terambil'
        );
    }

    public function updatePhoto(Request $request){
        $validator = Validator::make($request->all(),[
            'file' => 'required|image|max:20148',
        ]);

        if($validator->fails()){
            return ResponseFormatter::error([
                'error' => $validator->errors(),
            ], 'Update photo Failed', 401);
        }
        
        if($request->file('file')){
            $file = $request->file->store('assets/user','public');

            $user = Auth::user();
            $ser->profile_photo_path = $file;
            $user->update();
        }

        return ResponseFormatter::success([$file], 'File succesfuly uploaded');

        
    }
}
