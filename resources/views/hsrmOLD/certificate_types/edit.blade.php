@extends('layouts.hsrm-app')

@section('title', 'Edit Equipment Type')
@section('page-title', 'Edit Equipment Type')

@section('content')
<div class="max-w-lg mx-auto bg-white p-6 rounded-lg shadow-sm border">
    <form action="{{ route('hsrm.certificate-types.update', $type) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name', $type->name) }}" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" required>
            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea name="description" rows="3" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">{{ old('description', $type->description) }}</textarea>
            @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>
        <div class="flex gap-3">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">Update</button>
            <a href="{{ route('hsrm.certificate-types.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-lg font-medium">Cancel</a>
        </div>
    </form>
</div>
@endsection