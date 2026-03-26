@props([
    'title' => 'Konfirmasi',
    'message' => 'Apakah Anda yakin ingin melakukan tindakan ini?',
    'confirmText' => 'Ya, Lanjutkan',
    'variant' => 'danger',
    'wireAction' => null,
    'alpineAction' => null,
])

<div
    x-show="showConfirm"
    x-on:keydown.escape.window="showConfirm = false"
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    style="display: none;"
    x-cloak
>
    {{-- Backdrop --}}
    <div 
        x-show="showConfirm"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="absolute inset-0 bg-zinc-900/40 backdrop-blur-sm" 
        @click="showConfirm = false"
    ></div>

    {{-- Content --}}
    <div
        x-show="showConfirm"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="relative bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl p-6 w-full max-w-sm z-10 border border-zinc-100 dark:border-zinc-800"
    >
        <div class="flex items-center gap-4 mb-4">
            <div @class([
                'p-3 rounded-xl shrink-0',
                'bg-red-50 dark:bg-red-900/30 text-red-600' => $variant === 'danger',
                'bg-blue-50 dark:bg-blue-900/30 text-blue-600' => $variant === 'primary',
                'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600' => $variant === 'success',
            ])>
                @switch($variant)
                    @case('danger')
                        <flux:icon.trash class="size-6" />
                        @break
                    @case('primary')
                        <flux:icon.information-circle class="size-6" />
                        @break
                    @case('success')
                        <flux:icon.check-circle class="size-6" />
                        @break
                @endswitch
            </div>
            <div>
                <h2 class="text-lg font-black uppercase tracking-tight text-zinc-900 dark:text-zinc-100">{{ $title }}</h2>
            </div>
        </div>

        <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-8 leading-relaxed">
            {{ $message }}
        </p>

        <div class="flex justify-end gap-3">
            <button 
                type="button" 
                @click="showConfirm = false" 
                class="px-4 py-2 text-sm font-bold text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200 transition-colors"
            >
                Batal
            </button>
            <button 
                type="button" 
                @click="{{ $wireAction ? '$wire.' . $wireAction . '; ' : '' }}{{ $alpineAction ? $alpineAction . '; ' : '' }} showConfirm = false" 
                @class([
                    'px-5 py-2.5 text-sm font-black uppercase tracking-tight rounded-xl transition-all shadow-sm flex items-center gap-2',
                    'bg-red-600 hover:bg-red-700 text-white shadow-red-200 dark:shadow-none' => $variant === 'danger',
                    'bg-blue-600 hover:bg-blue-700 text-white shadow-blue-200 dark:shadow-none' => $variant === 'primary',
                    'bg-emerald-600 hover:bg-emerald-700 text-white shadow-emerald-200 dark:shadow-none' => $variant === 'success',
                ])
            >
                @if($variant === 'danger') <flux:icon.trash class="size-4" /> @endif
                {{ $confirmText }}
            </button>
        </div>
    </div>
</div>
