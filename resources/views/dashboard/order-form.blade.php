@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Create New Order</h5>
            </div>
            <div class="card-body">
                <form id="order-form" autocomplete="off">
                    <div class="mb-3">
                        <label for="product" class="form-label">Product</label>
                        <select class="form-select" id="product" name="product_id" required>
                            <option value="">Select a product</option>
                            @foreach($products as $product)
                            <option value="{{ $product->id }}" data-price="{{ $product->price }}">
                                {{ $product->name }} - ${{ number_format($product->price, 2) }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" min="1" value="1"
                            required>
                    </div>

                    <div class="mb-3">
                        <label for="price" class="form-label">Price Per Unit</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="price" name="price" step="0.01" readonly
                                required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="total" class="form-label">Total Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="text" class="form-control" id="total" readonly>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Create Order</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Order Confirmation -->
        <div id="order-confirmation" class="alert alert-success mt-3 d-none">
            <h5>Order Created Successfully!</h5>
            <p>Your order has been processed and added to the system.</p>
            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
                <a href="{{ route('dashboard') }}" class="btn btn-primary me-md-2">
                    Go to Dashboard
                </a>
                <button id="new-order-btn" class="btn btn-outline-primary">
                    Create Another Order
                </button>
            </div>
        </div>

        <!-- Error Message -->
        <div id="error-message" class="alert alert-danger mt-3 d-none"></div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const productSelect = document.getElementById('product');
        const quantityInput = document.getElementById('quantity');
        const priceInput = document.getElementById('price');
        const totalInput = document.getElementById('total');
        const orderForm = document.getElementById('order-form');
        const confirmationDiv = document.getElementById('order-confirmation');
        const errorDiv = document.getElementById('error-message');
        const newOrderBtn = document.getElementById('new-order-btn');

        // Update price when product changes
        productSelect.addEventListener('change', updatePrice);

        // Update total when quantity changes
        quantityInput.addEventListener('input', updateTotal);

        // Form submission
        orderForm.addEventListener('submit', submitOrder);

        // New order button
        newOrderBtn.addEventListener('click', resetForm);

        function updatePrice() {
            const selectedOption = productSelect.options[productSelect.selectedIndex];
            const price = selectedOption.getAttribute('data-price') || 0;
            priceInput.value = parseFloat(price).toFixed(2);
            updateTotal();
        }

        function updateTotal() {
            const price = parseFloat(priceInput.value) || 0;
            const quantity = parseInt(quantityInput.value) || 0;
            const total = price * quantity;
            totalInput.value = total.toFixed(2);
        }

        function submitOrder(e) {
            e.preventDefault();

            // Disable the form during submission
            setFormState(true);

            // Get form data
            const formData = {
                product_id: productSelect.value,
                quantity: quantityInput.value,
                price: priceInput.value
            };

            // Submit via API
            fetch('/api/orders', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.errors) {
                    // Show validation errors
                    let errorText = 'Please correct the following errors:';
                    for (const [field, errors] of Object.entries(data.errors)) {
                        errorText += `<br>- ${errors[0]}`;
                    }
                    showError(errorText);
                } else {
                    // Show success message
                    showConfirmation();
                }
            })
            .catch(error => {
                console.error('Error creating order:', error);
                showError('An error occurred while creating the order. Please try again.');
            })
            .finally(() => {
                setFormState(false);
            });
        }

        function showConfirmation() {
            orderForm.classList.add('d-none');
            confirmationDiv.classList.remove('d-none');
            errorDiv.classList.add('d-none');
        }

        function showError(message) {
            errorDiv.innerHTML = message;
            errorDiv.classList.remove('d-none');
            confirmationDiv.classList.add('d-none');
        }

        function resetForm() {
            orderForm.reset();
            orderForm.classList.remove('d-none');
            confirmationDiv.classList.add('d-none');
            errorDiv.classList.add('d-none');
            updatePrice();
        }

        function setFormState(isSubmitting) {
            const submitBtn = orderForm.querySelector('button[type="submit"]');
            submitBtn.disabled = isSubmitting;
            submitBtn.innerHTML = isSubmitting
                ? '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...'
                : 'Create Order';
        }

        // Initialize price and total
        updatePrice();
    });
</script>
@endsection
