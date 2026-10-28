<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['repair_job_id', 'json', 'valid', 'errors'])]
class RepairedJson extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'valid' => 'boolean',
            'errors' => 'array',
        ];
    }

    /** @return BelongsTo<RepairJob, $this> */
    public function repairJob(): BelongsTo
    {
        return $this->belongsTo(RepairJob::class);
    }
}
