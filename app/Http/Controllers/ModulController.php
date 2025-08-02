<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Modul;
use Illuminate\Support\Facades\Storage;

class ModulController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/modul",
     *     summary="List semua modul dengan data kategori",
     *     tags={"Modul"},
     *     security={{ "sanctum":{ }}},
     *     @OA\Response(
     *         response=200,
     *         description="List modul dengan data kategori",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Modul"))
     *     )
     * )
     */
    public function index()
    {
        return response()->json(Modul::with('categoryModul')->get());
    }

    /**
     * @OA\Post(
     *     path="/api/modul",
     *     summary="Tambah modul baru (admin only, role_id=1)",
     *     tags={"Modul"},
     *     security={{ "sanctum":{ }}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"judul_modul","category_modul_id","link_video","deskripsi_modul"},
     *                 @OA\Property(property="judul_modul", type="string", example="Dasar Desain Produk"),
     *                 @OA\Property(property="category_modul_id", type="integer", example=2),
     *                 @OA\Property(property="link_video", type="string", example="https://youtu.be/xxxx"),
     *                 @OA\Property(property="pdf", type="file", format="binary"),
     *                 @OA\Property(property="foto", type="file", format="binary"),
     *                 @OA\Property(property="deskripsi_modul", type="string", example="Penjelasan modul...")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Modul berhasil dibuat",
     *         @OA\JsonContent(ref="#/components/schemas/Modul")
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'judul_modul' => 'required|string|max:255',
            'category_modul_id' => 'required|exists:category_modul,id',
            'link_video' => 'required|string|max:255',
            'pdf' => 'nullable|file|mimes:pdf|max:51200',
            'foto' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'deskripsi_modul' => 'required|string',
        ]);

        $pathPdf = null;
        if ($request->hasFile('pdf')) {
            $pathPdf = $request->file('pdf')->store('modul_pdfs', 'public');
        }

        $pathFoto = null;
        if ($request->hasFile('foto')) {
            $pathFoto = $request->file('foto')->store('modul_fotos', 'public');
        }

        $modul = Modul::create([
            'judul_modul' => $request->judul_modul,
            'category_modul_id' => $request->category_modul_id,
            'link_video' => $request->link_video,
            'path_pdf' => $pathPdf ? 'storage/' . $pathPdf : null,
            'foto' => $pathFoto ? 'storage/' . $pathFoto : null,
            'deskripsi_modul' => $request->deskripsi_modul,
        ]);

        // Load relationship setelah create
        $modul->load('categoryModul');

        return response()->json($modul, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/modul/{id}",
     *     summary="Detail modul dengan data kategori",
     *     tags={"Modul"},
     *     security={{ "sanctum":{ }}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detail modul dengan data kategori",
     *         @OA\JsonContent(ref="#/components/schemas/Modul")
     *     ),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show($id)
    {
        $modul = Modul::with('categoryModul')->findOrFail($id);
        return response()->json($modul);
    }

    /**
     * @OA\Post(
     *     path="/api/modul/{id}",
     *     summary="Update modul (admin only, role_id=1)",
     *     tags={"Modul"},
     *     security={{ "sanctum":{ }}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="judul_modul", type="string", example="Dasar Desain Produk"),
     *                 @OA\Property(property="category_modul_id", type="integer", example=2),
     *                 @OA\Property(property="link_video", type="string", example="https://youtu.be/xxxx"),
     *                 @OA\Property(property="pdf", type="file", format="binary"),
     *                 @OA\Property(property="foto", type="file", format="binary"),
     *                 @OA\Property(property="deskripsi_modul", type="string", example="Penjelasan modul...")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Modul berhasil diupdate",
     *         @OA\JsonContent(ref="#/components/schemas/Modul")
     *     ),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(Request $request, $id)
    {
        $modul = Modul::findOrFail($id);
        $request->validate([
            'judul_modul' => 'sometimes|required|string|max:255',
            'category_modul_id' => 'sometimes|required|exists:category_modul,id',
            'link_video' => 'sometimes|required|string|max:2048',
            'pdf' => 'nullable|file|mimes:pdf|max:51200',
            'foto' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'deskripsi_modul' => 'sometimes|required|string',
        ]);

        $data = $request->only(['judul_modul','category_modul_id','link_video','deskripsi_modul']);
        if ($request->hasFile('pdf')) {
            // Hapus file lama jika ada
            if ($modul->path_pdf) {
                $oldPath = str_replace('storage/', '', $modul->path_pdf);
                Storage::disk('public')->delete($oldPath);
            }
            $data['path_pdf'] = 'storage/' . $request->file('pdf')->store('modul_pdfs', 'public');
        }
        if ($request->hasFile('foto')) {
            // Hapus file lama jika ada
            if ($modul->foto) {
                $oldPath = str_replace('storage/', '', $modul->foto);
                Storage::disk('public')->delete($oldPath);
            }
            $data['foto'] = 'storage/' . $request->file('foto')->store('modul_fotos', 'public');
        }
        $modul->update($data);
        
        // Load relationship setelah update
        $modul->load('categoryModul');
        
        return response()->json($modul);
    }

    /**
     * @OA\Delete(
     *     path="/api/modul/{id}",
     *     summary="Hapus modul (admin only, role_id=1)",
     *     tags={"Modul"},
     *     security={{ "sanctum":{ }}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Modul berhasil dihapus",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Modul deleted"))
     *     ),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy($id)
    {
        $modul = Modul::with('categoryModul')->findOrFail($id);
        if ($modul->path_pdf) {
            $oldPath = str_replace('storage/', '', $modul->path_pdf);
            Storage::disk('public')->delete($oldPath);
        }
        if ($modul->foto) {
            $oldPath = str_replace('storage/', '', $modul->foto);
            Storage::disk('public')->delete($oldPath);
        }
        $modul->delete();
        return response()->json(['message' => 'Modul deleted']);
    }
}
