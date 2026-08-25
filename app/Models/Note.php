<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'content', 'embedding', 'summary'];

    /** Internal search vector; never expose implementation details through the public API. */
    protected $hidden = ['embedding'];

    protected function casts(): array
    {
        return ['embedding' => 'array'];
    }
}
