
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Upload Product') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div>
                            <label for="name" class="block">Product Name</label>
                            <input type="text" name="name" id="name" class="form-input" required>
                        </div>
                        <div>
                            <label for="main_price" class="block">Main Price</label>
                            <input type="number" name="main_price" id="main_price" class="form-input" required>
                        </div>
                        <div>
                            <label for="discount_price" class="block">Discount Price</label>
                            <input type="number" name="discount_price" id="discount_price" class="form-input">
                        </div>
                        <div>
                            <label for="quantity" class="block">Quantity</label>
                            <input type="number" name="quantity" id="quantity" class="form-input" required>
                        </div>
                        <div>
                            <label for="description" class="block">Description</label>
                            <textarea name="description" id="description" class="form-input" rows="3"></textarea>
                        </div>
                        <!-- Image Upload -->
                        <div>
                            <label for="image" class="block">Product Image</label>
                            <input type="file" name="image" id="image" class="form-input" accept="image/*" required>
                        </div>

                        <button type="submit" class="btn btn-primary">Upload Product</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
