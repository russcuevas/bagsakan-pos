<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Product;
use App\Models\ReceivingBatch;
use App\Models\Spoilage;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class AdminSpoilageController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function index(Request $request)
    {
        $query = Spoilage::with(['product', 'batch', 'warehouse', 'submitter', 'approver']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $spoilages = $query->latest('spoilage_date')->get();
        $products = Product::with('activeBatches')->where('is_active', true)->get();
        $warehouses = Warehouse::where('is_active', true)->get();

        $totalSpoilageCostMonth = Spoilage::where('status', 'approved')
            ->whereMonth('spoilage_date', now()->month)
            ->whereYear('spoilage_date', now()->year)
            ->sum('total_cost');

        return view('admin.spoilage.index', compact('spoilages', 'products', 'warehouses', 'totalSpoilageCostMonth'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'batch_id' => 'nullable|exists:receiving_batches,id',
            'quantity' => 'required|numeric|min:0.01',
            'spoilage_date' => 'required|date',
            'reason' => 'required|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'auto_approve' => 'nullable|boolean',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $filename = 'spoil_' . time() . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();
            $targetDir = public_path('uploads/spoilage');
            if (!File::exists($targetDir)) {
                File::makeDirectory($targetDir, 0755, true);
            }
            $photo->move($targetDir, $filename);
            $photoPath = 'uploads/spoilage/' . $filename;
        }

        $product = Product::findOrFail($validated['product_id']);
        $unitCost = (float) $product->average_cost;
        if (!empty($validated['batch_id'])) {
            $batch = ReceivingBatch::find($validated['batch_id']);
            if ($batch) {
                $unitCost = (float) $batch->unit_cost;
            }
        }
        $totalCost = $validated['quantity'] * $unitCost;

        $spoilageNumber = 'SPL-' . date('Ym') . '-' . strtoupper(substr(uniqid(), -5));

        $spoilage = Spoilage::create([
            'spoilage_number' => $spoilageNumber,
            'product_id' => $validated['product_id'],
            'batch_id' => $validated['batch_id'] ?? null,
            'warehouse_id' => $validated['warehouse_id'],
            'quantity' => $validated['quantity'],
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
            'spoilage_date' => $validated['spoilage_date'],
            'reason' => $validated['reason'],
            'photo_path' => $photoPath,
            'status' => 'pending',
            'submitted_by' => auth()->id(),
        ]);

        if (!empty($validated['auto_approve'])) {
            $this->inventoryService->approveSpoilage($spoilage, auth()->id());
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => !empty($validated['auto_approve']) ? 'Spoilage recorded and approved immediately!' : 'Spoilage submitted for approval!',
            ]);
        }

        return redirect()->route('admin.spoilage.index')->with('success', 'Spoilage entry recorded!');
    }

    public function approve(Request $request, Spoilage $spoilage)
    {
        try {
            $this->inventoryService->approveSpoilage($spoilage, auth()->id());

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Spoilage {$spoilage->spoilage_number} approved and stock loss deducted successfully!",
                ]);
            }

            return back()->with('success', 'Spoilage approved successfully.');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    public function reject(Request $request, Spoilage $spoilage)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:255',
        ]);

        $spoilage->status = 'rejected';
        $spoilage->rejection_reason = $validated['rejection_reason'];
        $spoilage->approved_by = auth()->id();
        $spoilage->approved_at = now();
        $spoilage->save();

        AuditLog::log('spoilage_rejected', Spoilage::class, $spoilage->id, null, ['reason' => $validated['rejection_reason']], 'Spoilage entry rejected');

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Spoilage entry rejected.',
            ]);
        }

        return back()->with('success', 'Spoilage entry rejected.');
    }
}
