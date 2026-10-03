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
        // 1. Categories
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 2. Warehouses / Storage Locations
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('location')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Suppliers
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('mobile_number')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('tin')->nullable();
            $table->string('payment_terms')->default('Cash'); // Cash, 7 Days, 15 Days, 30 Days, etc.
            $table->text('bank_info')->nullable();
            $table->decimal('outstanding_balance', 14, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. Products (One SKU across suppliers, with Base Inventory Unit and Average Cost)
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('barcode')->nullable()->unique();
            $table->string('name');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('base_unit')->default('kg'); // kg, pc, tray, pack, etc.
            $table->decimal('default_srp', 12, 2)->default(0); // Base unit default SRP
            $table->enum('costing_method', ['weighted_average', 'fifo'])->default('weighted_average');
            $table->decimal('average_cost', 14, 4)->default(0); // Protected buying cost (weighted average per base unit)
            $table->decimal('low_stock_threshold', 12, 2)->default(10);
            $table->string('image_path')->nullable(); // stored in public/uploads/products/
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 5. Product Units (Wholesale & Retail Conversion Factors, e.g., 1 Tray = 30 pcs)
        Schema::create('product_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('unit_name'); // Tray, Box, Sack, Piece, kg, Pack
            $table->decimal('conversion_factor', 12, 4)->default(1); // multiplier to convert this unit into base unit
            $table->decimal('srp', 12, 2)->default(0); // Unit specific SRP
            $table->boolean('is_default_selling')->default(false);
            $table->boolean('is_default_purchasing')->default(false);
            $table->timestamps();
        });

        // 6. Product-Supplier Pivot (Links SKU with Supplier and tracks last buying cost)
        Schema::create('product_supplier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('supplier_product_code')->nullable();
            $table->decimal('last_purchase_cost', 14, 4)->default(0);
            $table->timestamps();
        });

        // 7. Purchases (Purchase Orders & Buying Invoices)
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_number')->unique(); // e.g. PO-202610-0001
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_dr_number')->nullable(); // DR or Supplier Invoice #
            $table->date('purchase_date');
            $table->string('payment_terms')->nullable();
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            $table->enum('status', ['draft', 'ordered', 'received', 'cancelled'])->default('ordered');
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 8. Purchase Lines
        Schema::create('purchase_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('unit_name');
            $table->decimal('conversion_factor', 12, 4)->default(1);
            $table->decimal('quantity_ordered', 12, 4)->default(0);
            $table->decimal('quantity_received', 12, 4)->default(0);
            $table->decimal('unit_cost', 14, 4)->default(0); // Cost per purchased unit
            $table->decimal('base_cost', 14, 4)->default(0); // Cost per base unit = unit_cost / conversion_factor
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->timestamps();
        });

        // 9. Receiving Batches / Lots (Maintains historical batches per supplier and purchase cost)
        Schema::create('receiving_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_code')->unique(); // e.g. BATCH-20261002-001
            $table->foreignId('purchase_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('purchase_line_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->date('receipt_date');
            $table->string('invoice_dr_number')->nullable();
            $table->decimal('initial_quantity', 14, 4)->default(0); // In base unit
            $table->decimal('current_quantity', 14, 4)->default(0); // In base unit
            $table->decimal('unit_cost', 14, 4)->default(0); // Buying cost per base unit
            $table->enum('status', ['active', 'depleted', 'expired'])->default('active');
            $table->timestamps();
        });

        // 10. Inventory Movements (Full movement traceability)
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('receiving_batches')->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('movement_type'); // purchase_receipt, pos_sale, spoilage, adjustment_in, adjustment_out, sale_void
            $table->string('reference_type')->nullable(); // purchase, sale, spoilage, adjustment
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('quantity_change', 14, 4); // In base unit (positive or negative)
            $table->decimal('unit_cost', 14, 4)->default(0); // Protected buying cost at movement
            $table->decimal('unit_price', 14, 2)->nullable(); // Selling price if sale
            $table->decimal('stock_before', 14, 4)->default(0);
            $table->decimal('stock_after', 14, 4)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 11. Selling Price Histories (Tracks every SRP change, who changed it, and reason)
        Schema::create('selling_price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('unit_name')->default('Base Unit');
            $table->decimal('old_srp', 12, 2);
            $table->decimal('new_srp', 12, 2);
            $table->dateTime('effective_date');
            $table->string('reason')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 12. Customers (Retail & Wholesale AR Clients)
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('business_name')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->decimal('credit_limit', 14, 2)->default(0);
            $table->decimal('current_balance', 14, 2)->default(0); // AR balance
            $table->integer('payment_terms_days')->default(0); // 0 = Cash, 30 = 30 Days
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 13. Sales / POS Transactions
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('sale_number')->unique(); // e.g. SI-00158 or POS-202610-001
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('sale_date');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->string('discount_type')->default('none'); // none, fixed, percentage
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->string('discount_reason')->nullable();
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->decimal('total_cogs', 14, 4)->default(0); // Protected total cost of goods sold
            $table->enum('payment_method', ['cash', 'gcash', 'bank_transfer', 'credit', 'mixed'])->default('cash');
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->decimal('change_amount', 14, 2)->default(0);
            $table->enum('payment_status', ['paid', 'partial', 'unpaid'])->default('paid');
            $table->date('due_date')->nullable(); // For credit terms
            $table->string('payment_terms')->nullable(); // e.g. 30-day term
            $table->string('reference_number')->nullable(); // GCash or Bank ref
            $table->enum('status', ['completed', 'voided'])->default('completed');
            $table->string('void_reason')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('voided_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 14. Sale Lines
        Schema::create('sale_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('receiving_batches')->nullOnDelete();
            $table->string('unit_name');
            $table->decimal('conversion_factor', 12, 4)->default(1);
            $table->decimal('quantity', 12, 4); // In selected unit (e.g. 2 trays or 1.5 kg)
            $table->decimal('base_quantity', 12, 4); // In base unit = quantity * conversion_factor
            $table->decimal('unit_price', 14, 2); // Selling price charged
            $table->decimal('cost_price', 14, 4)->default(0); // Protected unit cost at sale
            $table->decimal('line_cogs', 14, 4)->default(0); // Protected base_quantity * cost_price
            $table->decimal('line_discount', 12, 2)->default(0);
            $table->decimal('subtotal', 14, 2);
            $table->timestamps();
        });

        // 15. Payments & Collections
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->unique(); // e.g. PAY-202610-001
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['customer_collection', 'supplier_payment'])->default('customer_collection');
            $table->date('payment_date');
            $table->enum('payment_method', ['cash', 'gcash', 'bank_transfer', 'check'])->default('cash');
            $table->string('reference_number')->nullable();
            $table->decimal('amount', 14, 2);
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 16. Invoice Allocations (Linking payments to sales or purchases)
        Schema::create('invoice_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('purchase_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('allocated_amount', 14, 2);
            $table->timestamps();
        });

        // 17. Spoilage / Wastage
        Schema::create('spoilages', function (Blueprint $table) {
            $table->id();
            $table->string('spoilage_number')->unique(); // e.g. SPL-202610-001
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('receiving_batches')->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 12, 4); // In base unit
            $table->decimal('unit_cost', 14, 4)->default(0); // Cost per base unit
            $table->decimal('total_cost', 14, 2)->default(0); // Loss amount
            $table->date('spoilage_date');
            $table->string('reason');
            $table->string('photo_path')->nullable(); // stored in public/uploads/spoilage/
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamps();
        });

        // 18. Operating Expenses
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_number')->unique(); // e.g. EXP-202610-001
            $table->string('category'); // Utilities, Rent, Logistics/Fuel, Labor, Packaging, Supplies, Maintenance
            $table->decimal('amount', 14, 2);
            $table->date('expense_date');
            $table->string('payee');
            $table->enum('payment_method', ['cash', 'gcash', 'bank_transfer'])->default('cash');
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 19. Stock Adjustments (Manual audit corrections)
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_number')->unique();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('receiving_batches')->nullOnDelete();
            $table->enum('type', ['increase', 'decrease']);
            $table->decimal('quantity', 12, 4);
            $table->decimal('unit_cost', 14, 4)->default(0);
            $table->string('reason');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 20. Audit Trail / Activity Logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action'); // price_change, spoilage_approved, sale_void, discount_override, etc.
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('before_values')->nullable();
            $table->json('after_values')->nullable();
            $table->text('reason')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('spoilages');
        Schema::dropIfExists('invoice_allocations');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('sale_lines');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('selling_price_histories');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('receiving_batches');
        Schema::dropIfExists('purchase_lines');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('product_supplier');
        Schema::dropIfExists('product_units');
        Schema::dropIfExists('products');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('categories');
    }
};
