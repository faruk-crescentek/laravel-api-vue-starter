<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\ForgotPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Helpers\Helper;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Create user
     *
     * @param  [string] name
     * @param  [string] email
     * @param  [string] password
     * @param  [string] password_confirmation
     * @return [string] message
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|string|unique:users',
            'password' => 'required|string',
            'c_password' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $user = new User([
            'name'  => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);
        $user->save();
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->plainTextToken;

        $userAbilities = [
            'create_post',
            'edit_post',
            'delete_post',
        ];

        return response()->json([
            'message' => 'Successfully created user!',
            'accessToken' => $token,
            'userAbilities' => $userAbilities,
            'userData' => Auth::user()
        ], 201);
    }



    /**
     * Login user and create token
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [boolean] remember_me
     */

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
            'remember_me' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $credentials = request(['email', 'password']);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $user = $request->user();
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->plainTextToken;

        $userAbilities = $user->getAllPermissions()->pluck('name');
        $userRoles = $user->getRoleNames();

        $userData = Auth::user();
        $userData->role = $userRoles;

        return response()->json([
            'accessToken' => $token,
            'token_type' => 'Bearer',
            'userAbilities' => $userAbilities,
            'userData' => $userData
        ]);
    }

    /**
     * Get the authenticated User
     *
     * @return [json] user object
     */
    public function user(Request $request)
    {

        return response()->json($request->user());
    }

    /**
     * Logout user (Revoke the token)
     *
     * @return [string] message
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    public function forgotPassword(Request $request) {

        $email = $request->email;

        $token = Helper::generateRandomString(20);

        $user = User::where('email', $email)->first();

        if($user) {

            $messages["greeting"] = "Dear {$user->name}";
            $messages["message"] = "here is link to change your password";
            $messages["link"] = "http://localhost:5173/change-password/".$token;

            try {
                $user->notify(new ForgotPassword($messages));

                DB::table('password_reset_tokens')->where(['email'=> $request->email])->delete();

                DB::table('password_reset_tokens')->insert([
                    'email' => $email,
                    'token' => $token,
                    'created_at' => Carbon::now()
                ]);
            } catch(Exception $e) {
                return response()->json(['message' => $e->getMessage()]);
            }

            return response()->json(['success' => true, 'message' => 'forgot password link send to email successfully']);

        } else {

            return response()->json(['error' => true, 'message' => 'User not found']);
        }
    }

    public function changePassword(Request $request) {

        $request->validate([
            'password' => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required'
        ]);

        $updatePassword = DB::table('password_reset_tokens')->where(['token' => $request->token])->first();

        if(!$updatePassword){
            return response()->json(['error' => true, 'message' => 'Invalid token']);
        }

        $user = User::where('email', $updatePassword->email)->update(['password' => Hash::make($request->password)]);

        DB::table('password_reset_tokens')->where(['email'=> $updatePassword->email])->delete();

        return response()->json(['success' => true, 'message' => 'Your password has been changed!']);
    }
}
