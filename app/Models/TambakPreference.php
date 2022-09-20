<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;

class TambakPreference extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $table = "tambak_preference";
    public $timestamps = false;
    protected $fillable = [
        'id_tambak',
        'waktu',
        'pH',
        'Suhu',
        'Salinitas',
        'Ketinggian',
        'Oksigen',
        'Kekeruhan',
    ];
}
