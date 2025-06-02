<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id', 
        'title',
        'description',
        'status',
        'priority',
        'due_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'due_date' => 'date', // Automatically cast due_date to a Carbon date object
    ];

    /**
     * Get the user that owns the task.
     * Defines an inverse one-to-many relationship.
     * Each task BELONGS TO one user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
