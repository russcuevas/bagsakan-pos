<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            Schema::table('quotations', function (Blueprint $table) {
                $table->string('status', 50)->default('draft')->change();
            });
        } catch (\Throwable $e) {
            try {
                DB::statement("ALTER TABLE `quotations` MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'draft'");
            } catch (\Throwable $ex) {
                // Keep resilient if table already modified or driver different
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};

