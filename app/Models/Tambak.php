<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Tambak extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $table = "tambak";
    public $timestamps = false;
    protected $fillable = [
        'name',
        'desc',
        'pH',
        'Suhu',
        'Salinitas',
        'Ketinggian',
        'Oksigen',
        'Kekeruhan',
        'id_user'
    ];

    public function users()
    {
        return $this->belongsTo(User::class);
    }
    // public function kualitasAirs()
    // {
    //     return $this->hasMany(KualitasAir::class, "id", "id_tambak");
    // }
}

