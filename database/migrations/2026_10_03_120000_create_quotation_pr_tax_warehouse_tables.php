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
        // 1. Quotations Table
        if (!Schema::hasTable('quotations')) {
            Schema::create('quotations', function (Blueprint $table) {
                $table->id();
                $table->string('quotation_number')->unique(); // e.g. QTN-202610-0001
                $table->date('quotation_date');
                $table->date('valid_until')->nullable();
                $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
                $table->string('customer_name')->nullable();
                $table->string('customer_contact')->nullable();
                $table->text('customer_address')->nullable();
                $table->decimal('subtotal', 14, 2)->default(0);
                $table->decimal('discount_amount', 14, 2)->default(0);
                $table->decimal('tax_amount', 14, 2)->default(0);
                $table->decimal('total_amount', 14, 2)->default(0);
                $table->enum('status', ['draft', 'sent', 'accepted', 'rejected', 'expired', 'converted'])->default('draft');
                $table->text('remarks')->nullable();
                $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('converted_sale_id')->nullable()->constrained('sales')->nullOnDelete();
                $table->dateTime('converted_at')->nullable();
                $table->timestamps();
            });
        }

        // 2. Quotation Items Table
        if (!Schema::hasTable('quotation_items')) {
            Schema::create('quotation_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->string('sku');
                $table->string('description');
                $table->string('unit_name');
                $table->decimal('conversion_factor', 12, 4)->default(1);
                $table->decimal('quantity', 12, 4)->default(1);
                $table->decimal('unit_price', 14, 2)->default(0);
                $table->decimal('discount', 12, 2)->default(0);
                $table->decimal('tax', 12, 2)->default(0);
                $table->decimal('total', 14, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 3. Purchase Requests (PR) Table
        if (!Schema::hasTable('purchase_requests')) {
            Schema::create('purchase_requests', function (Blueprint $table) {
                $table->id();
                $table->string('pr_number')->unique(); // e.g. PR-202610-0001
                $table->date('request_date');
                $table->date('needed_by_date')->nullable();
                $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
                $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'returned', 'converted_to_po'])->default('draft');
                $table->decimal('total_estimated_amount', 14, 2)->default(0);
                $table->text('purpose')->nullable();
                $table->text('remarks')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->text('revision_notes')->nullable();
                $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('approved_at')->nullable();
                $table->timestamps();
            });
        }

        // 4. Purchase Request Items (Supports Multi-Supplier PR!)
        if (!Schema::hasTable('purchase_request_items')) {
            Schema::create('purchase_request_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_request_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
                $table->string('unit_name');
                $table->decimal('conversion_factor', 12, 4)->default(1);
                $table->decimal('quantity', 12, 4)->default(1);
                $table->decimal('estimated_unit_cost', 14, 4)->default(0);
                $table->decimal('estimated_subtotal', 14, 2)->default(0);
                // Captures original requested values for audit trail
                $table->decimal('original_quantity', 12, 4)->nullable();
                $table->decimal('original_unit_cost', 14, 4)->nullable();
                $table->foreignId('original_supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
                $table->text('remarks')->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->foreignId('generated_purchase_id')->nullable()->constrained('purchases')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 5. Add purchase_request_id to Purchases table
        if (Schema::hasTable('purchases') && !Schema::hasColumn('purchases', 'purchase_request_id')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->foreignId('purchase_request_id')->nullable()->after('purchase_number')->constrained('purchase_requests')->nullOnDelete();
            });
        }

        // 6. Tax / Withholding Tax additions on Payments and Allocations
        if (Schema::hasTable('payments') && !Schema::hasColumn('payments', 'tax_withheld')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->decimal('tax_withheld', 14, 2)->default(0)->after('amount');
                $table->string('tax_type')->nullable()->after('tax_withheld'); // e.g. "Creditable Withholding Tax (BIR 2307 - 1%)"
                $table->string('tax_doc_number')->nullable()->after('tax_type'); // e.g. 2307 Form #
            });
        }

        if (Schema::hasTable('invoice_allocations') && !Schema::hasColumn('invoice_allocations', 'tax_allocated')) {
            Schema::table('invoice_allocations', function (Blueprint $table) {
                $table->decimal('tax_allocated', 14, 2)->default(0)->after('allocated_amount');
            });
        }

        // 7. Warehouse Transfers (Multi-Warehouse transfer support)
        if (!Schema::hasTable('warehouse_transfers')) {
            Schema::create('warehouse_transfers', function (Blueprint $table) {
                $table->id();
                $table->string('transfer_number')->unique(); // e.g. TRF-202610-0001
                $table->foreignId('from_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
                $table->foreignId('to_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
                $table->date('transfer_date');
                $table->enum('status', ['pending', 'completed', 'cancelled'])->default('completed');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('warehouse_transfer_items')) {
            Schema::create('warehouse_transfer_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('transfer_id')->constrained('warehouse_transfers')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('batch_id')->nullable()->constrained('receiving_batches')->nullOnDelete();
                $table->string('unit_name');
                $table->decimal('conversion_factor', 12, 4)->default(1);
                $table->decimal('quantity', 12, 4); // in selected unit
                $table->decimal('base_quantity', 12, 4); // in base unit
                $table->decimal('unit_cost', 14, 4)->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_transfer_items');
        Schema::dropIfExists('warehouse_transfers');
        
        Schema::table('invoice_allocations', function (Blueprint $table) {
            $table->dropColumn('tax_allocated');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['tax_withheld', 'tax_type', 'tax_doc_number']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['purchase_request_id']);
            $table->dropColumn('purchase_request_id');
        });

        Schema::dropIfExists('purchase_request_items');
        Schema::dropIfExists('purchase_requests');
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
    }
};
