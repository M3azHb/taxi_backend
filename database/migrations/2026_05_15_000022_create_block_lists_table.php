<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('block_lists', function (Blueprint $table) {
            $table->id();
            $table->morphs('blocker');
            $table->morphs('blocked');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->unique(
                ['blocker_id', 'blocker_type', 'blocked_id', 'blocked_type'],
                'block_lists_unique_pair'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('block_lists');
    }
};
