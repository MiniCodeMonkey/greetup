@props(['tabs'])

<nav {{ $attributes->merge(['class' => 'overflow-x-auto [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden']) }} style="border-bottom: 0.5px solid var(--color-neutral-200);">
    <div class="flex" style="gap: 16px;">
        @foreach($tabs as $tab)
            <a
                href="{{ $tab['href'] }}"
                class="shrink-0 pb-2 {{ $tab['active'] ?? false ? 'text-green-500 font-medium' : 'text-neutral-500' }}"
                style="font-size: 13px;{{ ($tab['active'] ?? false) ? ' border-bottom: 2px solid var(--color-green-500);' : '' }}"
            >
                {{ $tab['label'] }}
            </a>
        @endforeach
    </div>
</nav>
