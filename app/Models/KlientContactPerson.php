<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KlientContactPerson extends Model
{
    use HasFactory;

    protected $table = 'klient_contact_persons';

    protected $fillable = [
        'klient_id', 'full_name', 'position', 'role', 'phone', 'email', 'is_primary'
    ];
}
