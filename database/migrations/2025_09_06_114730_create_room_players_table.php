<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('room_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->onDelete('cascade');
            $table->string('session_id');
            $table->string('name');
            $table->string('symbol', 1)->nullable();
            $table->integer('score')->default(0);
            $table->boolean('is_spectator')->default(false);
            $table->timestamp('last_active')->useCurrent();
            $table->timestamps();

            $table->index(['room_id', 'session_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_players');
    }
};
