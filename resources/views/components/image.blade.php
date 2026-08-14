@props([
    'src',
    'alt' => '',
    'width' => null,
    'height' => null,
    'sizes' => '100vw',
    'isLcp' => false,
    'class' => '',
    'id' => null
])

@php
    $pathInfo = pathinfo($src);
    $dirname = $pathInfo['dirname'] === '.' ? '' : $pathInfo['dirname'] . '/';
    $filename = $pathInfo['filename'];

    $avifSrc = asset($dirname . $filename . '.avif');
    $webpSrc = asset($dirname . $filename . '.webp');
    $fallbackSrc = asset($src);
@endphp

<picture>
    <source type="image/avif" srcset="{{ $avifSrc }}" sizes="{{ $sizes }}">
    <source type="image/webp" srcset="{{ $webpSrc }}" sizes="{{ $sizes }}">
    <img 
        src="{{ $fallbackSrc }}" 
        alt="{{ $alt }}"
        @if($id) id="{{ $id }}" @endif
        @if($width) width="{{ $width }}" @endif
        @if($height) height="{{ $height }}" @endif
        class="{{ $class }}"
        @if($isLcp)
            fetchpriority="high"
            loading="eager"
            decoding="sync"
        @else
            loading="lazy"
            decoding="async"
        @endif
    />
</picture>
