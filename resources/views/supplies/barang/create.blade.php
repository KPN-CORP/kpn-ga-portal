@extends('layouts.app_supplies_sidebar')
@section('content')
<div class="max-w-2xl">
    <h2 class="text-xl font-semibold mb-4">Tambah Barang</h2>
    <div class="bg-white border rounded-xl p-6">
        <form method="POST" action="{{ route('supplies.barang.store') }}">
            @csrf
            @include('supplies.barang._form')
            <div class="flex justify-end gap-2 mt-4">
                <a href="{{ route('supplies.barang.index') }}" class="px-4 py-2 bg-gray-200 rounded-lg">Batal</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection