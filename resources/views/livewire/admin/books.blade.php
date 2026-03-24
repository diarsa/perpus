<?php

use App\Models\Book;
use App\Models\Author;
use App\Models\Publisher;
use App\Models\Classification;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Storage;

new #[Layout('components.layouts.app')] #[Title('Data Buku')] class extends Component {
    use WithPagination, WithFileUploads;

    public $search = '';
    public $title = '';
    public $author_id = '';
    public $publisher_id = '';
    public $classification_id = '';
    public $published_year = '';
    public $isbn = '';
    public $description = '';
    public $stock = 1;
    public $cover_image_file = null;
    public $bookId = null;
    public $isModalOpen = false;

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'author_id' => 'required|exists:authors,id',
            'publisher_id' => 'nullable|exists:publishers,id',
            'classification_id' => 'nullable|exists:classifications,id',
            'published_year' => 'nullable|integer',
            'isbn' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'stock' => 'required|integer|min:1',
            'cover_image_file' => 'nullable|image|max:2048',
        ];
    }

    public function create()
    {
        $this->resetFields();
        $this->isModalOpen = true;
        $this->dispatch('init-select2');
    }

    public function edit($id)
    {
        $book = Book::findOrFail($id);
        $this->bookId = $book->id;
        $this->title = $book->title;
        $this->author_id = $book->author_id;
        $this->publisher_id = $book->publisher_id;
        $this->classification_id = $book->classification_id;
        $this->published_year = $book->published_year;
        $this->isbn = $book->isbn;
        $this->description = $book->description;
        $this->stock = $book->stock;
        
        $this->isModalOpen = true;
        
        $this->dispatch('init-select2', [
            'author_id' => $this->author_id,
            'publisher_id' => $this->publisher_id,
            'classification_id' => $this->classification_id
        ]);
    }

    #[Livewire\Attributes\On('triggerAuthorUpdate')]
    public function triggerAuthorUpdate($val)
    {
        if (is_numeric($val)) {
            $this->author_id = $val;
        } elseif (!empty($val)) {
            $author = Author::firstOrCreate(['name' => $val]);
            $this->author_id = $author->id;
        } else {
            $this->author_id = null;
        }
    }

    #[Livewire\Attributes\On('triggerPublisherUpdate')]
    public function triggerPublisherUpdate($val)
    {
        if (is_numeric($val)) {
            $this->publisher_id = $val;
        } elseif (!empty($val)) {
            $publisher = Publisher::firstOrCreate(['name' => $val]);
            $this->publisher_id = $publisher->id;
        } else {
            $this->publisher_id = null;
        }
    }

    #[Livewire\Attributes\On('triggerClassificationUpdate')]
    public function triggerClassificationUpdate($val)
    {
        if (is_numeric($val)) {
            $this->classification_id = $val;
        } elseif (!empty($val)) {
            $classification = Classification::firstOrCreate(['name' => $val]);
            $this->classification_id = $classification->id;
        } else {
            $this->classification_id = null;
        }
    }

    public function save()
    {
        $this->validate();

        $coverImagePath = null;
        if ($this->cover_image_file) {
            // Compress and resize with Intervention Image
            $img = Image::read($this->cover_image_file->getRealPath());
            $img->scale(width: 500); // Standardized width
            
            $filename = $this->cover_image_file->hashName();
            $coverImagePath = 'book-covers/' . $filename;
            
            // Store to public disk
            Storage::disk('public')->put($coverImagePath, (string) $img->toJpeg(80));
        }

        if ($this->bookId) {
            $book = Book::find($this->bookId);
            $stockDiff = $this->stock - $book->stock;
            
            $data = [
                'title' => $this->title,
                'author_id' => empty($this->author_id) ? null : $this->author_id,
                'publisher_id' => empty($this->publisher_id) ? null : $this->publisher_id,
                'classification_id' => empty($this->classification_id) ? null : $this->classification_id,
                'published_year' => empty($this->published_year) ? null : $this->published_year,
                'isbn' => $this->isbn,
                'description' => $this->description,
                'stock' => $this->stock,
                'available_stock' => $book->available_stock + $stockDiff,
            ];

            if ($coverImagePath) {
                $data['cover_image'] = $coverImagePath;
            }

            $book->update($data);
        } else {
            Book::create([
                'title' => $this->title,
                'author_id' => empty($this->author_id) ? null : $this->author_id,
                'publisher_id' => empty($this->publisher_id) ? null : $this->publisher_id,
                'classification_id' => empty($this->classification_id) ? null : $this->classification_id,
                'published_year' => empty($this->published_year) ? null : $this->published_year,
                'isbn' => $this->isbn,
                'description' => $this->description,
                'cover_image' => $coverImagePath,
                'stock' => $this->stock,
                'available_stock' => $this->stock,
            ]);
        }

        $this->isModalOpen = false;
        $this->resetFields();
        $this->dispatch('showToast', 'Berhasil', 'Data buku berhasil disimpan.');
    }

    #[Livewire\Attributes\On('deleteBook')]
    public function delete($id)
    {
        Book::find($id)->delete();
        $this->dispatch('showToast', 'Berhasil', 'Data buku berhasil dihapus.');
    }

    public function resetFields()
    {
        $this->title = '';
        $this->author_id = '';
        $this->publisher_id = '';
        $this->classification_id = '';
        $this->published_year = '';
        $this->isbn = '';
        $this->description = '';
        $this->stock = 1;
        $this->cover_image_file = null;
        $this->bookId = null;
    }

    public function with()
    {
        return [
            'books' => Book::with(['author', 'publisher', 'classification'])
                        ->where('title', 'like', '%'.$this->search.'%')
                        ->orWhereHas('author', function($q) {
                            $q->where('name', 'like', '%'.$this->search.'%');
                        })
                        ->latest()
                        ->paginate(10),
            'authors' => Author::all(),
            'publishers' => Publisher::all(),
            'classifications' => Classification::all(),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold">Data Buku</h1>
            <flux:button variant="primary" wire:click="create">Tambah Buku</flux:button>
        </div>

        <div class="flex mb-4">
            <flux:input wire:model.live.debounce.300ms="search" autocomplete="off" placeholder="Cari buku..." icon="magnifying-glass" class="w-full md:w-1/3" />
        </div>

        <div class="bg-white dark:bg-zinc-900 shadow rounded-lg overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-6 py-3">Sampul</th>
                        <th class="px-6 py-3">Judul Buku</th>
                        <th class="px-6 py-3">Penulis / Penerbit</th>
                        <th class="px-6 py-3">Klasifikasi</th>
                        <th class="px-6 py-3 text-center">Tahun</th>
                        <th class="px-6 py-3 text-center">Stok</th>
                        <th class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($books as $book)
                        <tr class="border-b dark:border-zinc-700 hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors">
                            <td class="px-6 py-4">
                                @if($book->cover_image)
                                    <img src="{{ Storage::url($book->cover_image) }}" class="h-10 w-8 object-cover rounded shadow-sm">
                                @else
                                    <div class="h-10 w-8 bg-zinc-100 dark:bg-zinc-800 rounded flex items-center justify-center text-[8px] text-zinc-400">NO IMG</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-medium">{{ $book->title }}<br><span class="text-[10px] text-zinc-500">ISBN: {{ $book->isbn }}</span></td>
                            <td class="px-6 py-4 text-xs">{{ $book->author->name ?? '-' }}<br><span class="text-zinc-500 opacity-75">{{ $book->publisher->name ?? '-' }}</span></td>
                            <td class="px-6 py-4">
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400">
                                    {{ $book->classification->name ?? '-' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center text-xs">{{ $book->published_year }}</td>
                            <td class="px-6 py-4 text-center text-xs">{{ $book->stock }} ({{ $book->available_stock }})</td>
                            <td class="px-6 py-4 text-right">
                                <flux:button size="sm" variant="outline" wire:click="edit({{ $book->id }})">Edit</flux:button>
                                <flux:button size="sm" variant="danger" wire:click="$dispatch('showConfirmModal', { title: 'Hapus Buku', message: 'Anda yakin ingin menghapus buku ini secara permanen?', actionEvent: 'deleteBook', actionParams: [{{ $book->id }}] })">Hapus</flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-zinc-500">Pencarian tidak menemukan buku.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4">
                {{ $books->links() }}
            </div>
        </div>

        <!-- Create/Edit Modal using Flux Modal -->
        <flux:modal name="book-modal" wire:model="isModalOpen" class="w-full max-w-2xl">
            <div id="bookModalBody" class="p-6">
                <h2 class="text-xl font-bold mb-4">{{ $bookId ? 'Edit Buku' : 'Tambah Buku' }}</h2>
                <form wire:submit="save" class="space-y-6 mt-4">
                    <div class="grid grid-cols-4 gap-6">
                        <div class="col-span-1">
                            <label class="block text-sm font-medium text-zinc-800 dark:text-zinc-200 mb-2">Sampul Buku</label>
                            <div class="relative group cursor-pointer border-2 border-dashed border-zinc-300 dark:border-zinc-700 rounded-lg p-2 h-48 flex items-center justify-center overflow-hidden hover:border-zinc-400 transition-colors">
                                <input type="file" wire:model="cover_image_file" class="absolute inset-0 opacity-0 cursor-pointer z-10">
                                @if($cover_image_file)
                                    <img src="{{ $cover_image_file->temporaryUrl() }}" class="w-full h-full object-cover rounded">
                                @elseif($bookId && ($existingBook = \App\Models\Book::find($bookId)) && $existingBook->cover_image)
                                    <img src="{{ Storage::url($existingBook->cover_image) }}" class="w-full h-full object-cover rounded">
                                @else
                                    <div class="text-center">
                                        <flux:icon icon="photo" class="mx-auto size-8 text-zinc-400 mb-2" />
                                        <p class="text-[10px] text-zinc-500">Klik untuk upload (Max 2MB)</p>
                                    </div>
                                @endif
                                <div wire:loading wire:target="cover_image_file" class="absolute inset-0 bg-white/50 dark:bg-black/50 flex items-center justify-center z-20">
                                    <flux:icon icon="arrow-path" class="size-6 animate-spin text-zinc-500" />
                                </div>
                            </div>
                        </div>
                        <div class="col-span-3 space-y-6">
                            <flux:input wire:model="title" label="Judul Buku" required />
                            
                            <!-- Penulis -->
                            <div style="position: relative; z-index: 999;">
                                <div wire:ignore x-data="{
                                        init() {
                                            $(this.$refs.select).select2({
                                                tags: true,
                                                placeholder: 'Pilih Penulis...',
                                                dropdownParent: $(this.$refs.select).parent(),
                                                width: '100%'
                                            }).on('change', (e) => {
                                                $wire.dispatch('triggerAuthorUpdate', { val: $(this.$refs.select).val() });
                                            });

                                            Livewire.on('init-select2', (params) => {
                                                if (params && params.length > 0 && params[0].author_id) {
                                                    $(this.$refs.select).val(params[0].author_id).trigger('change.select2');
                                                } else {
                                                    $(this.$refs.select).val('').trigger('change.select2');
                                                }
                                            });
                                        }
                                    }" 
                                    class="flex flex-col gap-1 relative">
                                    <label class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Penulis <span class="text-red-500">*</span></label>
                                    <select x-ref="select" id="authorSelect" class="w-full" required>
                                        <option value="">-- Pilih Penulis --</option>
                                        @foreach($authors as $author)
                                            <option value="{{ $author->id }}">{{ $author->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Penerbit -->
                            <div style="position: relative; z-index: 998;">
                                <div wire:ignore x-data="{
                                        init() {
                                            $(this.$refs.select).select2({
                                                tags: true,
                                                placeholder: 'Pilih Penerbit...',
                                                dropdownParent: $(this.$refs.select).parent(),
                                                width: '100%'
                                            }).on('change', (e) => {
                                                $wire.dispatch('triggerPublisherUpdate', { val: $(this.$refs.select).val() });
                                            });

                                            Livewire.on('init-select2', (params) => {
                                                if (params && params.length > 0 && params[0].publisher_id) {
                                                    $(this.$refs.select).val(params[0].publisher_id).trigger('change.select2');
                                                } else {
                                                    $(this.$refs.select).val('').trigger('change.select2');
                                                }
                                            });
                                        }
                                    }" 
                                    class="flex flex-col gap-1 relative">
                                    <label class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Penerbit</label>
                                    <select x-ref="select" id="publisherSelect" class="w-full">
                                        <option value="">-- Pilih Penerbit --</option>
                                        @foreach($publishers as $publisher)
                                            <option value="{{ $publisher->id }}">{{ $publisher->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Klasifikasi & (Tahun/Stok) -->
                    <div class="grid grid-cols-2 gap-6" style="position: relative; z-index: 997;">
                        <div wire:ignore x-data="{
                                init() {
                                    $(this.$refs.select).select2({
                                        tags: true,
                                        placeholder: 'Pilih Klasifikasi...',
                                        dropdownParent: $(this.$refs.select).parent(),
                                        width: '100%'
                                    }).on('change', (e) => {
                                        $wire.dispatch('triggerClassificationUpdate', { val: $(this.$refs.select).val() });
                                    });

                                    Livewire.on('init-select2', (params) => {
                                        if (params && params.length > 0 && params[0].classification_id) {
                                            $(this.$refs.select).val(params[0].classification_id).trigger('change.select2');
                                        } else {
                                            $(this.$refs.select).val('').trigger('change.select2');
                                        }
                                    });
                                }
                            }" 
                            class="flex flex-col gap-1 relative">
                            <label class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Klasifikasi</label>
                            <select x-ref="select" id="classificationSelect" class="w-full">
                                <option value="">-- Pilih Klasifikasi --</option>
                                @foreach($classifications as $classification)
                                    <option value="{{ $classification->id }}">{{ ($classification->code ? $classification->code . ' - ' : '') . $classification->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <flux:input wire:model="published_year" type="number" label="Tahun" />
                            <flux:input wire:model="stock" type="number" min="1" label="Stok" required />
                        </div>
                    </div>
                    
                    <div style="position: relative; z-index: 996;">
                        <flux:input wire:model="isbn" label="ISBN/ISSN" />
                    </div>
                    
                    <div style="position: relative; z-index: 995;" class="mt-6">
                        <flux:textarea wire:model="description" label="Deskripsi/Sinopsis" placeholder="Deskripsi buku..." rows="4" />
                    </div>
                    
                    <div class="flex justify-end gap-3 mt-8">
                        <flux:button variant="ghost" wire:click="$set('isModalOpen', false)">Batal</flux:button>
                        <flux:button variant="primary" type="submit">Simpan</flux:button>
                    </div>
                </form>
            </div>
        </flux:modal>
        <!-- Select2 Initialization -->
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        
        <style>
            .select2-container { z-index: 99999 !important; width: 100% !important; }
            .select2-dropdown { z-index: 100000 !important; }
        </style>
        
        <script>
            // Clean up scripts, no auto-close needed with correct layering
        </script>
    </div>
