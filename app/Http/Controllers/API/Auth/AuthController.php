<?php

namespace App\Http\Controllers\API\Auth;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    //
    public function register(Request $request)
    {
        $validator =  Validator::make($request->all(), [
            'name' => 'required|string|max:200',
            'email' => 'required|string|unique:users|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password)
            ]);
            $token = $user->createToken('register_token')->plainTextToken;

            return response()->json([
                'status' => Response::HTTP_OK,
                'message' => 'User created successfully',
                'access_token' => $token,
                'data' => $user,
                'type' => 'Bearer'
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function login(Request $request)
    {

        $credentials = $request->only('email', 'password');
        $user = User::where('email', $request->email)->first();

        $validator =  Validator::make($request->all(), [
            // 'name' => 'required|string',
            'email' => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        if (!Auth::attempt($credentials)) {
            if (!$user || Hash::check($request['password'], $user->password)) {
                return response()->json([
                    'status' => Response::HTTP_UNAUTHORIZED,
                    'message' => "Invalid Credentials"
                ], Response::HTTP_UNAUTHORIZED);
            }
        }

        $token = $user->createToken('login_token')->plainTextToken;

        return response()->json([
            'status' => Response::HTTP_OK,
            'message' => 'Login Success',
            'access_token' => $token,
            'data' => $request->user(),
            'token_type' => 'Bearer'
        ], Response::HTTP_OK);
    }

    public function logout(Request $request)
    {
        //memang tokens() undefind di vscode nya
        Auth::user()->tokens()->delete();
        return response()->json([
            'status' => Response::HTTP_OK,
            'message' => 'Logged out successfully'
        ], Response::HTTP_OK);
    }
}
