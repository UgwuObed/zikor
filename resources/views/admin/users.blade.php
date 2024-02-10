<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Users and Their Products') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="font-semibold text-lg mb-4">Users</h3>
                    @foreach ($usersWithProducts as $user)
                        <div class="mb-4">
                            <h4 class="font-semibold">{{ $user->name }}</h4>
                            <p class="text-gray-600">{{ $user->email }}</p>
                            <p class="text-gray-600">{{ $user->business_name }}</p>
                            <h5 class="font-semibold mt-2">Products:</h5>
                            <ul>
                                @foreach ($user->products as $product)
                                    <li>{{ $product->name }} - ₦{{ number_format($product->main_price, 2) }}</li>

                            <form action="{{ route('admin.product.delete', ['userId' => $user->id, 'productId' => $product->id]) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">Delete Product</button>
                    </form>
                                @endforeach
                            </ul>
                        </div>
                 <form action="{{ route('admin.user.delete', $user->id) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Delete User</button>
                            </form>
                            <hr>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

