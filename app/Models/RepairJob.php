<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['raw_text', 'status'])]
class RepairJob extends Model
{
    /** @return HasOne<RepairedJson, $this> */
    public function repairedJson(): HasOne
    {
        return $this->hasOne(RepairedJson::class);
    }
}
