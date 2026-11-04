<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanPrice extends Model
{
    protected $fillable = ['plan_id', 'months', 'price'];
}
