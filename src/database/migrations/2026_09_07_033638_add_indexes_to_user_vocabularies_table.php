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
        Schema::table('user_vocabularies', function (Blueprint $table) {
            // Index để truy vấn thống kê nhanh (user + trạng thái học)
            $table->index(['user_id', 'status'], 'idx_user_vocabularies_user_status');

            // Unique constraint đảm bảo mỗi user chỉ có 1 record/từ
            $table->unique(['user_id', 'vocabulary_id'], 'uniq_user_vocabularies_user_vocab');

            // Index cho query từ gần đây nhất
            $table->index(['user_id', 'last_review_at'], 'idx_user_vocabularies_user_reviewed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_vocabularies', function (Blueprint $table) {
            $table->dropIndex('idx_user_vocabularies_user_status');
            $table->dropUnique('uniq_user_vocabularies_user_vocab');
            $table->dropIndex('idx_user_vocabularies_user_reviewed');
        });
    }
};

