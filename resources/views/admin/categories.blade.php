<x-app-layout>
<form action="{{ route('admin.categories.create') }}" method="post">
    @csrf

    <label for="name">Category Name:</label>
    <input type="text" id="name" name="name" required><br>

    <button type="submit" style="background-color: blue; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">Add Category</button>

</form>

 <h2>Categories</h2>
    @if (count($categories) > 0)
        <ul>
            @foreach ($categories as $category)
                <li>
                    {{ $category->name }}
                </li>
            @endforeach
        </ul>
    @else
        <p>No categories added yet.</p>
    @endif

</x-app-layout>