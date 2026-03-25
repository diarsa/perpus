<?php

use App\Models\UserActivity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public function with()
    {
        return [
            'activities' => Auth::user()->activities()
                ->latest()
                ->paginate(10),
        ];
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout heading="Log Aktivitas" subheading="Tinjau aktivitas login dan tindakan akun Anda">
        <div class="mt-6 space-y-4">
            @forelse ($activities as $activity)
                <div class="flex items-start gap-4 p-4 rounded-xl border border-zinc-100 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm hover:border-zinc-200 dark:hover:border-zinc-700 transition-colors">
                    <div @class([
                        'p-2 rounded-lg shrink-0',
                        'bg-blue-50 dark:bg-blue-900/20 text-blue-600' => $activity->type === 'login',
                        'bg-amber-50 dark:bg-amber-900/20 text-amber-600' => $activity->type === 'logout',
                        'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600' => $activity->type === 'created',
                        'bg-purple-50 dark:bg-purple-900/20 text-purple-600' => $activity->type === 'updated',
                        'bg-red-50 dark:bg-red-900/20 text-red-600' => $activity->type === 'deleted',
                        'bg-zinc-50 dark:bg-zinc-800 text-zinc-600' => !in_array($activity->type, ['login', 'logout', 'created', 'updated', 'deleted']),
                    ])>
                        @switch($activity->type)
                            @case('login')
                                <flux:icon.arrow-right-start-on-rectangle class="size-5" />
                                @break
                            @case('logout')
                                <flux:icon.arrow-left-start-on-rectangle class="size-5" />
                                @break
                            @case('created')
                                <flux:icon.plus class="size-5" />
                                @break
                            @case('updated')
                                <flux:icon.pencil-square class="size-5" />
                                @break
                            @case('deleted')
                                <flux:icon.trash class="size-5" />
                                @break
                            @default
                                <flux:icon.information-circle class="size-5" />
                        @endswitch
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-bold text-zinc-900 dark:text-zinc-100 uppercase tracking-tight">
                                {{ str_replace('_', ' ', $activity->type) }}
                            </span>
                            <span class="text-[10px] font-medium text-zinc-400">
                                {{ $activity->created_at->diffForHumans() }}
                            </span>
                        </div>
                        <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-0.5 leading-relaxed">
                            {{ $activity->description }}
                        </p>

                        {{-- Perubahan Data --}}
                        @if(isset($activity->properties['before']) && isset($activity->properties['after']))
                            <div class="mt-3 overflow-hidden rounded-lg border border-zinc-100 dark:border-zinc-800">
                                <table class="w-full text-[10px] text-left">
                                    <thead class="bg-zinc-50 dark:bg-zinc-800/50 text-zinc-400 uppercase tracking-tighter">
                                        <tr>
                                            <th class="px-2 py-1 font-black">Field</th>
                                            <th class="px-2 py-1 font-black">Dari</th>
                                            <th class="px-2 py-1 font-black">Menjadi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-50 dark:divide-zinc-800">
                                        @foreach($activity->properties['after'] as $key => $value)
                                            @if(!in_array($key, ['updated_at', 'created_at']))
                                            <tr>
                                                <td class="px-2 py-1.5 font-bold text-zinc-400 bg-zinc-50/50 dark:bg-zinc-800/30 uppercase tracking-tighter w-20">{{ $key }}</td>
                                                <td class="px-2 py-1.5 text-zinc-400 line-through opacity-60">{{ is_array($activity->properties['before'][$key] ?? null) ? json_encode($activity->properties['before'][$key]) : ($activity->properties['before'][$key] ?? '-') }}</td>
                                                <td class="px-2 py-1.5 text-zinc-900 dark:text-zinc-100 font-bold bg-blue-50/20 dark:bg-blue-900/10">{{ is_array($value) ? json_encode($value) : $value }}</td>
                                            </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @elseif(in_array($activity->type, ['created', 'deleted']))
                            <div x-data="{ open: false }" class="mt-2">
                                <button @click="open = !open" class="text-[9px] font-black text-blue-600 uppercase tracking-tighter hover:underline flex items-center gap-1">
                                    <flux:icon.chevron-right class="size-2.5 transition-transform" x-bind:class="open ? 'rotate-90' : ''" />
                                    <span x-text="open ? 'Sembunyikan Data' : 'Lihat Detail Data'"></span>
                                </button>
                                <div x-show="open" x-collapse class="mt-2 p-2.5 rounded-lg bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-100 dark:border-zinc-800 text-[10px] space-y-1.5">
                                     @foreach($activity->properties as $key => $value)
                                        @if(!in_array($key, ['model', 'id', 'updated_at', 'created_at']) && !is_array($value))
                                            <div class="flex gap-2 border-b border-zinc-100 dark:border-zinc-800 pb-1 last:border-0 last:pb-0">
                                                <span class="font-bold text-zinc-400 w-24 shrink-0 uppercase tracking-tighter text-[9px]">{{ $key }}</span>
                                                <span class="text-zinc-700 dark:text-zinc-200 truncate">{{ $value }}</span>
                                            </div>
                                        @endif
                                     @endforeach
                                </div>
                            </div>
                        @endif
                        
                        <div class="mt-2 flex items-center gap-3 text-[10px] text-zinc-400 font-medium">
                            <span class="flex items-center gap-1">
                                <flux:icon.globe-alt class="size-3" />
                                {{ $activity->ip_address }}
                            </span>
                            <span class="flex items-center gap-1 truncate max-w-[200px]" title="{{ $activity->user_agent }}">
                                <flux:icon.computer-desktop class="size-3" />
                                {{ Str::limit($activity->user_agent, 40) }}
                            </span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-12 text-center">
                    <flux:icon.clock class="size-12 mx-auto text-zinc-200 dark:text-zinc-800 mb-4" />
                    <p class="text-sm text-zinc-500 font-medium">Belum ada aktivitas yang tercatat.</p>
                </div>
            @endforelse

            <div class="mt-6">
                {{ $activities->links() }}
            </div>
        </div>
    </x-settings.layout>
</section>
