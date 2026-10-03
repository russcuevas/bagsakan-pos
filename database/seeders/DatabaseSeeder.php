<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\InventoryMovement;
use App\Models\InvoiceAllocation;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Purchase;
use App\Models\PurchaseLine;
use App\Models\ReceivingBatch;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Models\SellingPriceHistory;
use App\Models\Spoilage;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Users for each role
        $admin = User::create([
            'name' => 'Worthy Acosta (Admin)',
            'username' => 'admin',
            'email' => 'admin@bagsakan.com',
            'role' => 'admin',
            'phone' => '09171234567',
            'status' => 'active',
            'password' => Hash::make('admin123'),
        ]);

        $purchasing = User::create([
            'name' => 'Maricel Santos (Purchasing Staff)',
            'username' => 'purchasing',
            'email' => 'purchasing@bagsakan.com',
            'role' => 'purchasing',
            'phone' => '09181234567',
            'status' => 'active',
            'password' => Hash::make('purchasing123'),
        ]);

        $cashier = User::create([
            'name' => 'Carlo Mendoza (Cashier)',
            'username' => 'cashier',
            'email' => 'cashier@bagsakan.com',
            'role' => 'cashier',
            'phone' => '09191234567',
            'status' => 'active',
            'password' => Hash::make('cashier123'),
        ]);

        // 2. Warehouses
        $whMain = Warehouse::create([
            'name' => 'Bagsakan Central Trading Hub',
            'code' => 'WH-MAIN',
            'location' => 'Building A, Trading Post, Balintawak',
            'is_active' => true,
        ]);

        $whCold = Warehouse::create([
            'name' => 'Cold Storage Facility 01',
            'code' => 'WH-COLD',
            'location' => 'Bay 4, Cold Chain Section',
            'is_active' => true,
        ]);

        // 3. Categories
        $catVeg = Category::create(['name' => 'Fresh Vegetables', 'code' => 'VEG', 'description' => 'Locally farmed vegetables from Benguet and Nueva Ecija']);
        $catPoultry = Category::create(['name' => 'Poultry & Eggs', 'code' => 'POULTRY', 'description' => 'Fresh table eggs and poultry goods']);
        $catRice = Category::create(['name' => 'Rice & Grains', 'code' => 'RICE', 'description' => 'Local and imported wholesale rice']);
        $catSpices = Category::create(['name' => 'Spices & Aromatics', 'code' => 'SPICE', 'description' => 'Onions, garlic, ginger, and chili']);
    }
}
