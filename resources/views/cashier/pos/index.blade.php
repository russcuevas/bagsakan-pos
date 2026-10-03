@extends('layouts.cashier')

@section('content')
<!-- Side Arrow Cart Drawer Trigger (Mobile/Tablet <= 1024px) -->
<button type="button" class="pos-cart-drawer-trigger" id="posCartDrawerTrigger" onclick="toggleCartDrawer(true)" title="Open Current Order">
    <i class="bi bi-chevron-left drawer-arrow-icon"></i>
    <div class="drawer-cart-meta">
        <i class="bi bi-cart3" style="font-size: 1.15rem;"></i>
        <span class="drawer-cart-badge" id="sideCartBadge">0</span>
    </div>
</button>

<!-- Backdrop overlay for Cart Drawer on smaller devices -->
<div class="pos-cart-backdrop" id="posCartBackdrop" onclick="toggleCartDrawer(false)"></div>

<div class="pos-layout">
    <!-- Left: Product Catalog & Search Area -->
    <div class="pos-products-area">
        <!-- Top Search Bar -->
        <div class="pos-search-bar">
            <div style="flex: 1; position: relative;">
                <i class="bi bi-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-light); font-size: 1.1rem;"></i>
                <input type="text" id="posSearchInput" class="form-control form-control-lg" style="padding-left: 42px; font-size: 0.95rem; border-radius: var(--radius-sm);" placeholder="Search SKU, Name, or Barcode..." autofocus>
            </div>
            <button type="button" class="btn btn-outline" onclick="loadPosProducts(document.getElementById('posSearchInput').value.trim(), selectedCategoryId, 1, false)" title="Refresh Product Catalog">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>

        <!-- Category Filter Pills Bar -->
        <div class="category-pills-bar">
            <button type="button" class="category-pill active" data-category="" onclick="filterCategory(this, '')">
                <i class="bi bi-grid-fill"></i> All Items
                <span class="badge-count" id="countAll">{{ $totalAllProducts ?? 0 }}</span>
            </button>
            @foreach($categories as $cat)
                <button type="button" class="category-pill" data-category="{{ $cat->id }}" onclick="filterCategory(this, '{{ $cat->id }}')">
                    {{ $cat->name }}
                    <span class="badge-count" id="countCat_{{ $cat->id }}">{{ $cat->products_count ?? 0 }}</span>
                </button>
            @endforeach
        </div>

        <!-- Product Cards Grid Container -->
        <div id="posProductGrid" class="pos-grid-container">
            <!-- Populated via JavaScript -->
        </div>
    </div>

    <!-- Right: Active Cart Pane & Checkout -->
    <div class="pos-cart-pane">
        <!-- Cart Header -->
        <div class="pos-cart-header">
            <div style="display: flex; align-items: center; gap: 8px;">
                <button type="button" class="btn btn-sm btn-outline d-lg-none" onclick="toggleCartDrawer(false)" title="Hide Cart" style="padding: 4px 8px; border-radius: 6px;">
                    <i class="bi bi-arrow-right" style="font-size: 1.1rem; font-weight: 800;"></i>
                </button>
                <div>
                    <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--color-deep-navy); margin-bottom: 2px;">
                        <i class="bi bi-cart3 text-primary me-1"></i> Current Order
                    </h3>
                    <div style="font-size: 0.74rem; color: var(--text-light);" id="cartItemCountDisplay">0 items in cart</div>
                </div>
            </div>
            <div style="display: flex; gap: 6px;">
                <button type="button" class="btn btn-sm btn-outline" onclick="openHeldOrdersModal()" title="View Held Orders" style="position: relative;">
                    <i class="bi bi-pause-circle"></i>
                    <span id="heldOrdersBadge" style="display: none; position: absolute; top: -6px; right: -6px; background: var(--danger-red); color: #fff; border-radius: 50%; font-size: 0.65rem; width: 18px; height: 18px; line-height: 18px; text-align: center; font-weight: 800;">0</span>
                </button>
                <button type="button" class="btn btn-sm btn-outline" onclick="holdCurrentSale()" title="Hold / Park Sale" id="holdSaleBtn">
                    <i class="bi bi-bookmark-plus"></i> Hold
                </button>
                <button type="button" class="btn btn-sm btn-outline" onclick="clearCart()" title="Clear Entire Cart">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>

        <!-- Customer Selector with Balance Warning -->
        <div style="padding: 10px 16px; border-bottom: 1px solid var(--card-border); background: var(--body-bg);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                <span style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                    <i class="bi bi-person text-primary me-1"></i> Customer Account
                </span>
                <span id="custBalanceWarning" style="font-size: 0.72rem; font-weight: 700; color: #991B1B; display: none;"></span>
            </div>
            <select id="cartCustomerId" class="form-select form-select-sm select2-init" onchange="onCustomerSelect(this)">
                <option value="">Walk-in Customer (Retail Cash)</option>
                @foreach($customers as $cust)
                    <option value="{{ $cust->id }}" data-limit="{{ $cust->credit_limit }}" data-balance="{{ $cust->current_balance }}" data-terms="{{ $cust->payment_terms_days }}" data-name="{{ $cust->name }}">
                        {{ $cust->name }} (AR: ₱{{ number_format($cust->current_balance, 2) }})
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Cart Items Scrollable List -->
        <div id="posCartItemsList" class="pos-cart-items">
            <div style="text-align: center; color: var(--text-light); padding: 50px 20px;">
                <i class="bi bi-cart-x" style="font-size: 2.5rem; display: block; margin-bottom: 8px; color: var(--card-border);"></i>
                Cart is empty.<br>Scan barcode or click items to add.
            </div>
        </div>

        <!-- Cart Summary & Checkout Actions -->
        <div class="pos-cart-summary">
            <div class="summary-row">
                <span>Subtotal:</span>
                <span id="cartSubtotalDisplay" style="font-weight: 700; color: var(--color-deep-navy);">₱0.00</span>
            </div>
            <div class="summary-row" id="cartDiscountRow" style="display: none; color: #065F46;">
                <span>Discount Applied:</span>
                <span id="cartDiscountDisplay">-₱0.00</span>
            </div>
            <div class="summary-row total-row">
                <span>Total Due:</span>
                <span id="cartTotalDueDisplay" style="color: var(--color-primary-blue);">₱0.00</span>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 10px;">
                <button type="button" class="btn btn-outline" onclick="openModal('discountModal')">
                    <i class="bi bi-percent"></i> Discount
                </button>
                <button type="button" class="btn btn-primary btn-lg" id="checkoutBtn" onclick="openPaymentModal()" disabled style="font-weight: 800; letter-spacing: 0.02em;">
                    <i class="bi bi-check2-circle me-1"></i> CHECKOUT [F2]
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Floating Mobile Cart Bar -->
<div class="pos-floating-cart-bar" id="posFloatingCartBar" onclick="toggleCartDrawer(true)">
    <div style="display: flex; align-items: center; gap: 10px;">
        <div class="pos-floating-cart-icon">
            <i class="bi bi-cart3"></i>
            <span class="pos-floating-badge" id="floatingCartBadge">0</span>
        </div>
        <div>
            <div style="font-size: 0.68rem; color: rgba(255,255,255,0.8); text-transform: uppercase; font-weight: 700;">Order Total</div>
            <div style="font-size: 1.05rem; font-weight: 800; color: #fff; line-height: 1.1;" id="floatingCartTotal">₱0.00</div>
        </div>
    </div>
    <div class="pos-floating-cart-action">
        <span>Open Cart</span> <i class="bi bi-arrow-right ms-1"></i>
    </div>
</div>

<!-- Enhanced Payment Modal with Keypad & Presets -->
<div id="paymentModal" class="modal-overlay">
    <div class="modal-dialog modal-lg" style="max-width: 720px;">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-credit-card-2-front text-primary me-2"></i> Payment & Checkout</h3>
            <button type="button" class="modal-close-btn" data-close-modal="paymentModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="modal-body">
            <div class="payment-modal-grid">
                <!-- Left: Payment Method & Totals -->
                <div>
                    <!-- Payable Banner -->
                    <div style="background: var(--color-deep-navy); color: #fff; padding: 18px 20px; border-radius: var(--radius-sm); margin-bottom: 16px; text-align: center;">
                        <div style="font-size: 0.76rem; color: var(--color-wave-cyan); text-transform: uppercase; font-weight: 700;">Total Amount Due</div>
                        <div style="font-size: 2.2rem; font-weight: 800; letter-spacing: -0.02em;" id="payModalTotalDue">₱0.00</div>
                    </div>

                    <!-- Payment Method Selector -->
                    <div class="form-group">
                        <label class="form-label">Payment Method</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                            <label class="btn btn-outline pay-method-label active" style="font-size: 0.82rem; padding: 10px 8px; text-align: center;">
                                <input type="radio" name="pay_method" value="cash" checked style="display: none;" onchange="selectPayMethod('cash')">
                                <i class="bi bi-cash-stack d-block mb-1" style="font-size: 1.2rem;"></i> Cash
                            </label>
                            <label class="btn btn-outline pay-method-label" style="font-size: 0.82rem; padding: 10px 8px; text-align: center;">
                                <input type="radio" name="pay_method" value="gcash" style="display: none;" onchange="selectPayMethod('gcash')">
                                <i class="bi bi-phone d-block mb-1" style="font-size: 1.2rem;"></i> GCash
                            </label>
                            <label class="btn btn-outline pay-method-label" style="font-size: 0.82rem; padding: 10px 8px; text-align: center;">
                                <input type="radio" name="pay_method" value="bank_transfer" style="display: none;" onchange="selectPayMethod('bank_transfer')">
                                <i class="bi bi-bank d-block mb-1" style="font-size: 1.2rem;"></i> Bank Transfer
                            </label>
                            <label class="btn btn-outline pay-method-label" style="font-size: 0.82rem; padding: 10px 8px; text-align: center;">
                                <input type="radio" name="pay_method" value="credit" style="display: none;" onchange="selectPayMethod('credit')">
                                <i class="bi bi-file-earmark-text d-block mb-1" style="font-size: 1.2rem;"></i> Credit (AR)
                            </label>
                        </div>
                    </div>

                    <!-- Reference / Credit Fields -->
                    <div id="extraPayFields" style="display: none;">
                        <div class="form-group" id="refFieldGroup">
                            <label class="form-label" for="payRefInput">Reference / Approval Code</label>
                            <input type="text" id="payRefInput" class="form-control" placeholder="e.g. GCash / Bank Ref #">
                        </div>
                        <div class="form-group" id="creditDueGroup" style="display: none;">
                            <label class="form-label" for="payDueDateInput">Credit Due Date</label>
                            <input type="date" id="payDueDateInput" class="form-control" value="{{ date('Y-m-d', strtotime('+30 days')) }}">
                        </div>
                    </div>

                    <!-- Change Display Banner -->
                    <div style="background: var(--color-mist-blue); border: 1.5px solid #d2eaf8; border-radius: var(--radius-sm); padding: 12px 16px; margin-top: 14px; display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-weight: 700; color: var(--color-deep-navy);">Change to Return:</span>
                        <span id="payModalChangeDisplay" style="font-size: 1.4rem; font-weight: 800; color: #065F46;">₱0.00</span>
                    </div>
                </div>

                <!-- Right: Tender Input, Preset Chips & Touch Keypad -->
                <div>
                    <div class="form-group">
                        <label class="form-label" for="payModalAmountPaid">Amount Received (₱) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" id="payModalAmountPaid" class="form-control form-control-lg" style="font-size: 1.4rem; font-weight: 800; color: var(--color-deep-navy);" placeholder="0.00" oninput="calcChange()">
                    </div>

                    <!-- Quick Cash Denomination Chips -->
                    <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-light); text-transform: uppercase; margin-bottom: 6px;">
                        Quick Cash Tender Presets:
                    </div>
                    <div style="display: flex; gap: 6px; margin-bottom: 12px; flex-wrap: wrap;" id="quickCashContainer">
                        <!-- Populated via JS -->
                    </div>

                    <!-- Interactive Touch Keypad -->
                    <div class="touch-keypad-grid">
                        <button type="button" class="keypad-btn" onclick="keypadPress('7')">7</button>
                        <button type="button" class="keypad-btn" onclick="keypadPress('8')">8</button>
                        <button type="button" class="keypad-btn" onclick="keypadPress('9')">9</button>

                        <button type="button" class="keypad-btn" onclick="keypadPress('4')">4</button>
                        <button type="button" class="keypad-btn" onclick="keypadPress('5')">5</button>
                        <button type="button" class="keypad-btn" onclick="keypadPress('6')">6</button>

                        <button type="button" class="keypad-btn" onclick="keypadPress('1')">1</button>
                        <button type="button" class="keypad-btn" onclick="keypadPress('2')">2</button>
                        <button type="button" class="keypad-btn" onclick="keypadPress('3')">3</button>

                        <button type="button" class="keypad-btn" onclick="keypadPress('0')">0</button>
                        <button type="button" class="keypad-btn" onclick="keypadPress('.')">.</button>
                        <button type="button" class="keypad-btn" style="color: var(--danger-red);" onclick="keypadClear()"><i class="bi bi-backspace"></i></button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" data-close-modal="paymentModal">Cancel</button>
            <button type="button" class="btn btn-primary btn-lg" id="submitPaymentBtn" onclick="submitPosTransaction()">
                <i class="bi bi-printer me-1"></i> Complete & Print Receipt [Enter]
            </button>
        </div>
    </div>
</div>

<!-- Held Orders Modal -->
<div id="heldOrdersModal" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-pause-circle text-primary me-2"></i> Parked / Held Orders</h3>
            <button type="button" class="modal-close-btn" data-close-modal="heldOrdersModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="modal-body" id="heldOrdersListBody">
            <!-- Populated via JS -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" data-close-modal="heldOrdersModal">Close</button>
        </div>
    </div>
</div>

<!-- Discount Modal -->
<div id="discountModal" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-percent text-primary me-2"></i> Apply Approved Discount</h3>
            <button type="button" class="modal-close-btn" data-close-modal="discountModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Discount Type</label>
                <select id="discountTypeInput" class="form-select" onchange="toggleDiscountVal()">
                    <option value="none">No Discount</option>
                    <option value="fixed">Fixed Amount (₱)</option>
                    <option value="percentage">Percentage (%)</option>
                </select>
            </div>
            <div class="form-group" id="discountValueGroup" style="display: none;">
                <label class="form-label" for="discountValueInput">Discount Value</label>
                <input type="number" step="0.01" id="discountValueInput" class="form-control" placeholder="0.00" value="0">
            </div>
            <div class="form-group" id="discountReasonGroup" style="display: none;">
                <label class="form-label" for="discountReasonInput">Reason / Justification</label>
                <input type="text" id="discountReasonInput" class="form-control" placeholder="e.g. Senior Citizen/PWD, wholesale volume concession">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" data-close-modal="discountModal">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="applyDiscount()">Apply Discount</button>
        </div>
    </div>
</div>

<!-- Printable Thermal Receipt Modal -->
<div id="receiptModal" class="modal-overlay">
    <div class="modal-dialog" style="max-width: 380px;">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-receipt text-success me-2"></i> Sale Receipt</h3>
            <button type="button" class="modal-close-btn" data-close-modal="receiptModal"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="modal-body" style="background: #FFF; padding: 15px;">
            <div id="printableReceipt" style="font-family: 'Courier New', monospace; font-size: 12px; color: #000; line-height: 1.4;">
                <!-- Populated via JS -->
            </div>
        </div>
        <div class="modal-footer" style="display: flex; gap: 8px;">
            <button type="button" class="btn btn-outline" data-close-modal="receiptModal">Close</button>
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print Receipt
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let posCatalog = [];
    let cart = [];
    let heldSales = JSON.parse(localStorage.getItem('bagsakan_held_sales') || '[]');
    let currentDiscount = { type: 'none', value: 0, reason: '' };
    let selectedCategoryId = '';
    let currentPage = 1;
    let hasMoreProducts = true;
    let isLoadingProducts = false;
    let currentSearchQuery = '';
    const warehouseId = {{ $warehouse->id ?? 1 }};

    // 1. Fetch POS Products (Page 1 or Pagination Append)
    function loadPosProducts(query = '', categoryId = '', page = 1, append = false) {
        if (isLoadingProducts) return;
        isLoadingProducts = true;

        currentSearchQuery = query;
        selectedCategoryId = categoryId;
        currentPage = page;

        const grid = document.getElementById('posProductGrid');
        const scrollLoader = document.getElementById('posScrollLoader');

        if (!append) {
            grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 40px;"><div class="spinner-border text-primary" style="width: 2.4rem; height: 2.4rem;"></div><div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600; margin-top: 10px;">Loading catalog...</div></div>';
            posCatalog = [];
        } else {
            if (scrollLoader) scrollLoader.style.display = 'block';
        }

        let url = `/cashier/pos/products?q=${encodeURIComponent(query)}&page=${page}&per_page=8`;
        if (categoryId) url += `&category_id=${categoryId}`;

        fetch(url, { headers: { 'Accept': 'application/json' } })
        .then(res => res.json())
        .then(res => {
            const newItems = res.data || [];
            hasMoreProducts = Boolean(res.has_more);

            if (res.category_counts && res.total_all !== undefined) {
                updateCategoryCounts(res.total_all, res.category_counts);
            }

            if (append) {
                posCatalog = [...posCatalog, ...newItems];
                appendProductGrid(newItems);
            } else {
                posCatalog = [...newItems];
                renderProductGrid(posCatalog);
            }
        })
        .catch(err => {
            if (!append) {
                grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; color: var(--danger-red); padding: 40px 0;"><i class="bi bi-exclamation-triangle" style="font-size: 2rem; display: block; margin-bottom: 8px;"></i>Failed to load products.</div>';
            }
        })
        .finally(() => {
            isLoadingProducts = false;
            const loader = document.getElementById('posScrollLoader');
            if (loader) loader.style.display = 'none';
        });
    }

    // 2. Update category counter badges with global database counts
    function updateCategoryCounts(totalAll, catCounts) {
        const countAllEl = document.getElementById('countAll');
        if (countAllEl) countAllEl.innerText = totalAll;
        if (catCounts) {
            for (const [catId, count] of Object.entries(catCounts)) {
                const el = document.getElementById(`countCat_${catId}`);
                if (el) el.innerText = count;
            }
        }
    }

    // 3. Category Filter Pill Click
    function filterCategory(btn, catId) {
        document.querySelectorAll('.category-pills-bar .category-pill').forEach(el => el.classList.remove('active'));
        btn.classList.add('active');
        selectedCategoryId = catId;
        const q = document.getElementById('posSearchInput').value.trim();
        loadPosProducts(q, selectedCategoryId, 1, false);
    }

    // 4. Create Single Product Card HTML
    function createProductCardHtml(p) {
        let stockClass = 'in-stock';
        let stockLabel = `Stock: ${p.available_stock.toFixed(1)} ${p.base_unit}`;
        if (p.available_stock <= 0) {
            stockClass = 'out-stock';
            stockLabel = 'Out of Stock';
        } else if (p.available_stock <= (p.low_stock_threshold || 10)) {
            stockClass = 'low-stock';
            stockLabel = `Low: ${p.available_stock.toFixed(1)} ${p.base_unit}`;
        }

        return `
            <div class="pos-product-card" onclick="addToCart(${p.id})">
                <div class="pos-card-top">
                    <img src="${p.image_url}" alt="${p.name}" class="pos-product-img" loading="lazy">
                    <span class="pos-stock-pill ${stockClass}">${stockLabel}</span>
                    <span class="pos-category-tag">${p.category_name || 'Produce'}</span>
                </div>
                <div class="pos-product-title" title="${p.name}">${p.name}</div>
                <div class="pos-product-sku">${p.sku}</div>
                <div class="pos-card-footer">
                    <div>
                        <div class="pos-product-price">₱${p.default_srp.toFixed(2)}</div>
                        <div class="pos-price-unit">per ${p.base_unit}</div>
                    </div>
                    <div class="pos-add-btn"><i class="bi bi-plus"></i></div>
                </div>
            </div>
        `;
    }

    // 5. Render Initial Product Catalog Grid
    function renderProductGrid(items) {
        const grid = document.getElementById('posProductGrid');
        if (items.length === 0) {
            grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; color: var(--text-light); padding: 50px 0;"><i class="bi bi-box-seam" style="font-size: 2.5rem; display: block; margin-bottom: 8px;"></i>No matching items found.</div>';
            return;
        }

        grid.innerHTML = items.map(p => createProductCardHtml(p)).join('') + `
            <div id="posScrollLoader" style="display: none; grid-column: 1/-1; text-align: center; padding: 24px 0;">
                <div class="spinner-border text-primary" style="width: 2rem; height: 2rem;" role="status"></div>
                <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 700; margin-top: 6px;">Loading more products...</div>
            </div>
        `;
    }

    // 6. Append Next Page Items to Grid
    function appendProductGrid(newItems) {
        const loader = document.getElementById('posScrollLoader');
        if (newItems.length > 0) {
            const cardsHtml = newItems.map(p => createProductCardHtml(p)).join('');
            if (loader) {
                loader.insertAdjacentHTML('beforebegin', cardsHtml);
            } else {
                const grid = document.getElementById('posProductGrid');
                grid.insertAdjacentHTML('beforeend', cardsHtml);
            }
        }
    }

    // 5. Add to Cart with Unit Conversion Selector
    function addToCart(productOrId) {
        let prod;
        if (typeof productOrId === 'object' && productOrId !== null) {
            prod = productOrId;
            if (!posCatalog.some(x => x.id === prod.id)) {
                posCatalog.push(prod);
            }
        } else {
            prod = posCatalog.find(x => x.id === productOrId);
        }
        if (!prod) return;

        if (prod.available_stock <= 0) {
            showToast('warning', 'Out of Stock', `${prod.name} has 0 available inventory.`);
            return;
        }

        const existing = cart.find(x => x.product_id === prod.id);
        if (existing) {
            existing.quantity += 1;
        } else {
            const defUnit = (prod.units && prod.units.find(u => u.is_default_selling)) || (prod.units && prod.units[0]) || { unit_name: prod.base_unit, conversion_factor: 1, srp: prod.default_srp };
            cart.push({
                product_id: prod.id,
                name: prod.name,
                sku: prod.sku,
                base_unit: prod.base_unit,
                available_stock: prod.available_stock,
                selected_unit: defUnit.unit_name,
                conversion_factor: defUnit.conversion_factor,
                unit_price: defUnit.srp,
                quantity: 1,
                line_discount: 0,
                units: prod.units || []
            });
        }
        renderCart();
    }

    function changeCartUnit(idx, unitName) {
        const item = cart[idx];
        const unitObj = item.units.find(u => u.unit_name === unitName);
        if (unitObj) {
            item.selected_unit = unitObj.unit_name;
            item.conversion_factor = unitObj.conversion_factor;
            item.unit_price = unitObj.srp;
            renderCart();
        }
    }

    function updateCartQty(idx, delta) {
        if (cart[idx]) {
            cart[idx].quantity += delta;
            if (cart[idx].quantity <= 0) {
                cart.splice(idx, 1);
            }
            renderCart();
        }
    }

    function setCartExactQty(idx, val) {
        val = parseFloat(val) || 0;
        if (val <= 0) {
            cart.splice(idx, 1);
        } else if (cart[idx]) {
            cart[idx].quantity = val;
        }
        renderCart();
    }

    function removeCartItem(idx) {
        cart.splice(idx, 1);
        renderCart();
    }

    function clearCart() {
        cart = [];
        currentDiscount = { type: 'none', value: 0, reason: '' };
        renderCart();
    }

    // Slide-out Cart Drawer Toggle (Mobile & Tablet <= 1024px)
    function toggleCartDrawer(open) {
        const pane = document.querySelector('.pos-cart-pane');
        const backdrop = document.getElementById('posCartBackdrop');
        const trigger = document.getElementById('posCartDrawerTrigger');

        if (open) {
            if (pane) pane.classList.add('drawer-open');
            if (backdrop) backdrop.classList.add('show');
            if (trigger) trigger.style.display = 'none';
            document.body.style.overflow = 'hidden';
        } else {
            if (pane) pane.classList.remove('drawer-open');
            if (backdrop) backdrop.classList.remove('show');
            if (trigger && window.innerWidth <= 1024) trigger.style.display = 'flex';
            document.body.style.overflow = '';
        }
    }

    window.addEventListener('resize', () => {
        const pane = document.querySelector('.pos-cart-pane');
        const backdrop = document.getElementById('posCartBackdrop');
        const trigger = document.getElementById('posCartDrawerTrigger');
        if (window.innerWidth > 1024) {
            if (pane) pane.classList.remove('drawer-open');
            if (backdrop) backdrop.classList.remove('show');
            if (trigger) trigger.style.display = 'none';
            document.body.style.overflow = '';
        } else {
            const isDrawerOpen = pane && pane.classList.contains('drawer-open');
            if (trigger) trigger.style.display = isDrawerOpen ? 'none' : 'flex';
        }
    });

    // 6. Render Cart UI & Compute Totals
    function renderCart() {
        const container = document.getElementById('posCartItemsList');
        const countDisplay = document.getElementById('cartItemCountDisplay');
        const checkoutBtn = document.getElementById('checkoutBtn');
        const sideBadge = document.getElementById('sideCartBadge');
        const floatingBar = document.getElementById('posFloatingCartBar');
        const floatingBadge = document.getElementById('floatingCartBadge');
        const floatingTotal = document.getElementById('floatingCartTotal');

        if (sideBadge) sideBadge.innerText = cart.length;
        if (floatingBadge) floatingBadge.innerText = cart.length;

        if (cart.length === 0) {
            container.innerHTML = `
                <div style="text-align: center; color: var(--text-light); padding: 50px 20px;">
                    <i class="bi bi-cart-x" style="font-size: 2.5rem; display: block; margin-bottom: 8px; color: var(--card-border);"></i>
                    Cart is empty.<br>Scan barcode or click items to add.
                </div>
            `;
            countDisplay.innerText = '0 items in cart';
            checkoutBtn.disabled = true;
            document.getElementById('cartSubtotalDisplay').innerText = '₱0.00';
            document.getElementById('cartDiscountRow').style.display = 'none';
            document.getElementById('cartTotalDueDisplay').innerText = '₱0.00';
            if (floatingBar) floatingBar.style.display = 'none';
            return;
        }

        let subtotal = 0;
        container.innerHTML = cart.map((item, idx) => {
            const lineTotal = item.quantity * item.unit_price;
            subtotal += lineTotal;

            const unitOptionsHtml = item.units && item.units.length > 0
                ? item.units.map(u => `<option value="${u.unit_name}" ${u.unit_name === item.selected_unit ? 'selected' : ''}>${u.unit_name} (₱${u.srp.toFixed(2)})</option>`).join('')
                : `<option value="${item.base_unit}">${item.base_unit}</option>`;

            return `
                <div class="cart-item-row">
                    <div class="cart-item-title">
                        <span>${item.name}</span>
                        <span style="color: var(--color-primary-blue); font-weight: 800;">₱${lineTotal.toFixed(2)}</span>
                    </div>

                    <div style="display: flex; gap: 8px; align-items: center;">
                        <select class="form-select form-select-sm" style="font-size: 0.78rem; padding: 4px 8px; height: 32px;" onchange="changeCartUnit(${idx}, this.value)">
                            ${unitOptionsHtml}
                        </select>
                    </div>

                    <div class="cart-item-controls">
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <button type="button" class="qty-stepper-btn" onclick="updateCartQty(${idx}, -1)">-</button>
                            <input type="number" step="0.01" class="form-control form-control-sm" style="width: 70px; text-align: center; height: 28px; font-weight: 700;" value="${item.quantity}" onchange="setCartExactQty(${idx}, this.value)">
                            <button type="button" class="qty-stepper-btn" onclick="updateCartQty(${idx}, 1)">+</button>
                        </div>
                        <button type="button" class="btn btn-sm btn-danger" style="padding: 2px 8px; height: 28px;" onclick="removeCartItem(${idx})" title="Remove item">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `;
        }).join('');

        let discountAmt = 0;
        if (currentDiscount.type === 'fixed') {
            discountAmt = currentDiscount.value;
        } else if (currentDiscount.type === 'percentage') {
            discountAmt = subtotal * (currentDiscount.value / 100);
        }

        const totalDue = Math.max(0, subtotal - discountAmt);

        countDisplay.innerText = `${cart.length} item(s) in cart`;
        document.getElementById('cartSubtotalDisplay').innerText = '₱' + subtotal.toFixed(2);

        if (discountAmt > 0) {
            document.getElementById('cartDiscountRow').style.display = 'flex';
            document.getElementById('cartDiscountDisplay').innerText = '-₱' + discountAmt.toFixed(2);
        } else {
            document.getElementById('cartDiscountRow').style.display = 'none';
        }

        document.getElementById('cartTotalDueDisplay').innerText = '₱' + totalDue.toFixed(2);
        if (floatingTotal) floatingTotal.innerText = '₱' + totalDue.toFixed(2);

        const tabBtnCatalog = document.getElementById('tabBtnCatalog');
        const isCatalogActive = tabBtnCatalog && tabBtnCatalog.classList.contains('active');
        if (floatingBar && isCatalogActive && window.innerWidth <= 1024) {
            floatingBar.style.display = 'flex';
        }

        checkoutBtn.disabled = false;
    }

    // 7. Customer Selection Handler with Warning
    function onCustomerSelect(select) {
        const selected = select.options[select.selectedIndex];
        const warning = document.getElementById('custBalanceWarning');
        if (selected && selected.value) {
            const bal = parseFloat(selected.dataset.balance) || 0;
            const limit = parseFloat(selected.dataset.limit) || 0;
            if (bal > 0) {
                warning.innerText = `Open AR: ₱${bal.toFixed(2)} / Limit: ₱${limit.toFixed(2)}`;
                warning.style.display = 'inline';
            } else {
                warning.style.display = 'none';
            }
        } else {
            warning.style.display = 'none';
        }
    }

    // 8. Hold Sale / Resume Parked Orders Feature
    function holdCurrentSale() {
        if (cart.length === 0) {
            showToast('info', 'Cart Empty', 'Nothing to hold.');
            return;
        }

        const heldItem = {
            id: Date.now(),
            date: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
            customer_id: document.getElementById('cartCustomerId').value,
            customer_name: document.getElementById('cartCustomerId').options[document.getElementById('cartCustomerId').selectedIndex]?.text || 'Walk-in',
            items: [...cart],
            discount: { ...currentDiscount }
        };

        heldSales.push(heldItem);
        localStorage.setItem('bagsakan_held_sales', JSON.stringify(heldSales));
        updateHeldBadge();
        clearCart();
        showToast('info', 'Sale Held', 'Transaction held in memory. Ready for next customer.');
    }

    function updateHeldBadge() {
        const badge = document.getElementById('heldOrdersBadge');
        if (heldSales.length > 0) {
            badge.innerText = heldSales.length;
            badge.style.display = 'block';
        } else {
            badge.style.display = 'none';
        }
    }

    function openHeldOrdersModal() {
        const body = document.getElementById('heldOrdersListBody');
        if (heldSales.length === 0) {
            body.innerHTML = '<div style="text-align: center; color: var(--text-light); padding: 30px;">No held orders found.</div>';
        } else {
            body.innerHTML = heldSales.map((h, i) => {
                const total = h.items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
                return `
                    <div style="background: var(--body-bg); border-radius: var(--radius-sm); padding: 12px; margin-bottom: 10px; border: 1px solid var(--card-border); display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 700; color: var(--color-deep-navy);">${h.customer_name} (${h.date})</div>
                            <div style="font-size: 0.76rem; color: var(--text-light);">${h.items.length} item(s) &bull; Total: ₱${total.toFixed(2)}</div>
                        </div>
                        <div style="display: flex; gap: 6px;">
                            <button type="button" class="btn btn-sm btn-primary" onclick="resumeHeldSale(${i})">Resume</button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteHeldSale(${i})"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                `;
            }).join('');
        }
        openModal('heldOrdersModal');
    }

    function resumeHeldSale(idx) {
        const held = heldSales[idx];
        cart = [...held.items];
        currentDiscount = { ...held.discount };
        document.getElementById('cartCustomerId').value = held.customer_id;
        $('#cartCustomerId').trigger('change');
        heldSales.splice(idx, 1);
        localStorage.setItem('bagsakan_held_sales', JSON.stringify(heldSales));
        updateHeldBadge();
        closeModal('heldOrdersModal');
        renderCart();
        showToast('success', 'Order Resumed', 'Held transaction restored to active cart.');
    }

    function deleteHeldSale(idx) {
        heldSales.splice(idx, 1);
        localStorage.setItem('bagsakan_held_sales', JSON.stringify(heldSales));
        updateHeldBadge();
        openHeldOrdersModal();
    }

    // 9. Payment Modal & Touch Keypad
    function openPaymentModal() {
        if (cart.length === 0) return;

        let subtotal = cart.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
        let discountAmt = currentDiscount.type === 'fixed' ? currentDiscount.value : (currentDiscount.type === 'percentage' ? subtotal * (currentDiscount.value / 100) : 0);
        let totalDue = Math.max(0, subtotal - discountAmt);

        document.getElementById('payModalTotalDue').innerText = '₱' + totalDue.toFixed(2);
        document.getElementById('payModalAmountPaid').value = totalDue.toFixed(2);
        calcChange();

        // Preset Chips
        const chipsContainer = document.getElementById('quickCashContainer');
        const next100 = Math.ceil(totalDue / 100) * 100;
        const next500 = Math.ceil(totalDue / 500) * 500;
        const next1000 = Math.ceil(totalDue / 1000) * 1000;
        const presets = Array.from(new Set([totalDue, next100, next500, next1000, 1000, 2000])).filter(v => v >= totalDue);

        chipsContainer.innerHTML = presets.map(v => `
            <button type="button" class="btn btn-sm btn-secondary" style="font-size: 0.76rem;" onclick="setPayAmount(${v})">
                ₱${v.toLocaleString()}
            </button>
        `).join('');

        openModal('paymentModal');
        setTimeout(() => document.getElementById('payModalAmountPaid').select(), 200);
    }

    function setPayAmount(val) {
        document.getElementById('payModalAmountPaid').value = val.toFixed(2);
        calcChange();
    }

    function keypadPress(key) {
        const input = document.getElementById('payModalAmountPaid');
        if (input.value === '0' && key !== '.') {
            input.value = key;
        } else {
            input.value += key;
        }
        calcChange();
    }

    function keypadClear() {
        const input = document.getElementById('payModalAmountPaid');
        input.value = input.value.slice(0, -1);
        if (!input.value) input.value = '';
        calcChange();
    }

    function calcChange() {
        let subtotal = cart.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
        let discountAmt = currentDiscount.type === 'fixed' ? currentDiscount.value : (currentDiscount.type === 'percentage' ? subtotal * (currentDiscount.value / 100) : 0);
        let totalDue = Math.max(0, subtotal - discountAmt);

        let paid = parseFloat(document.getElementById('payModalAmountPaid').value) || 0;
        let change = Math.max(0, paid - totalDue);

        document.getElementById('payModalChangeDisplay').innerText = '₱' + change.toFixed(2);
    }

    function selectPayMethod(method) {
        document.querySelectorAll('.pay-method-label').forEach(el => el.classList.remove('active'));
        event.target.closest('.pay-method-label').classList.add('active');

        const extraFields = document.getElementById('extraPayFields');
        const refGroup = document.getElementById('refFieldGroup');
        const creditGroup = document.getElementById('creditDueGroup');

        if (method === 'gcash' || method === 'bank_transfer') {
            extraFields.style.display = 'block';
            refGroup.style.display = 'block';
            creditGroup.style.display = 'none';
        } else if (method === 'credit') {
            extraFields.style.display = 'block';
            refGroup.style.display = 'none';
            creditGroup.style.display = 'block';

            const custId = document.getElementById('cartCustomerId').value;
            if (!custId) {
                showToast('warning', 'Customer Required', 'Please select a registered customer in the cart pane for credit sales.');
            }
        } else {
            extraFields.style.display = 'none';
        }
    }

    // 10. Process POS Transaction with SweetAlert2 & Direct Thermal Printer Iframe
    function submitPosTransaction() {
        if (cart.length === 0) return;

        Swal.fire({
            title: 'Confirm Payment',
            text: 'Do you want to print the receipt after payment?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#075998',
            cancelButtonColor: '#6B7280',
            confirmButtonText: '<i class="bi bi-printer me-1"></i> Yes, print',
            cancelButtonText: 'No, complete only'
        }).then(choice => {
            if (choice.isDismissed && choice.dismiss !== Swal.DismissReason.cancel) {
                return;
            }
            const wantsPrint = Boolean(choice.isConfirmed);

            const submitBtn = document.getElementById('submitPaymentBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Processing...';

            const method = document.querySelector('input[name="pay_method"]:checked')?.value || 'cash';
            const customerId = document.getElementById('cartCustomerId').value || null;
            const amountPaid = parseFloat(document.getElementById('payModalAmountPaid').value) || 0;
            const refNo = document.getElementById('payRefInput').value;
            const dueDate = document.getElementById('payDueDateInput').value;

            const payload = {
                warehouse_id: warehouseId,
                customer_id: customerId,
                payment_method: method,
                amount_paid: amountPaid,
                discount_type: currentDiscount.type,
                discount_value: currentDiscount.value,
                discount_reason: currentDiscount.reason,
                reference_number: refNo,
                due_date: dueDate,
                items: cart.map(i => ({
                    product_id: i.product_id,
                    unit_name: i.selected_unit,
                    conversion_factor: i.conversion_factor,
                    quantity: i.quantity,
                    unit_price: i.unit_price,
                    line_discount: i.line_discount || 0
                }))
            };

            fetch('{{ route("cashier.pos.checkout") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(payload)
            })
            .then(async res => {
                const data = await res.json();
                if (!res.ok) {
                    throw new Error(data.message || 'Checkout failed');
                }
                return data;
            })
            .then(data => {
                closeModal('paymentModal');
                showToast('success', 'Sale Completed', data.message);
                clearCart();
                loadPosProducts('', selectedCategoryId, 1, false);

                if (wantsPrint && data.sale) {
                    printThermalReceiptIframe(data.sale);
                }
            })
            .catch(err => {
                showToast('error', 'Checkout Error', err.message);
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-printer me-1"></i> Complete & Print Receipt [Enter]';
            });
        });
    }

    // 11. Direct 80mm / 58mm POS Thermal Printing via Hidden Iframe
    function printThermalReceiptIframe(sale) {
        let iframe = document.getElementById('receiptPrintIframe');
        if (!iframe) {
            iframe = document.createElement('iframe');
            iframe.id = 'receiptPrintIframe';
            iframe.style.position = 'fixed';
            iframe.style.width = '0';
            iframe.style.height = '0';
            iframe.style.border = '0';
            iframe.style.visibility = 'hidden';
            iframe.style.left = '-9999px';
            document.body.appendChild(iframe);
        }

        const linesHtml = sale.lines.map(l => `
            <tr>
                <td colspan="2" style="font-weight: 700; padding-top: 4px;">${l.name || l.product_name}</td>
            </tr>
            <tr>
                <td style="padding-bottom: 4px; font-size: 11px;">${l.quantity} ${l.unit || l.unit_name} @ ₱${parseFloat(l.unit_price).toFixed(2)}</td>
                <td style="text-align: right; padding-bottom: 4px; font-weight: 700;">₱${parseFloat(l.subtotal).toFixed(2)}</td>
            </tr>
        `).join('');

        const receiptDoc = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Receipt - ${sale.sale_number}</title>
                <style>
                    @page {
                        size: 80mm auto;
                        margin: 0;
                    }
                    body {
                        width: 74mm;
                        margin: 0 auto;
                        padding: 8px 4px;
                        font-family: 'Courier New', Courier, monospace;
                        font-size: 12px;
                        line-height: 1.35;
                        color: #000000;
                    }
                    .text-center { text-align: center; }
                    .text-right { text-align: right; }
                    .bold { font-weight: bold; }
                    .divider { border-bottom: 1px dashed #000; margin: 6px 0; }
                    table { width: 100%; font-size: 11.5px; border-collapse: collapse; }
                    .row-flex { display: flex; justify-content: space-between; align-items: center; }
                </style>
            </head>
            <body>
                <div class="text-center bold" style="font-size: 14px; letter-spacing: 0.5px;">WORTHY ACOSTA BAGSAKAN</div>
                <div class="text-center" style="font-size: 11px;">Wholesale & Retail Produce</div>
                <div class="text-center" style="font-size: 10px;">Trading Post Hub, Balintawak</div>
                <div class="divider"></div>
                <div>Invoice: <strong>${sale.sale_number}</strong></div>
                <div>Date: ${sale.sale_date}</div>
                <div>Cashier: ${sale.cashier_name || '{{ auth()->user()->name }}'}</div>
                <div>Customer: ${sale.customer_name || 'Walk-in Customer'}</div>
                <div class="divider"></div>
                <table>
                    ${linesHtml}
                </table>
                <div class="divider"></div>
                <div class="row-flex">
                    <span>Subtotal:</span>
                    <span>₱${parseFloat(sale.subtotal).toFixed(2)}</span>
                </div>
                ${parseFloat(sale.discount_amount) > 0 ? `
                <div class="row-flex">
                    <span>Discount:</span>
                    <span>-₱${parseFloat(sale.discount_amount).toFixed(2)}</span>
                </div>` : ''}
                <div class="row-flex bold" style="font-size: 13px; margin: 4px 0;">
                    <span>TOTAL DUE:</span>
                    <span>₱${parseFloat(sale.total_amount).toFixed(2)}</span>
                </div>
                <div class="row-flex">
                    <span>Payment (${sale.payment_method ? sale.payment_method.toUpperCase() : 'CASH'}):</span>
                    <span>₱${parseFloat(sale.amount_paid).toFixed(2)}</span>
                </div>
                <div class="row-flex bold">
                    <span>Change:</span>
                    <span>₱${parseFloat(sale.change_amount).toFixed(2)}</span>
                </div>
                <div class="divider"></div>
                <div class="text-center" style="font-size: 10px; margin-top: 6px;">
                    Thank you for your business!<br>
                    Please inspect goods upon delivery.
                </div>
            </body>
            </html>
        `;

        const doc = iframe.contentDocument || iframe.contentWindow.document;
        doc.open();
        doc.write(receiptDoc);
        doc.close();

        setTimeout(() => {
            try {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            } catch (err) {
                console.warn('Print failed on iframe', err);
            }
        }, 300);
    }

    // 12. Discount Handlers
    function toggleDiscountVal() {
        const type = document.getElementById('discountTypeInput').value;
        document.getElementById('discountValueGroup').style.display = type === 'none' ? 'none' : 'block';
        document.getElementById('discountReasonGroup').style.display = type === 'none' ? 'none' : 'block';
    }

    function applyDiscount() {
        const type = document.getElementById('discountTypeInput').value;
        const val = parseFloat(document.getElementById('discountValueInput').value) || 0;
        const reason = document.getElementById('discountReasonInput').value;

        currentDiscount = { type, value: val, reason };
        closeModal('discountModal');
        renderCart();
        showToast('info', 'Discount Applied', `${type === 'percentage' ? val + '%' : '₱' + val.toFixed(2)} discount applied.`);
    }

    // 13. Barcode Scanner Audio Feedback
    function playScanBeep(type = 'success') {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            const ctx = new AudioContext();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);

            if (type === 'success') {
                osc.type = 'sine';
                osc.frequency.setValueAtTime(1200, ctx.currentTime);
                gain.gain.setValueAtTime(0.15, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.12);
                osc.start();
                osc.stop(ctx.currentTime + 0.12);
            } else {
                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(220, ctx.currentTime);
                gain.gain.setValueAtTime(0.2, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.28);
                osc.start();
                osc.stop(ctx.currentTime + 0.28);
            }
        } catch (e) {
            // Audio context policy or unsupported
        }
    }

    // 14. Barcode Scan & Dedicated Search Handler
    function handleBarcodeScan(query) {
        const q = (query || '').trim();
        const searchInput = document.getElementById('posSearchInput');
        if (!q) return;

        let url = `/cashier/pos/products?q=${encodeURIComponent(q)}&page=1&per_page=12`;
        if (selectedCategoryId) url += `&category_id=${selectedCategoryId}`;

        fetch(url, { headers: { 'Accept': 'application/json' } })
        .then(res => res.json())
        .then(res => {
            const items = res.data || [];

            // 1. Check for Exact Barcode or SKU match
            const exactMatch = items.find(p => 
                (p.barcode && p.barcode.trim().toLowerCase() === q.toLowerCase()) ||
                (p.sku && p.sku.trim().toLowerCase() === q.toLowerCase())
            );

            if (exactMatch) {
                if (exactMatch.available_stock <= 0) {
                    playScanBeep('error');
                    showToast('warning', 'Out of Stock', `${exactMatch.name} has 0 available inventory.`);
                } else {
                    addToCart(exactMatch);
                    playScanBeep('success');
                    showToast('success', 'Item Added', `${exactMatch.name} added to cart.`);
                }
                if (searchInput) searchInput.value = '';
                loadPosProducts('', selectedCategoryId, 1, false);
                return;
            }

            // 2. Exactly one matching item from query
            if (items.length === 1) {
                const item = items[0];
                if (item.available_stock <= 0) {
                    playScanBeep('error');
                    showToast('warning', 'Out of Stock', `${item.name} has 0 available inventory.`);
                } else {
                    addToCart(item);
                    playScanBeep('success');
                    showToast('success', 'Item Added', `${item.name} added to cart.`);
                }
                if (searchInput) searchInput.value = '';
                loadPosProducts('', selectedCategoryId, 1, false);
                return;
            }

            // 3. No items found -> INVALID BARCODE NOTIFICATION
            if (items.length === 0) {
                playScanBeep('error');
                showToast('error', 'Invalid Barcode', `No product found for barcode / SKU "${q}".`);
                if (searchInput) {
                    searchInput.select();
                }
                renderProductGrid([]);
                return;
            }

            // 4. Multiple matches -> render in catalog for cashier selection
            posCatalog = items;
            renderProductGrid(items);
            showToast('info', 'Multiple Matches', `Found ${items.length} products matching "${q}". Click item to add.`);
        })
        .catch(err => {
            playScanBeep('error');
            showToast('error', 'Scan Error', 'Unable to check barcode. Please check connection.');
        });
    }

    // 15. Search, Infinite Scroll & Keyboard Shortcuts
    document.addEventListener('DOMContentLoaded', () => {
        loadPosProducts('');
        updateHeldBadge();

        const grid = document.getElementById('posProductGrid');
        if (grid) {
            grid.addEventListener('scroll', function () {
                // When within 100px of bottom, fetch next 8 items
                if (grid.scrollTop + grid.clientHeight >= grid.scrollHeight - 100) {
                    if (hasMoreProducts && !isLoadingProducts) {
                        loadPosProducts(currentSearchQuery, selectedCategoryId, currentPage + 1, true);
                    }
                }
            });
        }

        const searchInput = document.getElementById('posSearchInput');
        let debounceTimer;

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                const val = this.value.trim();
                debounceTimer = setTimeout(() => {
                    loadPosProducts(val, selectedCategoryId, 1, false);
                }, 250);
            });

            // Fast Barcode Scan & Enter Key Handler
            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(debounceTimer);
                    handleBarcodeScan(this.value.trim());
                }
            });
        }

        // Global Barcode Scanner Buffer Listener (captures scans if focus is not in an input/modal)
        let scanBuffer = '';
        let lastKeyTime = Date.now();

        document.addEventListener('keydown', function (e) {
            // Hotkeys
            if (e.key === 'F2') {
                e.preventDefault();
                openPaymentModal();
                return;
            } else if (e.key === 'F4') {
                e.preventDefault();
                holdCurrentSale();
                return;
            }

            const activeTag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
            const isInputFocused = (activeTag === 'input' || activeTag === 'textarea' || activeTag === 'select');

            // If posSearchInput is active, let its keydown handler process it
            if (document.activeElement === searchInput) {
                return;
            }

            // If another input/modal is focused, do not intercept
            if (isInputFocused) {
                return;
            }

            const currentTime = Date.now();
            if (currentTime - lastKeyTime > 120) {
                scanBuffer = '';
            }
            lastKeyTime = currentTime;

            if (e.key === 'Enter') {
                if (scanBuffer.length >= 2) {
                    e.preventDefault();
                    if (searchInput) searchInput.value = scanBuffer;
                    handleBarcodeScan(scanBuffer);
                    scanBuffer = '';
                }
            } else if (e.key.length === 1) {
                scanBuffer += e.key;
            }
        });
    });
</script>
@endpush
