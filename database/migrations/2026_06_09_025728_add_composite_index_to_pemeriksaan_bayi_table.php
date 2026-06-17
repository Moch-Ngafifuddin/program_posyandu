<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pemeriksaan_bayi', function (Blueprint $table) {
            // 🟢 Index kombinasi untuk mempercepat query subquery MAX(id) + GROUP BY pasien_id
            $table->index(['pasien_id', 'id'], 'idx_pasien_id_pemeriksaan_id');
        });
    }

    public function down(): void
    {
        Schema::table('pemeriksaan_bayi', function (Blueprint $table) {
            $table->dropIndex('idx_pasien_id_pemeriksaan_id');
        });
    }
};