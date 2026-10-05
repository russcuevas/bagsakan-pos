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
        // 1. Add supplier_id to purchase_lines table to support multi-supplier POs
        if (Schema::hasTable('purchase_lines') && !Schema::hasColumn('purchase_lines', 'supplier_id')) {
            Schema::table('purchase_lines', function (Blueprint $table) {
                $table->foreignId('supplier_id')->nullable()->after('product_id')->constrained('suppliers')->nullOnDelete();
            });
        }

        // 2. Make supplier_id nullable on purchases table
        if (Schema::hasTable('purchases') && Schema::hasColumn('purchases', 'supplier_id')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->foreignId('supplier_id')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('purchase_lines') && Schema::hasColumn('purchase_lines', 'supplier_id')) {
            Schema::table('purchase_lines', function (Blueprint $table) {
                $table->dropForeign(['supplier_id']);
                $table->dropColumn('supplier_id');
            });
        }
    }
};
