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
        if (Schema::hasTable('quotations')) {
            Schema::table('quotations', function (Blueprint $table) {
                if (!Schema::hasColumn('quotations', 'customer_contact')) {
                    $table->string('customer_contact')->nullable()->after('customer_name');
                }
                if (!Schema::hasColumn('quotations', 'customer_phone')) {
                    $table->string('customer_phone')->nullable()->after('customer_name');
                }
                if (!Schema::hasColumn('quotations', 'customer_email')) {
                    $table->string('customer_email')->nullable()->after('customer_phone');
                }
                if (!Schema::hasColumn('quotations', 'customer_address')) {
                    $table->text('customer_address')->nullable()->after('customer_contact');
                }
                if (Schema::hasColumn('quotations', 'user_id')) {
                    $table->foreignId('user_id')->nullable()->change();
                }
                if (Schema::hasColumn('quotations', 'customer_name')) {
                    $table->string('customer_name')->nullable()->change();
                }
                if (!Schema::hasColumn('quotations', 'remarks')) {
                    $table->text('remarks')->nullable()->after('status');
                }
                if (!Schema::hasColumn('quotations', 'notes')) {
                    $table->text('notes')->nullable()->after('remarks');
                }
                if (!Schema::hasColumn('quotations', 'prepared_by')) {
                    $table->foreignId('prepared_by')->nullable()->after('remarks')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('quotations', 'user_id')) {
                    $table->foreignId('user_id')->nullable()->after('prepared_by')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('quotations', 'converted_sale_id')) {
                    $table->foreignId('converted_sale_id')->nullable()->after('prepared_by')->constrained('sales')->nullOnDelete();
                }
                if (!Schema::hasColumn('quotations', 'converted_at')) {
                    $table->dateTime('converted_at')->nullable()->after('converted_sale_id');
                }
            });
        }

        if (Schema::hasTable('quotation_items')) {
            Schema::table('quotation_items', function (Blueprint $table) {
                if (!Schema::hasColumn('quotation_items', 'conversion_factor')) {
                    $table->decimal('conversion_factor', 12, 4)->default(1)->after('unit_name');
                }
                if (!Schema::hasColumn('quotation_items', 'notes')) {
                    $table->text('notes')->nullable()->after('total');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('quotations')) {
            Schema::table('quotations', function (Blueprint $table) {
                // Drop added columns if necessary
            });
        }
    }
};
