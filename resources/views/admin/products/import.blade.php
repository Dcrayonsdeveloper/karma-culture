{{-- Every other admin screen renders through the x-layouts.admin component.
     This one alone extended admin.layouts.app, a Blade layout that does not
     exist in this codebase, so the page threw "View [admin.layouts.app] not
     found" the moment its route became reachable. --}}
<x-layouts.admin>
    <x-slot name="title">Import Products</x-slot>

@push('styles')
{{-- This screen's markup is Bootstrap; the admin is Tailwind and does not load
     Bootstrap at all, so none of these classes meant anything. That was not
     only cosmetic - the page drives itself with `d-none`, so the spinner, the
     empty-state text and the results table were all on screen at once, with no
     way to hide any of them. Rather than rewrite 400 lines of markup, this
     defines the handful of classes the page actually uses, scoped to
     .kk-import so nothing here can reach the rest of the admin. --}}
<style>
.kk-import { --kk-i-line: #e5e7eb; --kk-i-muted: #6b7280; }
.kk-import .d-none { display: none !important; }
.kk-import .d-flex { display: flex; }
.kk-import .d-inline-block { display: inline-block; }
.kk-import .justify-content-between { justify-content: space-between; }
.kk-import .align-items-center { align-items: center; }
.kk-import .text-center { text-align: center; }
.kk-import .w-auto { width: auto; }
.kk-import .row { display: grid; grid-template-columns: repeat(12, 1fr); gap: 16px; }
.kk-import .col-12 { grid-column: span 12; }
.kk-import .col-md-4 { grid-column: span 12; }
@media (min-width: 768px) { .kk-import .col-md-4 { grid-column: span 4; } }
.kk-import .card { background: #fff; border: 1px solid var(--kk-i-line); border-radius: 12px; overflow: hidden; }
.kk-import .card-header { padding: 14px 18px; border-bottom: 1px solid var(--kk-i-line); background: #fafafa; }
.kk-import .card-body { padding: 18px; }
.kk-import .card-title { font-size: 13px; font-weight: 600; opacity: .85; margin: 0 0 6px; }
.kk-import .bg-primary { background: #6F9CA2; } .kk-import .bg-success { background: #2f855a; }
.kk-import .bg-info { background: #4c7a8c; } .kk-import .text-white, .kk-import .text-white * { color: #fff; }
.kk-import .text-muted { color: var(--kk-i-muted); }
.kk-import .text-primary { color: #6F9CA2; }
.kk-import .text-decoration-line-through { text-decoration: line-through; }
.kk-import h1.h3 { font-size: 20px; font-weight: 600; } .kk-import h5 { font-size: 15px; font-weight: 600; }
.kk-import .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 8px;
  border: 1px solid var(--kk-i-line); background: #fff; font-size: 13px; font-weight: 500; cursor: pointer; }
.kk-import .btn:disabled { opacity: .55; cursor: not-allowed; }
.kk-import .btn-primary { background: #6F9CA2; border-color: #6F9CA2; color: #fff; }
.kk-import .btn-success { background: #2f855a; border-color: #2f855a; color: #fff; }
.kk-import .btn-secondary { background: #f3f4f6; }
.kk-import .btn-outline-primary { border-color: #6F9CA2; color: #6F9CA2; }
.kk-import .btn-sm { padding: 6px 10px; font-size: 12px; }
.kk-import .table { width: 100%; border-collapse: collapse; font-size: 13px; }
.kk-import .table th, .kk-import .table td { padding: 8px 10px; border-bottom: 1px solid var(--kk-i-line); text-align: left; }
.kk-import .table-hover tbody tr:hover { background: #fafafa; }
.kk-import .table-responsive { overflow-x: auto; }
.kk-import .form-select { padding: 7px 10px; border: 1px solid var(--kk-i-line); border-radius: 8px; font-size: 13px; background: #fff; }
.kk-import .form-select-sm { padding: 5px 8px; font-size: 12px; }
.kk-import .form-check-input { width: 15px; height: 15px; }
.kk-import .alert { padding: 10px 14px; border-radius: 8px; font-size: 13px; border: 1px solid var(--kk-i-line); }
.kk-import .alert-success { background: #f0fdf4; border-color: #bbf7d0; color: #166534; }
.kk-import .alert-danger { background: #fef2f2; border-color: #fecaca; color: #991b1b; }
.kk-import .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; background: #f3f4f6; }
.kk-import .progress { background: #f3f4f6; border-radius: 999px; overflow: hidden; }
.kk-import .progress-bar { background: #6F9CA2; color: #fff; font-size: 12px; line-height: 25px; text-align: center; transition: width .3s ease; }
.kk-import .spinner-border { display: inline-block; width: 28px; height: 28px; border: 3px solid #d1d5db;
  border-top-color: #6F9CA2; border-radius: 50%; animation: kk-i-spin .7s linear infinite; }
.kk-import .spinner-border-sm { width: 13px; height: 13px; border-width: 2px; }
@keyframes kk-i-spin { to { transform: rotate(360deg); } }
.kk-import .visually-hidden { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); }
/* Bootstrap Icons are not loaded either; the <i> tags would be empty boxes. */
.kk-import .bi { display: none; }
.kk-import .rounded { border-radius: 8px; }
.kk-import .mb-0 { margin-bottom: 0; } .kk-import .mb-3 { margin-bottom: 12px; } .kk-import .mb-4 { margin-bottom: 20px; }
.kk-import .mt-2 { margin-top: 8px; } .kk-import .mt-3 { margin-top: 12px; }
.kk-import .me-1 { margin-right: 4px; } .kk-import .me-2 { margin-right: 8px; }
.kk-import .py-4 { padding-top: 20px; padding-bottom: 20px; }
</style>
@endpush

<div class="container-fluid kk-import">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">Import Products from House of Rare</h1>
                <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to Products
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Products</h5>
                    <h2 class="mb-0" id="total-products">{{ $stats['total_products'] ?? 0 }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Images</h5>
                    <h2 class="mb-0" id="total-images">{{ $stats['total_images'] ?? 0 }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Categories</h5>
                    <h2 class="mb-0">{{ $stats['total_categories'] ?? 0 }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Setup Categories -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Step 1: Setup Categories</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">Create category structure based on House of Rare (MEN, WOMEN, KIDS with subcategories)</p>
            <button type="button" class="btn btn-outline-primary" id="create-categories-btn">
                <i class="bi bi-folder-plus me-1"></i> Create Categories
            </button>
            <div id="category-result" class="mt-3"></div>
        </div>
    </div>

    <!-- Fetch Products -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Step 2: Fetch & Import Products</h5>
            <div>
                <select id="page-limit" class="form-select form-select-sm d-inline-block w-auto me-2">
                    <option value="25">25 per page</option>
                    <option value="50" selected>50 per page</option>
                    <option value="100">100 per page</option>
                </select>
                <button type="button" class="btn btn-primary btn-sm" id="fetch-products-btn">
                    <i class="bi bi-cloud-download me-1"></i> Fetch Products
                </button>
            </div>
        </div>
        <div class="card-body">
            <div id="loading" class="text-center py-4 d-none">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Fetching products from House of Rare...</p>
            </div>
            
            <div id="products-container">
                <p class="text-muted">Click "Fetch Products" to load products from thehouseofrare.com</p>
            </div>

            <div id="products-list" class="d-none">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="40">
                                    <input type="checkbox" id="select-all" class="form-check-input">
                                </th>
                                <th width="80">Image</th>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Category</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="products-tbody">
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        <span id="selected-count">0</span> selected
                    </div>
                    <div>
                        <button type="button" class="btn btn-secondary btn-sm me-2" id="load-more-btn">
                            <i class="bi bi-arrow-down me-1"></i> Load More
                        </button>
                        <button type="button" class="btn btn-success" id="import-selected-btn" disabled>
                            <i class="bi bi-download me-1"></i> Import Selected
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Progress -->
    <div class="card d-none" id="import-progress-card">
        <div class="card-header">
            <h5 class="mb-0">Import Progress</h5>
        </div>
        <div class="card-body">
            <div class="progress mb-3" style="height: 25px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" 
                     style="width: 0%;" id="import-progress-bar">0%</div>
            </div>
            <div id="import-status" class="text-muted"></div>
            <div id="import-results" class="mt-3"></div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const categories = @json($categories ?? []);
    let currentPage = 1;
    let allProducts = [];
    
    // Create Categories
    document.getElementById('create-categories-btn').addEventListener('click', async function() {
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Creating...';
        
        try {
            const response = await fetch('{{ route("admin.products.import-external.create-categories") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            
            const data = await response.json();
            document.getElementById('category-result').innerHTML = 
                '<div class="alert alert-success">' + data.message + '</div>';
            
            // Reload page to refresh categories
            setTimeout(() => location.reload(), 2000);
        } catch (error) {
            document.getElementById('category-result').innerHTML = 
                '<div class="alert alert-danger">Error: ' + error.message + '</div>';
        }
        
        this.disabled = false;
        this.innerHTML = '<i class="bi bi-folder-plus me-1"></i> Create Categories';
    });
    
    // Fetch Products
    document.getElementById('fetch-products-btn').addEventListener('click', async function() {
        currentPage = 1;
        allProducts = [];
        await fetchProducts();
    });
    
    // Load More
    document.getElementById('load-more-btn').addEventListener('click', async function() {
        currentPage++;
        await fetchProducts(true);
    });
    
    async function fetchProducts(append = false) {
        const limit = document.getElementById('page-limit').value;
        document.getElementById('loading').classList.remove('d-none');
        document.getElementById('products-container').classList.add('d-none');
        
        try {
            const response = await fetch(`{{ route("admin.products.import-external.fetch") }}?page=${currentPage}&limit=${limit}`);
            const data = await response.json();
            
            if (append) {
                allProducts = [...allProducts, ...data.products];
            } else {
                allProducts = data.products;
            }
            
            renderProducts(allProducts);
            
            document.getElementById('load-more-btn').style.display = data.has_more ? 'inline-block' : 'none';
            
        } catch (error) {
            alert('Error fetching products: ' + error.message);
        }
        
        document.getElementById('loading').classList.add('d-none');
        document.getElementById('products-list').classList.remove('d-none');
    }
    
    function renderProducts(products) {
        const tbody = document.getElementById('products-tbody');
        tbody.innerHTML = '';
        
        products.forEach((product, index) => {
            const image = product.images?.[0]?.src || '';
            const price = product.variants?.[0]?.price || '0';
            const comparePrice = product.variants?.[0]?.compare_at_price || '';
            
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <input type="checkbox" class="form-check-input product-checkbox" 
                           data-index="${index}" ${product.already_exists ? 'disabled' : ''}>
                </td>
                <td>
                    <img src="${image}" alt="" style="width: 60px; height: 60px; object-fit: cover;" 
                         class="rounded" onerror="this.src='/images/no-product-image.svg'">
                </td>
                <td>
                    <strong>${escapeHtml(product.title)}</strong>
                    <br><small class="text-muted">${product.product_type || 'No type'}</small>
                </td>
                <td>
                    <strong>₹${parseFloat(price).toFixed(2)}</strong>
                    ${comparePrice ? `<br><small class="text-muted text-decoration-line-through">₹${parseFloat(comparePrice).toFixed(2)}</small>` : ''}
                </td>
                <td>
                    <select class="form-select form-select-sm category-select" data-index="${index}" 
                            ${product.already_exists ? 'disabled' : ''}>
                        ${getCategoryOptions(product.suggested_category)}
                    </select>
                </td>
                <td>
                    ${product.already_exists 
                        ? '<span class="badge bg-secondary">Already Imported</span>' 
                        : '<span class="badge bg-success">Available</span>'}
                </td>
            `;
            tbody.appendChild(tr);
        });
        
        updateSelectedCount();
    }
    
    function getCategoryOptions(selectedId) {
        let options = '<option value="">-- Select Category --</option>';
        
        // Group by parent
        const parents = categories.filter(c => !c.parent_id);
        parents.forEach(parent => {
            const children = categories.filter(c => c.parent_id === parent.id);
            if (children.length > 0) {
                options += `<optgroup label="${parent.name}">`;
                children.forEach(child => {
                    options += `<option value="${child.id}" ${child.id == selectedId ? 'selected' : ''}>${child.name}</option>`;
                });
                options += '</optgroup>';
            } else {
                options += `<option value="${parent.id}" ${parent.id == selectedId ? 'selected' : ''}>${parent.name}</option>`;
            }
        });
        
        return options;
    }
    
    // Select All
    document.getElementById('select-all').addEventListener('change', function() {
        document.querySelectorAll('.product-checkbox:not(:disabled)').forEach(cb => {
            cb.checked = this.checked;
        });
        updateSelectedCount();
    });
    
    // Individual checkbox
    document.getElementById('products-tbody').addEventListener('change', function(e) {
        if (e.target.classList.contains('product-checkbox')) {
            updateSelectedCount();
        }
    });
    
    function updateSelectedCount() {
        const count = document.querySelectorAll('.product-checkbox:checked').length;
        document.getElementById('selected-count').textContent = count;
        document.getElementById('import-selected-btn').disabled = count === 0;
    }
    
    // Import Selected
    document.getElementById('import-selected-btn').addEventListener('click', async function() {
        const selectedProducts = [];
        document.querySelectorAll('.product-checkbox:checked').forEach(cb => {
            const index = cb.dataset.index;
            const categorySelect = document.querySelector(`.category-select[data-index="${index}"]`);
            const categoryId = categorySelect.value;
            
            if (!categoryId) {
                alert('Please select a category for all selected products');
                return;
            }
            
            selectedProducts.push({
                ...allProducts[index],
                category_id: parseInt(categoryId)
            });
        });
        
        if (selectedProducts.length === 0) return;
        
        // Show progress
        document.getElementById('import-progress-card').classList.remove('d-none');
        this.disabled = true;
        
        const progressBar = document.getElementById('import-progress-bar');
        const statusDiv = document.getElementById('import-status');
        const resultsDiv = document.getElementById('import-results');
        
        // Import in batches
        const batchSize = 5;
        let imported = 0, skipped = 0, errors = [];
        
        for (let i = 0; i < selectedProducts.length; i += batchSize) {
            const batch = selectedProducts.slice(i, i + batchSize);
            
            try {
                const response = await fetch('{{ route("admin.products.import-external.import") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ products: batch })
                });
                
                const result = await response.json();
                imported += result.imported;
                skipped += result.skipped;
                errors = [...errors, ...result.errors];
                
            } catch (error) {
                errors.push({ title: 'Batch error', error: error.message });
            }
            
            const progress = Math.round(((i + batch.length) / selectedProducts.length) * 100);
            progressBar.style.width = progress + '%';
            progressBar.textContent = progress + '%';
            statusDiv.textContent = `Processing... ${i + batch.length} of ${selectedProducts.length}`;
        }
        
        // Show results
        statusDiv.textContent = 'Import complete!';
        resultsDiv.innerHTML = `
            <div class="alert alert-info">
                <strong>Results:</strong><br>
                ✅ Imported: ${imported}<br>
                ⏭️ Skipped: ${skipped}<br>
                ❌ Errors: ${errors.length}
            </div>
            ${errors.length > 0 ? `
                <div class="alert alert-warning">
                    <strong>Errors:</strong><br>
                    ${errors.map(e => `• ${e.title}: ${e.error}`).join('<br>')}
                </div>
            ` : ''}
        `;
        
        // Update stats
        document.getElementById('total-products').textContent = 
            parseInt(document.getElementById('total-products').textContent) + imported;
        
        this.disabled = false;
        
        // Refresh products list
        await fetchProducts();
    });
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>
@endpush
</x-layouts.admin>
