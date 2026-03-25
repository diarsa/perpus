<?php

use App\Models\Classification;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] #[Title('Data Klasifikasi')] class extends Component {
    use WithPagination;

    public $search = '';
    public $name = '';
    public $code = '';
    public $classificationId = null;

    public $isModalOpen = false;

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:20',
        ];
    }

    public function create()
    {
        $this->reset(['name', 'code', 'classificationId']);
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        $classification = Classification::findOrFail($id);
        $this->classificationId = $classification->id;
        $this->name = $classification->name;
        $this->code = $classification->code;
        
        $this->isModalOpen = true;
    }

    public function save()
    {
        $this->validate();

        Classification::updateOrCreate(
            ['id' => $this->classificationId],
            [
                'name' => $this->name,
                'code' => $this->code,
            ]
        );

        $this->isModalOpen = false;
        $this->reset(['name', 'code', 'classificationId']);

        $message = $this->classificationId ? 'Data klasifikasi berhasil diperbarui.' : 'Data klasifikasi berhasil ditambahkan.';
        $this->dispatch('showToast', 'success', 'Berhasil', $message);
    }

    #[Livewire\Attributes\On('deleteClassification')]
    public function delete($id)
    {
        Classification::find($id)->delete();
        $this->dispatch('showToast', 'success', 'Berhasil', 'Data klasifikasi berhasil dihapus.');
    }

    public function with()
    {
        return [
            'classifications' => Classification::withCount('books')
                        ->where(function($query) {
                            $query->where('name', 'like', '%'.$this->search.'%')
                                  ->orWhere('code', 'like', '%'.$this->search.'%');
                        })
                        ->latest()
                        ->paginate(10),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Data Klasifikasi</h1>
        <flux:button variant="primary" wire:click="create">Tambah Klasifikasi</flux:button>
    </div>

    <div class="flex mb-4">
        <flux:input wire:model.live.debounce.300ms="search" autocomplete="off" placeholder="Cari Kode atau Nama Klasifikasi..." icon="magnifying-glass" class="w-full md:w-1/3" />
    </div>

    <div class="bg-white dark:bg-zinc-900 shadow rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-800">
        <table class="w-full text-sm text-left">
            <thead class="text-xs uppercase bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-800">
                <tr>
                    <th class="px-6 py-3 w-32">Kode</th>
                    <th class="px-6 py-3">Nama Klasifikasi</th>
                    <th class="px-6 py-3 text-center w-32">Jumlah Buku</th>
                    <th class="px-6 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse($classifications as $classification)
                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors">
                        <td class="px-6 py-4 font-mono text-xs text-zinc-900 dark:text-white fw-bold">{{ $classification->code ?? '-' }}</td>
                        <td class="px-6 py-4 font-medium text-zinc-900 dark:text-white">{{ $classification->name }}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200">
                                {{ $classification->books_count }} buku
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <flux:button size="sm" variant="outline" wire:click="edit({{ $classification->id }})">Edit</flux:button>
                            <flux:button size="sm" variant="danger" wire:click="$dispatch('showConfirmModal', { title: 'Hapus Klasifikasi', message: 'Anda yakin ingin menghapus klasifikasi ini? Buku yang terhubung akan kehilangan tautan klasifikasinya.', actionEvent: 'deleteClassification', actionParams: [{{ $classification->id }}] })">Hapus</flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-zinc-500 h-32 italic">Data klasifikasi tidak ditemukan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t border-zinc-200 dark:border-zinc-800">
            {{ $classifications->links() }}
        </div>
    </div>

    <flux:modal wire:model="isModalOpen" class="w-full max-w-lg">
        <div class="p-6">
            <h2 class="text-xl font-bold mb-4">{{ $classificationId ? 'Edit Klasifikasi' : 'Tambah Klasifikasi' }}</h2>
            <form wire:submit="save" class="space-y-6 mt-4">
                <flux:input wire:model="code" label="Kode (DDC / Lokal)" placeholder="Contoh: 800" />
                <flux:input wire:model="name" label="Nama Klasifikasi" required placeholder="Contoh: Kesusastraan" />
                
                <div class="flex justify-end gap-3 mt-8">
                    <flux:button variant="ghost" wire:click="$set('isModalOpen', false)">Batal</flux:button>
                    <flux:button variant="primary" type="submit">Simpan</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
