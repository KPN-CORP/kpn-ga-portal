@extends('layouts.hsrm-app')
@section('title', 'Certificate Types')
@section('page-title', 'Manage Certificate Types')

@section('content')
<div class="flex justify-between items-center mb-4">
    @if(\App\Helpers\HsrmRole::isAdmin())
    <a href="{{ route('hsrm.certificate-types.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">
        <i class="fas fa-plus mr-1"></i> Add Type
    </a>
    @else
    <div></div>
    @endif
</div>

<div class="bg-white rounded-lg shadow-sm border overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="bg-gray-50 text-left">
                <th class="p-3">No</th>
                <th class="p-3">Name</th>
                <th class="p-3">Description</th>
                @if(\App\Helpers\HsrmRole::isAdmin())
                <th class="p-3">Actions</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($types as $type)
            <tr class="border-t">
                <td class="p-3">{{ $loop->iteration }}</td>
                <td class="p-3">{{ $type->name }}</td>
                <td class="p-3">{{ $type->description ?? '-' }}</td>
                @if(\App\Helpers\HsrmRole::isAdmin())
                <td class="p-3 flex space-x-2">
                    <a href="{{ route('hsrm.certificate-types.edit', $type) }}" class="text-blue-600 hover:text-blue-800"><i class="fas fa-edit"></i></a>
                    <form action="{{ route('hsrm.certificate-types.destroy', $type) }}" method="POST" onsubmit="return confirm('Delete this type?')" class="inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
                @endif
            </tr>
            @empty
            <tr><td colspan="{{ \App\Helpers\HsrmRole::isAdmin() ? 4 : 3 }}" class="p-4 text-center text-gray-500">No types defined.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection