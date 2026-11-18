@php
    $relativePath = 'images/userguideline/'.$file;
    $localPath = public_path($relativePath);
@endphp
@if(is_file($localPath))
<figure class="guide-screenshot">
    <img src="{{ ($standalone ?? false) ? 'data:image/png;base64,'.base64_encode(file_get_contents($localPath)) : asset($relativePath) }}" alt="{{ $alt }}" loading="lazy">
    <figcaption>{{ $caption }}</figcaption>
</figure>
@endif
