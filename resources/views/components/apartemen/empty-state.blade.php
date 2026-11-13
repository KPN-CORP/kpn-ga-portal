@props(['title', 'text' => null, 'resetUrl' => null])
<div class="ap-empty">
    <div class="ap-empty-ico"><i class="fas fa-inbox"></i></div>
    <h3>{{ $title }}</h3>
    @if($text)<p>{{ $text }}</p>@endif
    @if($resetUrl)
        <a href="{{ $resetUrl }}" class="ap-btn ap-btn-lg ap-btn-blue mt-4">Reset filter</a>
    @endif
</div>