@php
    $role = session('hsrm_role', 'pic');
    $isAdmin = $role === 'admin';
@endphp

<aside class="sidebar fixed h-full bg-white border-r overflow-y-auto z-50 hidden md:block">
    <div class="p-6 border-b flex items-center space-x-3">
        <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center border">
            <span class="font-bold text-blue-600">HSR</span>
        </div>
        <div>
            <h1 class="text-xl font-bold text-gray-800">HSR Management</h1>
            <p class="text-xs text-gray-500">{{ $isAdmin ? 'Admin' : 'PIC' }}</p>
        </div>
    </div>

    <nav class="p-4">
        <ul class="space-y-1">
            <li>
                <a href="{{ route('hsrm.dashboard') }}" class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('hsrm.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-home mr-3 text-gray-400"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="{{ route('hsrm.certificates.index') }}" class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('hsrm.certificates.*') ? 'active' : '' }}">
                    <i class="fas fa-file-alt mr-3 text-gray-400"></i> Certificates
                </a>
            </li>
            <li>
                <a href="{{ route('hsrm.equipments.index') }}" class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('hsrm.equipments.*') ? 'active' : '' }}">
                    <i class="fas fa-fire-extinguisher mr-3 text-gray-400"></i> Equipments
                </a>
            </li>
            @if($isAdmin)
                <li>
                    <a href="{{ route('hsrm.certificate-types.index') }}" class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('hsrm.certificate-types.*') ? 'active' : '' }}">
                        <i class="fas fa-tags mr-3 text-gray-400"></i> Certificate Types
                    </a>
                </li>
                <li>
                    <a href="{{ route('hsrm.logs.index') }}" class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('hsrm.logs.*') ? 'active' : '' }}">
                        <i class="fas fa-history mr-3 text-gray-400"></i> Logs
                    </a>
                </li>
            @endif
        </ul>
    </nav>

    <div class="absolute bottom-0 left-0 right-0 p-4 border-t bg-white">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center border">
                <span class="font-semibold text-blue-600">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}</span>
            </div>
            <div>
                <h4 class="font-medium text-gray-800">{{ auth()->user()->name }}</h4>
                <p class="text-xs text-gray-500">{{ $isAdmin ? 'Admin' : 'PIC' }}</p>
            </div>
        </div>
    </div>
</aside>