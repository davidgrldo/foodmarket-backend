<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class MsFood extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'description', 'ingredients', 'price', 'rate', 'types', 'picture_path'
    ];

    public function getAttributeCreatedAt($value)
    {
        return Carbon::parse($value)->timestamp;
    }

    public function getAttributeUpdatedAt($value)
    {
        return Carbon::parse($value)->timestamp;
    }

    public function getPicturePathAtribute()
    {
        return url('') . Storage::url($this->attributes['picture_path']);
    }
}
