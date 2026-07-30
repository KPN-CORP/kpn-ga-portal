@php $baris = array_reverse(explode('<br>', $waktu)); @endphp
<ol class="relative border-l-2 border-orange-200 ml-2 space-y-4">
  @foreach ($baris as $i => $line)
    <li class="ml-4">
      <span class="absolute -left-[7px] w-3 h-3 rounded-full {{ $i === 0 ? 'bg-[#f36f21]' : 'bg-gray-300' }}"></span>
      <p class="text-sm {{ $i === 0 ? 'font-medium text-gray-800' : 'text-gray-500' }}">{!! $line !!}</p>
    </li>
  @endforeach
</ol>
