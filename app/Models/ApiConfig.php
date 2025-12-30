<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiConfig extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'api_name',
        'api_key',
        'user_id',
    ];

    /**
     * Get the user that owns the API config.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
