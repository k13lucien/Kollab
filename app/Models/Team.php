<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'label',
        'leader_id',
    ];

    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function members()
    {
        //  Doit être belongsToMany pour utiliser attach()
        return $this->belongsToMany(User::class, 'team_user')->withTimestamps();
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }
}
