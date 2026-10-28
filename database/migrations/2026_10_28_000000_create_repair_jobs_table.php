<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_jobs', function (Blueprint $table): void {
            $table->id();
            $table->longText('raw_text');
            $table->string('status', 24)->default('pending');
            $table->timestamps();
        });

        Schema::create('repaired_jsons', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('repair_job_id')->unique()->constrained()->cascadeOnDelete();
            $table->longText('json')->nullable();
            $table->boolean('valid')->default(false);
            $table->json('errors');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repaired_jsons');
        Schema::dropIfExists('repair_jobs');
    }
};
