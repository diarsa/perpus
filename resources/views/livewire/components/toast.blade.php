<?php

use Livewire\Volt\Component;

new class extends Component {
    public $isOpen = false;
    public $title = '';
    public $message = '';
    public $type = 'success';

    protected $listeners = ['showToast' => 'show'];

    public function show($type = 'success', $title = 'Notifikasi', $message = '')
    {
        $this->type = $type;
        $this->title = $title;
        $this->message = $message;
        $this->isOpen = true;
    }

    public function close()
    {
        $this->isOpen = false;
    }
}; ?>

<template x-teleport="body">
<div
    x-data="{
        open: @entangle('isOpen'),
        timer: null,
        start() {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => { this.open = false; }, 3500);
        }
    }"
    x-init="$watch('open', val => { if (val) start() })"
    x-show="open"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 -translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 -translate-y-4"
    style="position:fixed !important; top:24px !important; right:24px !important; z-index:999999; width:320px;"
>
    <div class="flex items-start gap-4 rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.15)] px-5 py-4 border-l-4 backdrop-blur-md transition-all duration-500
        {{ $type === 'success' ? 'bg-white/90 dark:bg-zinc-900/90 border-green-500 text-zinc-800 dark:text-zinc-100' : '' }}
        {{ $type === 'error' ? 'bg-white/90 dark:bg-zinc-900/90 border-red-500 text-zinc-800 dark:text-zinc-100' : '' }}
        {{ $type === 'warning' ? 'bg-white/90 dark:bg-zinc-900/90 border-amber-500 text-zinc-800 dark:text-zinc-100' : '' }}
        {{ $type === 'info' ? 'bg-white/90 dark:bg-zinc-900/90 border-blue-500 text-zinc-800 dark:text-zinc-100' : '' }}">
        
        <div class="mt-0.5 flex-shrink-0">
            @if($type === 'success')
                <div class="bg-green-100 dark:bg-green-900/30 p-2 rounded-xl text-green-600 dark:text-green-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </div>
            @elseif($type === 'error')
                <div class="bg-red-100 dark:bg-red-900/30 p-2 rounded-xl text-red-600 dark:text-red-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
            @elseif($type === 'warning')
                <div class="bg-amber-100 dark:bg-amber-900/30 p-2 rounded-xl text-amber-600 dark:text-amber-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
            @else
                <div class="bg-blue-100 dark:bg-blue-900/30 p-2 rounded-xl text-blue-600 dark:text-blue-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            @endif
        </div>

        <div class="flex-1 min-w-0 pr-2">
            <p class="font-bold text-sm tracking-tight capitalize">{{ $title }}</p>
            <p class="text-[13px] mt-1 text-zinc-500 dark:text-zinc-400 leading-snug">{{ $message }}</p>
        </div>

        <button @click="open = false" class="flex-shrink-0 -mt-1 -mr-1 p-1 text-zinc-400 hover:text-zinc-600 dark:text-zinc-500 dark:hover:text-zinc-300 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
</div>
</template>
