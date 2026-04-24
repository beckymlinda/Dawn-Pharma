<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'is_default',
        'is_superadmin'
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_superadmin' => 'boolean'
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function givePermissionTo($permission)
    {
        $this->permissions()->syncWithoutDetaching($permission);
        return $this;
    }

    public function revokePermissionTo($permission)
    {
        $this->permissions()->detach($permission);
        return $this;
    }

    public function hasPermission($permission): bool
    {
        if ($this->is_superadmin) {
            return true;
        }

        return $this->permissions()->where('name', $permission)->exists();
    }
}
