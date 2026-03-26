<?php

use App\Models\Author;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] #[Title('Data Penulis')] class extends Component {
    use WithPagination;

    public $search = '';
    public $name = '';
    public $biography = '';
    public $authorId = null;

    public $isModalOpen = false;

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'biography' => 'nullable|string',
        ];
    }

    public function create()
    {
        $this->reset(['name', 'biography', 'authorId']);
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        $author = Author::findOrFail($id);
        $this->authorId = $author->id;
        $this->name = $author->name;
        $this->biography = $author->biography;
        
        $this->isModalOpen = true;
    }

    public function save()
    {
        $this->validate();

        Author::updateOrCreate(
            ['id' => $this->authorId],
            [
                'name' => $this->name,
                'biography' => $this->biography,
            ]
        );

        $this->isModalOpen = false;
        $this->reset(['name', 'biography', 'authorId']);
        $this->dispatch('showToast', 'success', 'Berhasil', 'Data penulis berhasil disimpan.');
    }

    #[Livewire\Attributes\On('deleteAuthor')]
    public function delete($id)
    {
        Author::find($id)->delete();
        $this->dispatch('showToast', 'success', 'Berhasil', 'Data penulis berhasil dihapus.');
    }

    public function with()
    {
        return [
            'authors' => Author::withCount('books')
                        ->where('name', 'like', '%'.$this->search.'%')
                        ->latest()
                        ->paginate(10),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl" x-data="{ deleteId: null, showConfirm: false }">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold">Data Penulis</h1>
            <flux:button variant="primary" wire:click="create">Tambah Penulis</flux:button>
        </div>

        <div class="flex mb-4">
            <flux:input wire:model.live.debounce.300ms="search" autocomplete="off" placeholder="Cari Penulis..." icon="magnifying-glass" class="w-full md:w-1/3" />
        </div>

        <div class="bg-white dark:bg-zinc-900 shadow rounded-lg overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-6 py-3">Nama Penulis</th>
                        <th class="px-6 py-3">Biografi (Singkat)</th>
                        <th class="px-6 py-3 text-center">Jumlah Buku</th>
                        <th class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($authors as $author)
                        <tr class="border-b dark:border-zinc-700 hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors">
                            <td class="px-6 py-4 font-medium">{{ $author->name }}</td>
                            <td class="px-6 py-4 text-xs text-zinc-500">{{ Str::limit($author->biography ?? '-', 100) }}</td>
                            <td class="px-6 py-4 text-center text-zinc-600 dark:text-zinc-400">{{ $author->books_count }} buku</td>
                            <td class="px-6 py-4 text-right mb-2">
                                <flux:button size="sm" variant="outline" wire:click="edit({{ $author->id }})">Edit</flux:button>
                                <flux:button size="sm" variant="danger" @click="deleteId = {{ $author->id }}; showConfirm = true">Hapus</flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-zinc-500">Pencarian tidak menemukan data penulis.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">
            {{ $authors->links() }}
        </div>


    <flux:modal wire:model="isModalOpen" class="w-full max-w-lg">
        <div class="p-6">
            <h2 class="text-xl font-bold mb-4">{{ $authorId ? 'Edit Penulis' : 'Tambah Penulis' }}</h2>
            <form wire:submit="save" class="space-y-6 mt-4">
                <flux:input wire:model="name" label="Nama Penulis" required />
                <flux:textarea wire:model="biography" label="Biografi / Keterangan" rows="4" />
                
                <div class="flex justify-end gap-3 mt-8">
                    <flux:button variant="ghost" wire:click="$set('isModalOpen', false)">Batal</flux:button>
                    <flux:button variant="primary" type="submit">Simpan</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <x-confirm-dialog 
        title="Hapus Penulis"
        message="Anda yakin ingin menghapus data penulis ini secara permanen? Tindakan ini tidak dapat dibatalkan."
        wireAction="delete(deleteId)"
        variant="danger"
        confirmText="Ya, Hapus"
    />
</div>
