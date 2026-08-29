<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BestSeller extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id',
        'list_name',
        'rank',
        'week_date',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'week_date' => 'date',
            'rank' => 'integer',
        ];
    }

    /**
     * The book that belongs to the best seller record.
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
