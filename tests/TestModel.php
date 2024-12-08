<?php

namespace afantecsf\LivewireSelect3\Tests;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
    use HasFactory;

    protected $fillable = ['id', 'name', 'parent_id', 'active'];
    protected $guarded = [];

    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', "%{$search}%");
    }

    public static function activeFilter($query)
    {
        return $query->where('active', true);
    }
}