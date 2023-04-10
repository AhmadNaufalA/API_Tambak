<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Temp extends Model
{
    use HasFactory;

    protected $table = "temp6";
    public $timestamps = false;
    protected $fillable = [
        'pH',
        'Suhu',
        'TDS',
        //'Ketinggian',
        'Oksigen',
        'Kekeruhan',
    ];
}