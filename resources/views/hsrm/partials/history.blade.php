{{-- Timeline history satu record. Param: $logs (Collection<HsrmLog>, terbaru di atas) --}}
@php
    $groups = ['all' => 'All', 'created' => 'Created', 'updated' => 'Updated', 'approved' => 'Approved', 'revision' => 'Revision', 'system' => 'System'];
@endphp
<div class="max-w-3xl mx-auto mt-6 bg-white p-6 rounded-lg shadow-sm border" x-data="{ filter: 'all' }">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <h3 class="text-lg font-semibold"><i class="fas fa-history mr-2 text-gray-400"></i>History</h3>
        <div class="flex flex-wrap gap-2">
            @foreach($groups as $key => $label)
                <button type="button" @click="filter = '{{ $key }}'"
                        :class="filter === '{{ $key }}' ? 'bg-blue-700 text-white border-blue-700' : 'bg-white text-gray-700 border-gray-300'"
                        class="px-3 py-1.5 rounded border text-sm">{{ $label }}</button>
            @endforeach
        </div>
    </div>

    @if($logs->isEmpty())
        <p class="text-sm text-gray-500">No history recorded yet.</p>
    @else
        <ol class="border-l-2 border-gray-200 ml-1.5">
            @foreach($logs as $log)
                @php $ev = $log->event; $changes = $log->changes; @endphp
                <li class="relative pl-6 pb-5" x-show="filter === 'all' || filter === '{{ $ev['group'] }}'">
                    <span class="absolute -left-2 top-1 w-3.5 h-3.5 rounded-full bg-white border-2 border-blue-700"></span>
                    <div class="flex flex-wrap items-center gap-2">
                        <strong>{{ $ev['title'] }}</strong>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $ev['badge'] }}">{{ $ev['badge_text'] }}</span>
                    </div>
                    <div class="text-sm text-gray-600 mt-0.5">
                        {{ $log->user->name ?? 'System' }} · {{ $log->created_at ? $log->created_at->format('d M Y, H:i') : '-' }}
                    </div>
                    @if($log->reason)
                        <p class="text-sm mt-2">Reason: {{ $log->reason }}</p>
                    @endif
                    @if(count($changes))
                        <div class="overflow-x-auto mt-2">
                            <table class="w-full text-sm border-collapse">
                                <thead>
                                    <tr class="text-left text-gray-600">
                                        <th class="py-1.5 pr-3 border-b font-semibold">Field</th>
                                        <th class="py-1.5 pr-3 border-b font-semibold">Before</th>
                                        <th class="py-1.5 border-b font-semibold">After</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($changes as $c)
                                        <tr>
                                            <td class="py-1.5 pr-3 border-b">{{ $c['field'] }}</td>
                                            <td class="py-1.5 pr-3 border-b text-red-800 line-through">{{ $c['before'] }}</td>
                                            <td class="py-1.5 border-b text-green-800 font-semibold">{{ $c['after'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </li>
            @endforeach
        </ol>
    @endif
</div>
