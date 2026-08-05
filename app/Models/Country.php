<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $fillable = ["name", "code", "phone_code"];

    public function States()
    {
        return $this->hasMany(State::class);
    }
}
