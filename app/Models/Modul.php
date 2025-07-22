<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *   schema="Modul",
 *   type="object",
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="judul_modul", type="string", example="Dasar Desain Produk"),
 *   @OA\Property(property="category_modul_id", type="integer", example=2),
 *   @OA\Property(property="link_video", type="string", example="https://youtu.be/xxxx"),
 *   @OA\Property(property="path_pdf", type="string", example="storage/modul_pdfs/abc.pdf"),
 *   @OA\Property(property="foto", type="string", example="storage/modul_fotos/abc.jpg"),
 *   @OA\Property(property="deskripsi_modul", type="string", example="Penjelasan modul..."),
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time"),
 *   @OA\Property(property="category_modul", ref="#/components/schemas/CategoryModul")
 * )
 */
class Modul extends Model
{
    protected $table = 'modul';
    protected $fillable = [
        'judul_modul',
        'category_modul_id',
        'link_video',
        'path_pdf',
        'foto',
        'deskripsi_modul',
    ];

    public function categoryModul()
    {
        return $this->belongsTo(CategoryModul::class, 'category_modul_id');
    }
}
