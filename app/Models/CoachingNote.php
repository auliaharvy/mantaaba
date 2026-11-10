<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

/** Catatan pembinaan (PRD 9.5). author_id null = dibuat sistem. */
class CoachingNote extends Model
{
    use LogsActivity;

    public const TYPES = ['positif' => 'Positif', 'perhatian' => 'Perhatian', 'pelanggaran_ringan' => 'Pelanggaran ringan', 'pelanggaran_sedang' => 'Pelanggaran sedang', 'pelanggaran_berat' => 'Pelanggaran berat'];

    public const TYPE_COLORS = ['positif' => 'success', 'perhatian' => 'warning', 'pelanggaran_ringan' => 'light-danger', 'pelanggaran_sedang' => 'danger', 'pelanggaran_berat' => 'dark'];

    /** Dimensi => dimensi penilaian 4D yang memakainya (M3). */
    public const DIMENSIONS = ['disiplin' => 'Disiplin', 'kejujuran' => 'Kejujuran', 'adab' => 'Adab', 'tanggung_jawab' => 'Tanggung jawab', 'kontribusi_tim' => 'Kontribusi tim', 'inisiatif' => 'Inisiatif'];

    public const VISIBILITIES = ['privat' => 'Hanya pengurus', 'anggota' => 'Terlihat oleh anggota'];

    protected $fillable = ['member_id', 'author_id', 'date', 'type', 'dimension', 'body', 'visibility', 'follow_up'];

    protected $casts = ['date' => 'date'];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
