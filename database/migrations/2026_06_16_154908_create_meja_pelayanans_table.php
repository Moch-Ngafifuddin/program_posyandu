<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('meja_pelayanan', function (Blueprint $table) {
            $table->id();
            $table->string('kode_meja')->unique();
            $table->string('nama_meja');
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meja_pelayanans');
    }
};
