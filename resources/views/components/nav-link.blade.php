@props(['active'])

<style>
a.nav-text {
    position: relative;
    transition: color 180ms ease, letter-spacing 180ms ease;
}
a.nav-text::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: -2px;
    height: 2px;
    width: 0;
    background: currentColor;
    transition: width 180ms ease;
}
a.nav-text:hover {
    letter-spacing: 0.2px;
}
a.nav-text:hover::after {
    width: 100%;
}
a.nav-text.active::after {
    width: 100%;
}
</style>

@php
$classes = ($active ?? false)
            ? 'nav-text active inline-flex items-center px-1 pt-1 text-sm font-semibold leading-5 text-blue-700 focus:outline-none transition-colors duration-150 ease-out'
            : 'nav-text inline-flex items-center px-1 pt-1 text-sm font-medium leading-5 text-gray-600 hover:text-blue-700 focus:outline-none transition-colors duration-150 ease-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
