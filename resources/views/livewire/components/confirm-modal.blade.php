<?php

use Livewire\Volt\Component;

new class extends Component {
    public $isOpen = false;
    public $title = 'Konfirmasi';
    public $message = 'Apakah Anda yakin ingin melanjutkan?';
    public $actionEvent = '';
    public $actionParams = [];

    protected $listeners = ['showConfirmModal' => 'show'];

    public function show($title, $message, $actionEvent, $actionParams = [])
    {
        $this->title = $title;
        $this->message = $message;
        $this->actionEvent = $actionEvent;
        $this->actionParams = is_array($actionParams) ? $actionParams : [$actionParams];
        $this->isOpen = true;
    }

    public function confirm()
    {
        $this->dispatch($this->actionEvent, ...$this->actionParams);
        $this->isOpen = false;
    }

    public function cancel()
    {
        $this->isOpen = false;
    }
}; ?>

<flux:modal wire:model="isOpen" class="w-full max-w-sm">
    <div class="p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="p-2 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-full">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <h2 class="text-xl font-bold">{{ $title }}</h2>
        </div>
        <div class="space-y-6">
            <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $message }}</p>
            
            <div class="flex justify-end gap-3 mt-8">
                <flux:button variant="ghost" wire:click="cancel">Batal</flux:button>
                <flux:button variant="danger" wire:click="confirm">Ya, Hapus</flux:button>
            </div>
        </div>
    </div>
</flux:modal>
