<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Tambak;

class User extends Model
{
    use HasFactory;

    protected $table = "user";
    public $timestamps = false;
    protected $fillable = [
        'username',
        'nama',
        'password',
        'secret_question',
        'secret_answer'
    ];
    protected $hidden = ['password'];
    public function tambaks()
    {
        return $this->hasMany(Tambak::class);
    }
}
