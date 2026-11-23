{{-- Banner alasan revision. Dipakai di show & edit. Param: $record (certificate/equipment) --}}
@if($record->status_verif === 'revision' && !empty($record->revision_reason))
<div class="max-w-3xl mx-auto mb-4 border-l-4 border-red-500 bg-red-50 p-4 rounded">
    <p class="font-semibold text-red-800"><i class="fas fa-exclamation-circle mr-1"></i> Revision requested</p>
    <p class="text-sm text-red-900 mt-1">{{ $record->revision_reason }}</p>
</div>
@endif
