{{-- resources/views/feedbacks/create.blade.php --}}
@extends('layouts.app-sidebar')

@section('title', 'Buat Feedback Baru')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden">
        <div class="bg-green-600 px-6 py-4">
            <h1 class="text-xl font-semibold text-white">Buat Feedback Baru</h1>
            <p class="text-green-100 text-sm">Sampaikan saran atau masukan Anda</p>
        </div>
        <form action="{{ route('feedbacks.store') }}" method="POST" class="p-6 space-y-5">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Judul</label>
                <input type="text" name="subject" value="{{ old('subject') }}" required
                       class="w-full rounded-xl border-gray-200 focus:border-green-400 focus:ring-green-200"
                       placeholder="Contoh: Usulan fitur baru">
                @error('subject') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pesan</label>
                <textarea name="message" rows="6" required
                          class="w-full rounded-xl border-gray-200 focus:border-green-400 focus:ring-green-200"
                          placeholder="Tulis detail feedback Anda...">{{ old('message') }}</textarea>
                @error('message') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white rounded-xl py-2 font-semibold transition">
                    <i class="fas fa-paper-plane mr-2"></i> Kirim
                </button>
                <a href="{{ route('feedbacks.index') }}" class="px-4 py-2 border border-gray-300 rounded-xl text-gray-600 hover:bg-gray-50 transition">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection