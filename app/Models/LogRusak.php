<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogRusak extends Model
{
    use HasFactory;

    protected $table = "log_rusak";
    public $timestamps = false;
    protected $fillable = [
        'id_tambak',
        'isi',
    ];
}