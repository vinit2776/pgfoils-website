<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/** A section of the Investors Corner menu. Two levels. Hidden instead of deleted. */
#[Fillable(['parent_id', 'name', 'slug', 'sort_order', 'visible', 'group_by_year'])]
class Category extends Model
{
    use LogsActivity;

    protected function casts(): array
    {
        return ['visible' => 'boolean', 'group_by_year' => 'boolean'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function scopeVisible(Builder $q): Builder
    {
        return $q->where('visible', true);
    }

    public function scopeTopLevel(Builder $q): Builder
    {
        return $q->whereNull('parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function path(): string
    {
        return $this->parent ? $this->parent->slug.'/'.$this->slug : $this->slug;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['name', 'slug', 'parent_id', 'sort_order', 'visible', 'group_by_year'])
            ->logOnlyDirty()->dontLogEmptyChanges()->useLogName('investors');
    }
}
