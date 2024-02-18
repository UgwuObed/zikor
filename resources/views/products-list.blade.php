

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Products List') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="font-semibold text-lg mb-4">Products</h3>
                    @foreach ($products as $product)
                        <div class="flex mb-4">
                            <div class="mr-4">
                                <img src="{{ asset($product->image) }}" alt="{{ $product->name }}" class="w-32 h-32 object-cover rounded">
                            </div>
                            <div>
                                <h4 class="font-semibold">{{ $product->name }}</h4>
                                <p class="text-gray-600">Price: ${{ $product->main_price }}</p>
                                <!-- Add more product details here -->
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

