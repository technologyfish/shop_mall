<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactInfo extends Model
{
    protected $table = 'contact_info';
    
    protected $fillable = [
        'key', 'value', 'label', 'type', 'sort'
    ];

    protected $casts = [
        'sort' => 'integer',
    ];
}





