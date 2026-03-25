<?php

use App\Models\Book;
use App\Models\Student;
use App\Models\Borrowing;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] #[Title('Data Peminjaman')] class extends Component {
    use WithPagination;

    public $search = '';
    public $student_id = '';
    public $book_id = '';
    public $borrow_date = '';
    public $return_date = '';
    public $borrowingId = null;
    public $rejectionReason = '';
    public $statusFilter = '';

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['search', 'statusFilter'])) {
            $this->resetPage();
        }

        try {
            $this->validateOnly($propertyName);
        } catch (\Exception $e) {
            // Silently ignore if property is not in rules
        }
    }

    public function clearFilters()
    {
        $this->reset(['search', 'statusFilter']);
        $this->resetPage();
    }

    public $isModalOpen = false;
    public $isReturnModalOpen = false;
    public $isRejectModalOpen = false;
    public $fineAmount = 0;

    public function rules()
    {
        return [
            'student_id' => 'required|exists:students,id',
            'book_id' => 'required|exists:books,id',
            'borrow_date' => 'required|date',
            'return_date' => 'required|date|after_or_equal:borrow_date',
        ];
    }

    public function create()
    {
        $this->reset(['student_id', 'book_id', 'borrow_date', 'return_date', 'borrowingId']);
        $this->resetValidation();
        $this->borrow_date = date('Y-m-d');
        $this->return_date = date('Y-m-d', strtotime('+7 days'));
        $this->isModalOpen = true;
        $this->dispatch('borrowing-modal-opened');
    }

    public function approve($id)
    {
        $borrowing = Borrowing::findOrFail($id);
        if ($borrowing->status != 'pending') return;

        // Check stock again
        if ($borrowing->book->available_stock < 1) {
            $this->dispatch('showToast', 'Gagal', 'Stok buku habis. Tidak bisa menyetujui.');
            return;
        }

        $borrowing->update(['status' => 'borrowed']);
        $borrowing->book->decrement('available_stock');

        $this->dispatch('showToast', 'Berhasil', 'Peminjaman disetujui.');
    }

    public function openRejectModal($id)
    {
        $this->borrowingId = $id;
        $this->rejectionReason = '';
        $this->isRejectModalOpen = true;
    }

    public function reject()
    {
        $this->validate(['rejectionReason' => 'required|string|max:255']);

        $borrowing = Borrowing::findOrFail($this->borrowingId);
        $borrowing->update([
            'status' => 'rejected',
            'rejection_reason' => $this->rejectionReason
        ]);

        $this->isRejectModalOpen = false;
        $this->dispatch('showToast', 'Berhasil', 'Peminjaman ditolak.');
    }

    #[Livewire\Attributes\On('setBorrowingStudent')]
    public function setBorrowingStudent($val)
    {
        $this->student_id = $val;
    }

    #[Livewire\Attributes\On('setBorrowingBook')]
    public function setBorrowingBook($val)
    {
        $this->book_id = $val;
    }

    public function save()
    {
        $this->validate();

        $book = Book::find($this->book_id);
        
        if ($book->available_stock < 1) {
            $this->addError('book_id', 'Stok buku ini sedang kosong.');
            return;
        }

        Borrowing::create([
            'student_id' => $this->student_id,
            'book_id' => $this->book_id,
            'borrow_date' => $this->borrow_date,
            'return_date' => $this->return_date,
            'status' => 'borrowed'
        ]);

        $book->decrement('available_stock');

        $this->isModalOpen = false;
        $this->reset(['student_id', 'book_id', 'borrow_date', 'return_date']);
        $this->dispatch('showToast', 'Berhasil', 'Data peminjaman berhasil disimpan.');
    }

    public function openReturnModal($id)
    {
        $this->borrowingId = $id;
        $borrowing = Borrowing::findOrFail($id);
        
        $diff = now()->diffInDays($borrowing->return_date, false);
        $this->fineAmount = $diff < 0 ? abs($diff) * 1000 : 0; // Rp 1.000 per day late
        
        $this->isReturnModalOpen = true;
    }

    public function processReturn()
    {
        $borrowing = Borrowing::findOrFail($this->borrowingId);
        
        if (!in_array($borrowing->status, ['borrowed', 'returning'])) return;

        $borrowing->update([
            'status' => 'returned',
            'actual_return_date' => now(),
            'fine' => $this->fineAmount
        ]);

        $borrowing->book->increment('available_stock');
        
        $this->isReturnModalOpen = false;
        $this->reset(['borrowingId', 'fineAmount']);
        $this->dispatch('showToast', 'Berhasil', 'Pengembalian buku berhasil diproses.');
    }

    #[Livewire\Attributes\On('deleteBorrowing')]
    public function delete($id)
    {
        $b = Borrowing::find($id);
        if ($b->status == 'borrowed') {
            $b->book->increment('available_stock');
        }
        $b->delete();
        $this->dispatch('showToast', 'Berhasil', 'Riwayat peminjaman berhasil dihapus.');
    }

    public function with()
    {
        return [
            'borrowings' => Borrowing::with(['student', 'book'])
                        ->where(function($q) {
                            $q->whereHas('student', function($sq) {
                                $sq->where('name', 'like', '%'.$this->search.'%')
                                   ->orWhere('nis', 'like', '%'.$this->search.'%');
                            })
                            ->orWhereHas('book', function($bq) {
                                $bq->where('title', 'like', '%'.$this->search.'%');
                            });
                        })
                        ->latest()
                        ->when($this->statusFilter, function($q) {
                            $q->where('status', $this->statusFilter);
                        })
                        ->paginate(10),
            'students' => Student::orderBy('name')->get(),
            'available_books' => Book::where('available_stock', '>', 0)->orderBy('title')->get(),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold">Data Peminjaman</h1>
            <flux:button variant="primary" wire:click="create">Peminjaman Baru</flux:button>
        </div>

        <div class="flex gap-4 mb-4">
            <flux:input wire:model.live.debounce.300ms="search" autocomplete="off" placeholder="Cari nama siswa atau judul buku..." icon="magnifying-glass" class="w-full md:w-1/3" />
            
            <flux:select wire:model.live="statusFilter" class="w-full md:w-1/3" placeholder="Semua Status">
                <flux:select.option value="">Semua Status</flux:select.option>
                <flux:select.option value="pending">Menunggu Verifikasi</flux:select.option>
                <flux:select.option value="borrowed">Sedang Dipinjam</flux:select.option>
                <flux:select.option value="returning">Proses Pengembalian</flux:select.option>
                <flux:select.option value="returned">Sudah Dikembalikan</flux:select.option>
                <flux:select.option value="rejected">Ditolak / Dibatalkan</flux:select.option>
            </flux:select>

            @if($search || $statusFilter)
                <flux:button variant="ghost" size="sm" wire:click="clearFilters" icon="x-mark">
                    Hapus Filter
                </flux:button>
            @endif
        </div>

        <div class="bg-white dark:bg-zinc-900 shadow rounded-lg overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-6 py-3">Siswa</th>
                        <th class="px-6 py-3">Buku</th>
                        <th class="px-6 py-3 text-center">Tanggal Pinjam</th>
                        <th class="px-6 py-3 text-center">Batas Kembali / Alasan</th>
                        <th class="px-6 py-3 text-center">Status</th>
                        <th class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($borrowings as $borrow)
                        <tr class="border-b dark:border-zinc-700 hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors">
                            <td class="px-6 py-4">
                                <span class="font-medium text-zinc-900 dark:text-white">{{ $borrow->student->name }}</span> <br>
                                <span class="text-xs text-zinc-500">NIS: {{ $borrow->student->nis }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-zinc-800 dark:text-zinc-200 line-clamp-1">{{ $borrow->book->title }}</span>
                            </td>
                            <td class="px-6 py-4 text-center text-xs text-zinc-600 dark:text-zinc-400 font-bold uppercase">{{ $borrow->borrow_date->format('d M Y') }}</td>
                            <td class="px-6 py-4 text-center text-xs text-zinc-600 dark:text-zinc-400">
                                @if($borrow->status == 'rejected')
                                    <span class="text-red-500 italic">{{ Str::limit($borrow->rejection_reason, 30) }}</span>
                                @else
                                    {{ $borrow->return_date->format('d M Y') }}
                                    @if($borrow->status == 'borrowed' && now()->startOfDay()->greaterThan($borrow->return_date))
                                        <br><span class="text-[10px] text-red-500 font-black uppercase">Terlambat!</span>
                                    @endif
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    $statusClasses = [
                                        'pending' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                        'borrowed' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
                                        'returning' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400 animate-pulse',
                                        'returned' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
                                        'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                    ];
                                    $statusLabels = [
                                        'pending' => 'Menunggu',
                                        'borrowed' => 'Dipinjam',
                                        'returning' => 'Konfirmasi Balik',
                                        'returned' => 'Kembali',
                                        'rejected' => 'Ditolak',
                                    ];
                                @endphp
                                <span class="px-2.5 py-0.5 text-[9px] font-black uppercase rounded-lg {{ $statusClasses[$borrow->status] ?? 'bg-zinc-100' }}">
                                    {{ $statusLabels[$borrow->status] ?? $borrow->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="flex justify-end gap-2">
                                    @if($borrow->status == 'pending')
                                        <flux:button size="sm" variant="primary" wire:click="approve({{ $borrow->id }})">Setujui</flux:button>
                                        <flux:button size="sm" variant="danger" wire:click="openRejectModal({{ $borrow->id }})">Tolak</flux:button>
                                    @elseif($borrow->status == 'borrowed' || $borrow->status == 'returning')
                                        <flux:button size="sm" variant="{{ $borrow->status == 'returning' ? 'primary' : 'outline' }}" wire:click="openReturnModal({{ $borrow->id }})">
                                            {{ $borrow->status == 'returning' ? 'Konfirmasi Kembali' : 'Kembalikan' }}
                                        </flux:button>
                                    @endif
                                    
                                    <flux:button size="sm" icon="trash" variant="ghost" wire:click="$dispatch('showConfirmModal', { title: 'Hapus Riwayat', message: 'Hapus data ini?', actionEvent: 'deleteBorrowing', actionParams: [{{ $borrow->id }}] })"></flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-zinc-500">Tidak ada data peminjaman.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4 border-t border-zinc-100 dark:border-zinc-800">
                {{ $borrowings->links() }}
            </div>
        </div>

        <flux:modal wire:model="isModalOpen" class="w-full max-w-lg">
            <div class="p-6" id="borrowingModalBody">
                <h2 class="text-xl font-bold mb-6 uppercase tracking-tight">Peminjaman Buku Baru</h2>
                <form wire:submit="save" class="space-y-6">
                    <flux:field>
                        <div x-data="{ 
                            studentId: @entangle('student_id'),
                            init() {
                                let check = setInterval(() => {
                                    if (window.$ && window.$.fn.select2) {
                                        clearInterval(check);
                                        $(this.$refs.studentSelect).select2({ width: '100%', dropdownParent: $('#borrowingModalBody') })
                                            .on('change', (e, data) => { 
                                                if (data && data.ignore) return;
                                                this.studentId = e.target.value; 
                                                @this.setBorrowingStudent(e.target.value); 
                                            });
                                        $wire.on('borrowing-modal-opened', () => {
                                            $(this.$refs.studentSelect).val(null).trigger('change', { ignore: true });
                                        });
                                    }
                                }, 50);
                            }
                        }" wire:ignore>
                            <flux:label>Siswa Peminjam <span class="text-red-500 ml-1">*</span></flux:label>
                            <select x-ref="studentSelect" class="w-full rounded-md border-zinc-200">
                                <option value="">Pilih Siswa...</option>
                                @foreach($students as $s)
                                    <option value="{{ $s->id }}">{{ $s->nis }} - {{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <flux:error name="student_id" />
                    </flux:field>

                    <flux:field>
                        <div x-data="{ 
                            bookId: @entangle('book_id'),
                            init() {
                                let check = setInterval(() => {
                                    if (window.$ && window.$.fn.select2) {
                                        clearInterval(check);
                                        $(this.$refs.bookSelect).select2({ width: '100%', dropdownParent: $('#borrowingModalBody') })
                                            .on('change', (e, data) => { 
                                                if (data && data.ignore) return;
                                                this.bookId = e.target.value; 
                                                @this.setBorrowingBook(e.target.value); 
                                            });
                                        $wire.on('borrowing-modal-opened', () => {
                                            $(this.$refs.bookSelect).val(null).trigger('change', { ignore: true });
                                        });
                                    }
                                }, 50);
                            }
                        }" wire:ignore>
                            <flux:label>Buku yang Dipinjam <span class="text-red-500 ml-1">*</span></flux:label>
                            <select x-ref="bookSelect" class="w-full rounded-md border-zinc-200">
                                <option value="">Pilih Buku...</option>
                                @foreach($available_books as $b)
                                    <option value="{{ $b->id }}">{{ $b->title }} (Stok: {{ $b->available_stock }})</option>
                                @endforeach
                            </select>
                        </div>
                        <flux:error name="book_id" />
                    </flux:field>

                    <div class="grid grid-cols-2 gap-6">
                        <flux:field>
                            <flux:label>Tanggal Pinjam <span class="text-red-500 ml-1">*</span></flux:label>
                            <flux:input type="date" wire:model.live="borrow_date" required />
                            <flux:error name="borrow_date" />
                        </flux:field>
                        
                        <flux:field>
                            <flux:label>Batas Kembali <span class="text-red-500 ml-1">*</span></flux:label>
                            <flux:input type="date" wire:model.live="return_date" required />
                            <flux:error name="return_date" />
                        </flux:field>
                    </div>
                    
                    <div class="flex justify-end gap-3 mt-8">
                        <flux:button variant="ghost" x-on:click="$wire.isModalOpen = false">Batal</flux:button>
                        <flux:button variant="primary" type="submit">Simpan Peminjaman</flux:button>
                    </div>
                </form>
            </div>
        </flux:modal>

        <flux:modal wire:model="isReturnModalOpen" class="w-full max-w-sm">
            <div class="p-6">
                <h2 class="text-xl font-black uppercase mb-2">Konfirmasi Kembali</h2>
                <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-xl mb-6">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Denda:</p>
                    <p class="text-2xl font-black text-red-600">Rp {{ number_format($fineAmount) }}</p>
                    @if($fineAmount > 0)
                        <p class="text-[10px] text-zinc-400 italic font-medium mt-1">Denda keterlambatan Rp 1.000/hari</p>
                    @endif
                </div>
                <div class="flex justify-end gap-3">
                    <flux:button variant="ghost" wire:click="$set('isReturnModalOpen', false)">Batal</flux:button>
                    <flux:button variant="primary" wire:click="processReturn">Simpan & Selesai</flux:button>
                </div>
            </div>
        </flux:modal>

        <flux:modal wire:model="isRejectModalOpen" class="w-full max-w-md">
            <div class="p-6 text-center">
                <div class="size-16 bg-red-50 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="size-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                </div>
                <h3 class="text-xl font-black uppercase mb-4">Tolak Peminjaman</h3>
                <flux:textarea wire:model="rejectionReason" label="Alasan Penolakan" placeholder="Contoh: Belum mengembalikan buku sebelumnya, atau data NIS tidak valid." rows="3" required />
                <div class="flex justify-end gap-3 mt-8">
                    <flux:button variant="ghost" wire:click="$set('isRejectModalOpen', false)">Batal</flux:button>
                    <flux:button variant="danger" wire:click="reject">Tolak Sekarang</flux:button>
                </div>
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
</div>
