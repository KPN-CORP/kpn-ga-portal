@extends('layouts.app-sidebar')

@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans">

    <!-- SEARCH & STATS -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- SEARCH -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow p-5 sm:p-6 transition transform hover:-translate-y-1">
                <h2 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <i class="fas fa-search text-indigo-600"></i> Search Employees
                </h2>

                <form method="GET" class="space-y-4">
                    <input type="text" name="search"
                        class="w-full px-4 py-3 border rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                        placeholder="Search name, email, department..."
                        value="{{ request('search') }}">

                    <div class="flex flex-col sm:flex-row gap-3">
                        <button class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-lg transition transform hover:scale-105">
                            <i class="fas fa-search mr-2"></i> Search
                        </button>

                        @if(request('search'))
                        <a href="{{ route('employees.index') }}"
                           class="sm:w-auto text-center bg-gray-100 hover:bg-gray-200 px-4 py-3 rounded-lg transition">
                            Clear
                        </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- STATS -->
        <div class="bg-white rounded-xl shadow p-6 transition transform hover:-translate-y-1">
            <h3 class="font-semibold mb-4 text-gray-800">Quick Stats</h3>
            <div class="space-y-3 text-sm text-gray-600">
                <div class="flex justify-between">
                    <span>Total Employees</span>
                    <span class="font-bold">{{ $employees->total() }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Showing</span>
                    <span class="font-bold">{{ $employees->count() }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Page</span>
                    <span class="font-bold">
                        {{ $employees->currentPage() }}/{{ $employees->lastPage() }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- DESKTOP TABLE -->
    <div class="hidden lg:block bg-white rounded-xl shadow overflow-hidden transition transform hover:-translate-y-1">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                <tr>
                    <th class="px-6 py-3 text-left">No</th>
                    <th class="px-6 py-3 text-left">Employee</th>
                    <th class="px-6 py-3 text-left">Department</th>
                    <th class="px-6 py-3 text-left">Contact</th>
                    <th class="px-6 py-3 text-left">Action</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">
                @foreach($employees as $employee)
                <tr class="hover:bg-gray-50 transition duration-150">
                    <td class="px-6 py-4">
                        {{ $loop->iteration + ($employees->currentPage()-1)*$employees->perPage() }}
                    </td>

                    <td class="px-6 py-4 flex items-center gap-3">
                        <div class="h-10 w-10 rounded-full bg-indigo-500 text-white flex items-center justify-center">
                            {{ substr($employee->nama_pelanggan,0,1) }}
                        </div>
                        <div>
                            <div class="font-semibold text-gray-800">{{ $employee->nama_pelanggan }}</div>
                            <div class="text-sm text-gray-500">{{ $employee->username_pelanggan }}</div>
                        </div>
                    </td>

                    <td class="px-6 py-4 text-gray-600">{{ $employee->departemen ?? '-' }}</td>

                    <td class="px-6 py-4 text-sm text-gray-600">
                        <div><i class="fas fa-envelope mr-1"></i> {{ $employee->email_pelanggan }}</div>
                        <div><i class="fas fa-phone mr-1"></i> {{ $employee->no_hp_pelanggan }}</div>
                    </td>

                    <td class="px-6 py-4">
                        <a href="{{ route('employees.show',$employee->id_pelanggan) }}"
                           class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm transition transform hover:scale-105">
                            View
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- MOBILE / TABLET CARD VIEW -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 lg:hidden">
        @foreach($employees as $employee)
        <div class="bg-white rounded-xl shadow p-4 space-y-3 transition transform hover:-translate-y-1 hover:shadow-lg">
            <div class="flex items-center gap-3">
                <div class="h-12 w-12 rounded-full bg-indigo-500 text-white flex items-center justify-center">
                    {{ substr($employee->nama_pelanggan,0,1) }}
                </div>
                <div>
                    <div class="font-semibold text-gray-800">{{ $employee->nama_pelanggan }}</div>
                    <div class="text-sm text-gray-500">{{ $employee->departemen ?? '-' }}</div>
                </div>
            </div>

            <div class="text-sm text-gray-600 space-y-1">
                <div><i class="fas fa-envelope mr-1"></i> {{ $employee->email_pelanggan }}</div>
                <div><i class="fas fa-phone mr-1"></i> {{ $employee->no_hp_pelanggan }}</div>
            </div>

            <a href="{{ route('employees.show',$employee->id_pelanggan) }}"
               class="block text-center bg-indigo-600 hover:bg-indigo-700 text-white py-2 rounded-lg text-sm transition transform hover:scale-105">
                View Detail
            </a>
        </div>
        @endforeach
    </div>

    <!-- PAGINATION -->
    @if($employees->hasPages())
    <div class="flex flex-col sm:flex-row justify-between items-center gap-3 text-sm mt-4">
        <div class="text-gray-600">
            {{ $employees->firstItem() }} - {{ $employees->lastItem() }} of {{ $employees->total() }}
        </div>
        <div>
            {{ $employees->links('pagination::tailwind') }}
        </div>
    </div>
    @endif

</div>
@endsection
