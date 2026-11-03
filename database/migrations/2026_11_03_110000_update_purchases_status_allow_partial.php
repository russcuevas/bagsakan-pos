<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('purchases') && Schema::hasColumn('purchases', 'status')) {
            try {
                DB::statement("ALTER TABLE purchases MODIFY status VARCHAR(50) NOT NULL DEFAULT 'ordered'");
            } catch (\Throwable $e) {
                Schema::table('purchases', function (Blueprint $table) {
                    $table->string('status', 50)->default('ordered')->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('purchases') && Schema::hasColumn('purchases', 'status')) {
            try {
                DB::statement("ALTER TABLE purchases MODIFY status ENUM('draft', 'ordered', 'received', 'cancelled') NOT NULL DEFAULT 'ordered'");
            } catch (\Throwable $e) {
                // Ignore rollback failure if enum strict
            }
        }
    }
};

