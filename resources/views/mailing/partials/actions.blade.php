<div class="flex gap-2 text-xs">
    @if($m->mailing_status=='Mailing Room')
    <form method="POST" action="{{ route('mailing.lantai47',$m->id_mailing) }}">
        @csrf
        <button class="bg-indigo-600 text-white px-3 py-1 rounded">Lantai 47</button>
    </form>
    @endif

    @if($m->mailing_status=='Lantai 47')
    <form method="POST" action="{{ route('mailing.selesai',$m->id_mailing) }}"
          enctype="multipart/form-data"
          class="flex gap-1 items-center">
        @csrf
        <input type="file" name="mailing_foto" required class="text-xs">
        <input type="text" name="mailing_penerima_distribusi" 
               placeholder="Penerima" required class="border rounded px-2 py-1 text-xs">
        <button class="bg-green-600 text-white px-3 py-1 rounded text-xs">Selesai</button>
    </form>
    @endif
</div>