<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Property extends Model
{
    protected $fillable = ['user_id', 'name', 'location', 'description'];

    protected static function booted(): void
    {
        static::addGlobalScope('owner', function ($query){
            if (Auth::check()) {
                $query->where('user_id', Auth::id());
            }
        });

        static::creating(function (Property $property){
            if(Auth::check() && empty($property->user_id)){
                $property->user_id = Auth::id();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }
}
