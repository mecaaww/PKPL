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
        Schema::create('target_omzet', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('bulan');
            $table->unsignedSmallInteger('tahun');
            $table->unsignedBigInteger('target');
            $table->timestamps();

            $table->unique(['bulan', 'tahun']);
        });

        Schema::create('pengumuman_admin', function (Blueprint $table) {
            $table->id();
            $table->text('konten');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengumuman_admin');
        Schema::dropIfExists('target_omzet');
    }
};
