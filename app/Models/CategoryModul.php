<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *   schema="CategoryModul",
 *   type="object",
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="nama", type="string", example="Design Product"),
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class CategoryModul extends Model
{
    protected $table = 'category_modul';
    protected $fillable = ['nama'];

    public function moduls()
    {
        return $this->hasMany(Modul::class, 'category_modul_id');
    }
}
