<?php

use Livewire\Volt\Component;

new class extends Component {
    public $isOpen = false;
    public $title = '';
    public $message = '';
    public $type = 'success';

    protected $listeners = ['showToast' => 'show'];

    public function show($title, $message, $type = 'success')
    {
        $this->title = $title;
        $this->message = $message;
        $this->type = $type;
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
    <div class="flex items-start gap-3 rounded-xl shadow-2xl px-4 py-4 border
        {{ $type === 'success' ? 'bg-green-600 border-green-500 text-white' : 'bg-red-600 border-red-500 text-white' }}">
        <div class="mt-0.5 flex-shrink-0">
            @if($type === 'success')
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            @else
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            @endif
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-semibold text-sm">{{ $title }}</p>
            <p class="text-xs mt-0.5 opacity-90">{{ $message }}</p>
        </div>
        <button @click="open = false" class="flex-shrink-0 ml-1 opacity-75 hover:opacity-100 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
</div>
</template>
