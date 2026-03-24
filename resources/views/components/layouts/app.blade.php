<x-layouts.app.sidebar :title="$title ?? null">
    <flux:main>
        {{ $slot }}
        <livewire:components.confirm-modal />
        <livewire:components.toast />
    </flux:main>
</x-layouts.app.sidebar>
