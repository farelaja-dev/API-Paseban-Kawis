<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CategoryModul;
use App\Models\Modul;

class CategoryModulController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/category_modul",
     *     summary="List semua kategori modul",
     *     tags={"Category Modul"},
     *     security={{ "sanctum":{ }}},
     *     @OA\Response(
     *         response=200,
     *         description="List kategori modul",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/CategoryModul"))
     *     )
     * )
     */
    public function index()
    {
        return response()->json(CategoryModul::all());
    }

    /**
     * @OA\Post(
     *     path="/api/category_modul",
     *     summary="Tambah kategori modul baru (admin only, role_id=1)",
     *     tags={"Category Modul"},
     *     security={{ "sanctum":{ }}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nama"},
     *             @OA\Property(property="nama", type="string", example="Design Product")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Kategori modul berhasil dibuat",
     *         @OA\JsonContent(ref="#/components/schemas/CategoryModul")
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
        ]);
        $category = CategoryModul::create(['nama' => $request->nama]);
        return response()->json($category, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/category_modul/{id}",
     *     summary="Detail kategori modul",
     *     tags={"Category Modul"},
     *     security={{ "sanctum":{ }}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detail kategori modul",
     *         @OA\JsonContent(ref="#/components/schemas/CategoryModul")
     *     ),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show($id)
    {
        $category = CategoryModul::findOrFail($id);
        return response()->json($category);
    }

    /**
     * @OA\Put(
     *     path="/api/category_modul/{id}",
     *     summary="Update kategori modul (admin only, role_id=1)",
     *     tags={"Category Modul"},
     *     security={{ "sanctum":{ }}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nama"},
     *             @OA\Property(property="nama", type="string", example="Design Product Baru")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Kategori modul berhasil diupdate",
     *         @OA\JsonContent(ref="#/components/schemas/CategoryModul")
     *     ),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
        ]);
        $category = CategoryModul::findOrFail($id);
        $category->update(['nama' => $request->nama]);
        return response()->json($category);
    }

    /**
     * @OA\Delete(
     *     path="/api/category_modul/{id}",
     *     summary="Hapus kategori modul beserta semua modul yang terkait (admin only, role_id=1)",
     *     tags={"Category Modul"},
     *     security={{ "sanctum":{ }}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Kategori dan modul terkait berhasil dihapus",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Category and related modul deleted")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy($id)
    {
        $category = CategoryModul::findOrFail($id);
        Modul::where('category_modul_id', $id)->delete();
        $category->delete();
        return response()->json(['message' => 'Category and related modul deleted']);
    }
}
