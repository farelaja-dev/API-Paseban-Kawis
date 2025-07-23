<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\User;
use App\Models\EmailOtp;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="Login user",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="user@email.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login berhasil",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Login berhasil"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Email atau password salah"
     *     )
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Email atau password salah'], 401);
        }

        // Generate token dengan expired 1 bulan
        $token = $user->createToken('flutter-app', ['*'], now()->addMonth())->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'nama' => $user->nama,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'telepon' => $user->telepon,
                'foto' => $user->foto,
                'role_id' => $user->role_id,
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/register",
     *     summary="Register user baru dengan OTP email",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nama","email","password","telepon"},
     *             @OA\Property(property="nama", type="string", example="Izuna"),
     *             @OA\Property(property="email", type="string", format="email", example="user@email.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="telepon", type="string", example="08123456789")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP dikirim ke email",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Kode OTP dikirim ke email")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validasi gagal"
     *     )
     * )
     */
    public function register(Request $request)
    {
        $request->validate([
            'nama' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'telepon' => 'required',
        ]);

        $user = User::create([
            'nama' => $request->nama,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'telepon' => $request->telepon,
            'role_id' => 2, 
        ]);

        if (!$user || !$user->id) {
            Log::error('User gagal dibuat', ['user' => $user]);
            return response()->json(['message' => 'User gagal dibuat'], 500);
        }

        Log::info('User ID untuk OTP:', ['user_id' => $user->id]);

        $kode_otp = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $expired = now()->addMinutes(10);

        // INSERT KE email_otps
        EmailOtp::create([
            'user_id' => $user->id,
            'kode_otp' => $kode_otp,
            'type' => 'register',
            'expired_at' => $expired,
        ]);

        // Kirim email OTP
        Mail::raw("Kode OTP Anda: $kode_otp", function($msg) use ($user) {
            $msg->to($user->email)->subject('Kode OTP Registrasi');
        });

        return response()->json([
            'message' => 'Kode OTP dikirim ke email',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/verify-otp",
     *     summary="Verifikasi OTP email user",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","kode_otp"},
     *             @OA\Property(property="email", type="string", format="email", example="user@email.com"),
     *             @OA\Property(property="kode_otp", type="string", example="1234")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Verifikasi berhasil",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Email berhasil diverifikasi")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="OTP salah atau expired"
     *     )
     * )
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'kode_otp' => 'required|digits:4',
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }

        $otp = EmailOtp::where('user_id', $user->id)
            ->where('kode_otp', $request->kode_otp)
            ->where('expired_at', '>', now())
            ->first();

        if (!$otp) {
            return response()->json(['message' => 'OTP salah atau expired'], 400);
        }

        // Tandai email sudah diverifikasi
        $user->email_verified_at = now();
        $user->save();

        // Hapus OTP setelah verifikasi
        $otp->delete();

        return response()->json(['message' => 'Email berhasil diverifikasi']);
    }

    /**
     * @OA\Post(
     *     path="/api/forgot-password",
     *     summary="Request OTP for forgot password",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email")
     *         )
     *     ),
     *     @OA\Response(response=200, description="OTP sent to email"),
     *     @OA\Response(response=404, description="Email not found")
     * )
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'Email not found'], 404);
        }

        $kode_otp = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $expiredAt = now()->addMinutes(10);

        EmailOtp::updateOrCreate(
            ['user_id' => $user->id, 'type' => 'forgot_password'],
            ['kode_otp' => $kode_otp, 'expired_at' => $expiredAt]
        );

        Mail::raw("Kode OTP lupa password Anda: $kode_otp", function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Kode OTP Lupa Password');
        });

        return response()->json(['message' => 'OTP sent to email']);
    }

    /**
     * @OA\Post(
     *     path="/api/verify-forgot-otp",
     *     summary="Verify OTP for forgot password",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "otp"},
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="otp", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="OTP verified"),
     *     @OA\Response(response=400, description="OTP invalid or expired")
     * )
     */
    public function verifyForgotOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:4'
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $otpData = EmailOtp::where('user_id', $user->id)
            ->where('type', 'forgot_password')
            ->where('kode_otp', $request->otp)
            ->where('expired_at', '>', now())
            ->first();

        if (!$otpData) {
            return response()->json(['message' => 'OTP invalid or expired'], 400);
        }

        $otpData->delete();

        return response()->json(['message' => 'Kode OTP terverifikasi']);
    }

    /**
     * @OA\Post(
     *     path="/api/reset-password",
     *     summary="Reset password after OTP verified",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password", "password_confirmation"},
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password"),
     *             @OA\Property(property="password_confirmation", type="string", format="password")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Password reset successful"),
     *     @OA\Response(response=400, description="Email not found")
     * )
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|confirmed|min:6'
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'Email not found'], 400);
        }

        // Update password
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json(['message' => 'Password reset successful']);
    }

    /**
     * @OA\Post(
     *     path="/api/resend-register-otp",
     *     summary="Kirim ulang OTP registrasi ke email user yang belum diverifikasi",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="user@email.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP dikirim ulang ke email",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Kode OTP dikirim ulang ke email")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User tidak ditemukan atau sudah diverifikasi"
     *     )
     * )
     */
    public function resendRegisterOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }
        if ($user->email_verified_at) {
            return response()->json(['message' => 'Email sudah diverifikasi'], 404);
        }

        $kode_otp = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $expired = now()->addMinutes(10);

        // Update or create OTP baru untuk type register
        \App\Models\EmailOtp::updateOrCreate(
            [
                'user_id' => $user->id,
                'type' => 'register',
            ],
            [
                'kode_otp' => $kode_otp,
                'expired_at' => $expired,
            ]
        );

        // Kirim email OTP
        Mail::raw("Kode OTP Anda: $kode_otp", function($msg) use ($user) {
            $msg->to($user->email)->subject('Kode OTP Registrasi');
        });

        return response()->json([
            'message' => 'Kode OTP dikirim ulang ke email',
        ]);
    }
} 
