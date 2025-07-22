<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AkunController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/akun/user-list",
     *     summary="List user dengan role_id=2 (user biasa)",
     *     tags={"Akun"},
     *     security={{ "sanctum":{ }}},
     *     @OA\Response(
     *         response=200,
     *         description="Daftar user",
     *         @OA\JsonContent(type="array", @OA\Items(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="nama", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="telepon", type="string")
     *         ))
     *     )
     * )
     */
    public function listUser()
    {
        $users = User::where('role_id', 2)
            ->select('id', 'nama', 'email', 'telepon')
            ->get();
        return response()->json($users);
    }

    /**
     * @OA\Delete(
     *     path="/api/akun/user/{id}",
     *     summary="Hapus user dan data terkait (admin only)",
     *     tags={"Akun"},
     *     security={{ "sanctum":{ }}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User and related data deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function deleteUser($id)
    {
        $admin = Auth::user();
        if (!$admin || $admin->role_id != 1) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        $user->delete(); // relasi email_otps sudah cascade
        return response()->json(['message' => 'User and related data deleted successfully']);
    }
} 
