@extends('admin.layouts.app')

@section('title', 'Import Products from House of Rare')

@section('content')
<div class="container-fluid">
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
@endsection
