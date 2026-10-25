<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Scope extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'target',
        'asset_type',
        'scope_status',
        'criticality_weight',
        'description',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'criticality_weight' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function isInScope(): bool
    {
        return $this->scope_status === 'in_scope';
    }
}
