<?php

use App\Models\Publisher;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] #[Title('Data Penerbit')] class extends Component {
    use WithPagination;

    public $search = '';
    public $name = '';
    public $address = '';
    public $publisherId = null;

    public $isModalOpen = false;

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
        ];
    }

    public function create()
    {
        $this->reset(['name', 'address', 'publisherId']);
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        $publisher = Publisher::findOrFail($id);
        $this->publisherId = $publisher->id;
        $this->name = $publisher->name;
        $this->address = $publisher->address;
        
        $this->isModalOpen = true;
    }

    public function save()
    {
        $this->validate();

        Publisher::updateOrCreate(
            ['id' => $this->publisherId],
            [
                'name' => $this->name,
                'address' => $this->address,
            ]
        );

        $this->isModalOpen = false;
        $this->reset(['name', 'address', 'publisherId']);
        $this->dispatch('showToast', 'success', 'Berhasil', 'Data penerbit berhasil disimpan.');
    }

    #[Livewire\Attributes\On('deletePublisher')]
    public function delete($id)
    {
        Publisher::find($id)->delete();
        $this->dispatch('showToast', 'success', 'Berhasil', 'Data penerbit berhasil dihapus.');
    }

    public function with()
    {
        return [
            'publishers' => Publisher::withCount('books')
                        ->where('name', 'like', '%'.$this->search.'%')
                        ->latest()
                        ->paginate(10),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl" x-data="{ deleteId: null, showConfirm: false }">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Data Penerbit</h1>
        <flux:button variant="primary" wire:click="create">Tambah Penerbit</flux:button>
    </div>

    <div class="flex mb-4">
        <flux:input wire:model.live.debounce.300ms="search" autocomplete="off" placeholder="Cari Penerbit..." icon="magnifying-glass" class="w-full md:w-1/3" />
    </div>

    <div class="bg-white dark:bg-zinc-900 shadow rounded-lg overflow-hidden">
        <table class="w-full text-sm text-left">
            <thead class="text-xs uppercase bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th class="px-6 py-3">Nama Penerbit</th>
                    <th class="px-6 py-3">Alamat</th>
                    <th class="px-6 py-3 text-center">Jumlah Buku</th>
                    <th class="px-6 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($publishers as $publisher)
                    <tr class="border-b dark:border-zinc-700 hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors">
                        <td class="px-6 py-4 font-medium text-zinc-900 dark:text-white">{{ $publisher->name }}</td>
                        <td class="px-6 py-4 text-xs text-zinc-500">{{ Str::limit($publisher->address ?? '-', 100) }}</td>
                        <td class="px-6 py-4 text-center text-zinc-600 dark:text-zinc-400">{{ $publisher->books_count }} buku</td>
                        <td class="px-6 py-4 text-right">
                            <flux:button size="sm" variant="outline" wire:click="edit({{ $publisher->id }})">Edit</flux:button>
                            <flux:button size="sm" variant="danger" @click="deleteId = {{ $publisher->id }}; showConfirm = true">Hapus</flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-zinc-500">Pencarian tidak menemukan data penerbit.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">
            {{ $publishers->links() }}
        </div>
    </div>

    <flux:modal wire:model="isModalOpen" class="w-full max-w-lg">
        <div class="p-6">
            <h2 class="text-xl font-bold mb-4">{{ $publisherId ? 'Edit Penerbit' : 'Tambah Penerbit' }}</h2>
            <form wire:submit="save" class="space-y-6 mt-4">
                <flux:input wire:model="name" label="Nama Penerbit" required />
                <flux:textarea wire:model="address" label="Alamat / Kontak" rows="4" />
                
                <div class="flex justify-end gap-3 mt-8">
                    <flux:button variant="ghost" wire:click="$set('isModalOpen', false)">Batal</flux:button>
                    <flux:button variant="primary" type="submit">Simpan</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <x-confirm-dialog 
        title="Hapus Penerbit"
        message="Anda yakin ingin menghapus data penerbit ini secara permanen? Tindakan ini tidak dapat dibatalkan."
        wireAction="delete(deleteId)"
        variant="danger"
        confirmText="Ya, Hapus"
    />
</div>
