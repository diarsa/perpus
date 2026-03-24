<?php

use App\Models\Book;
use App\Models\Student;
use App\Models\Borrowing;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Hash;

new #[Layout('components.layouts.guest')] class extends Component {
    use WithPagination;

    public $search = '';
    public $sortBy = 'latest'; // Sorting criteria
    public $activeTab = 'home'; // home, login, register, account
    public $sessionNis = null;

    // Login & Reg Forms
    public $loginNis = '', $loginPassword = '';
    public $regNis = '', $regPassword = '', $regName = '', $regClass = '', $regGender = 'L', $regPhone = '', $regAddress = '';

    public $statusMessage = '';
    public $statusType = 'success';

    // Borrow Modal State
    public $showBorrowModal = false;
    public $selectedBookItem = null;
    public $returnDate = '';

    public function mount()
    {
        $this->sessionNis = session()->get('student_nis');
        $this->returnDate = now()->addDays(7)->format('Y-m-d');
    }

    public function with()
    {
        $activeStudent = null;
        $myBorrowings = [];
        if ($this->sessionNis) {
            $activeStudent = Student::where('nis', $this->sessionNis)->first();
            if ($activeStudent) {
                $myBorrowings = Borrowing::with('book')
                    ->where('student_id', $activeStudent->id)
                    ->latest()
                    ->get();
            }
        }

        $query = Book::with(['author', 'publisher'])
                ->where('title', 'like', '%'.$this->search.'%');

        if ($this->sortBy == 'year') {
            $query->orderBy('published_year', 'desc');
        } elseif ($this->sortBy == 'title') {
            $query->orderBy('title', 'asc');
        } elseif ($this->sortBy == 'available') {
            $query->where('available_stock', '>', 0)->latest();
        } else {
            $query->latest();
        }

        return [
            'books' => $query->paginate(12),
            'activeStudent' => $activeStudent,
            'myBorrowings' => $myBorrowings,
        ];
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->statusMessage = '';
    }

    public function login()
    {
        $student = Student::where('nis', $this->loginNis)->first();
        if ($student && Hash::check($this->loginPassword, $student->password)) {
            $this->sessionNis = $student->nis;
            session()->put('student_nis', $student->nis);
            $this->activeTab = 'home';
            $this->statusMessage = 'Halo ' . $student->name . ', selamat datang kembali!';
            $this->statusType = 'success';
        } else {
            $this->statusMessage = 'NIS atau Password salah.';
            $this->statusType = 'danger';
        }
    }

    public function register()
    {
        $this->validate([
            'regNis' => 'required|unique:students,nis',
            'regName' => 'required',
            'regPassword' => 'required|min:4',
            'regClass' => 'required',
        ]);

        $student = Student::create([
            'nis' => $this->regNis,
            'password' => Hash::make($this->regPassword),
            'name' => $this->regName,
            'class' => $this->regClass,
            'gender' => $this->regGender,
            'phone' => $this->regPhone,
            'address' => $this->regAddress,
        ]);

        $this->loginNis = $student->nis;
        $this->loginPassword = $this->regPassword;
        $this->login();
        $this->statusMessage = 'Pendaftaran berhasil!';
    }

    public function openBorrowModal($id)
    {
        if (!$this->sessionNis) {
            $this->activeTab = 'login';
            return;
        }

        $this->selectedBookItem = Book::with('author')->find($id);
        $this->returnDate = now()->addDays(7)->format('Y-m-d');
        $this->showBorrowModal = true;
    }

    public function confirmBorrow()
    {
        if (!$this->sessionNis || !$this->selectedBookItem) return;

        $student = Student::where('nis', $this->sessionNis)->first();
        
        // Active limit
        $activeCount = Borrowing::where('student_id', $student->id)
            ->whereIn('status', ['pending', 'borrowed'])
            ->count();
        
        if ($activeCount >= 100) {
            $this->statusMessage = 'Kamu sudah punya 100 pinjaman aktif.';
            $this->statusType = 'danger';
            $this->showBorrowModal = false;
            return;
        }

        Borrowing::create([
            'student_id' => $student->id,
            'book_id' => $this->selectedBookItem->id,
            'borrow_date' => now(),
            'return_date' => $this->returnDate,
            'status' => 'pending'
        ]);

        $this->statusMessage = 'Berhasil diajukan! Cek status di Profil.';
        $this->statusType = 'success';
        $this->showBorrowModal = false;
        $this->activeTab = 'account';
    }

    public function logout()
    {
        session()->forget('student_nis');
        $this->sessionNis = null;
        $this->activeTab = 'home';
    }
    public function requestReturn($id)
    {
        Borrowing::find($id)?->update(['status' => 'returning']);
        $this->statusMessage = 'Pengembalian diajukan ke admin.';
    }
}; ?>

<div>
    <style>
        .page-loading {
            opacity: 0.5;
            pointer-events: none;
            filter: blur(2px);
        }
        .animate-fade {
            animation: fadeIn 0.4s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
    <!-- Shopee-style Toast Notification -->
    <div 
        x-data="{ 
            show: false, 
            message: '', 
            type: 'success',
            init() {
                $watch('$wire.statusMessage', value => {
                    if (value) {
                        this.message = value;
                        this.type = $wire.statusType;
                        this.show = true;
                        setTimeout(() => {
                            this.show = false;
                            $wire.set('statusMessage', '');
                        }, 4000);
                    }
                })
            }
        }"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform translate-x-12"
        x-transition:enter-end="opacity-100 transform translate-x-0"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100 transform translate-x-0"
        x-transition:leave-end="opacity-0 transform translate-x-12"
        class="position-fixed top-0 end-0 p-4 z-3"
        style="margin-top: 80px; pointer-events: none;"
        x-cloak
    >
        <div class="toast show border-0 shadow-lg align-items-center text-white" :class="type == 'success' ? 'bg-success' : 'bg-danger'" role="alert" aria-live="assertive" aria-atomic="true" style="min-width: 300px; pointer-events: auto;">
            <div class="d-flex">
                <div class="toast-body p-3 fw-bold small uppercase">
                    <i class="fas d-inline-block me-2" :class="type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'"></i>
                    <span x-text="message"></span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" @click="show = false"></button>
            </div>
        </div>
    </div>

    <!-- Topbar -->
    <div class="shopee-orange d-none d-md-block">
        <div class="container nav-top d-flex justify-content-between fs-6">
            <div class="d-flex">
                <a href="#">Portal Siswa</a>
                <a href="#">Bantuan</a>
            </div>
            <div class="d-flex">
                @if($sessionNis)
                    <a href="#" wire:click="setTab('account')"><i class="fas fa-user-circle me-1"></i> {{ $activeStudent->name }}</a>
                    <a href="#" wire:click="logout">Keluar</a>
                @else
                    <a href="#" wire:click="setTab('register')">Daftar</a>
                    <a href="#" wire:click="setTab('login')">Login</a>
                @endif
            </div>
        </div>
    </div>

    <!-- Main Header -->
    <header class="navbar-main shadow-sm">
        <div class="container text-white">
            <div class="row align-items-center">
                <!-- Logo -->
                <div class="col-6 col-md-3 mb-3 mb-md-0 d-flex align-items-center" style="cursor: pointer;" wire:click="setTab('home')">
                    <i class="fas fa-shopping-bag fa-2xl me-2"></i>
                    <h2 class="m-0 fw-bold h4">RumahBaca<br><small class="fs-6 opacity-75">SFOURTEM</small></h2>
                </div>
                
                <!-- Search -->
                <div class="col-12 col-md-7 mb-3 mb-md-0">
                    <div class="search-bar">
                        <input type="text" wire:model.live.debounce.400ms="search" class="search-input" placeholder="Cari buku pelajaran, fiksi, atau lainnya...">
                        <button class="search-btn"><i class="fas fa-search"></i></button>
                    </div>
                </div>
                
                <!-- Account Button -->
                <div class="col-4 col-md-2 d-flex justify-content-end align-items-center">
                    <button class="btn btn-outline-light btn-sm position-relative d-flex align-items-center gap-1 fw-medium" wire:click="setTab('account')">
                        <i class="fas fa-user-circle"></i> <span>Akun Saya</span>
                        @if($sessionNis)
                            @php $b_count = $myBorrowings->whereIn('status', ['pending', 'borrowed', 'returning'])->count(); @endphp
                            @if($b_count > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger text-white">
                                    {{ $b_count }}
                                </span>
                            @endif
                        @endif
                    </button>
                </div>
            </div>
        </div>
    </header>

    <main class="container my-4" wire:loading.class="page-loading" style="transition: all 0.3s ease-in-out;">
        <div wire:key="tab-content-{{ $activeTab }}-{{ $search }}-{{ $sortBy }}-{{ request('page') }}" class="animate-fade">
        @if($statusMessage && false) 
            <!-- Inline alert hidden, replaced by toast -->
        @endif

        @if($activeTab == 'home')
            <!-- HOME TAB (Product Grid) -->
            <div class="row g-4">
                <!-- Sidebar / Banner area -->
                <div class="col-12">
                    <div class="promo-banner d-flex align-items-center justify-content-between overflow-hidden mb-0">
                        <div class="p-2">
                            <h3 class="fw-bold shopee-text-orange mb-2">Ajukan Peminjaman Buku Semudah Belanja Online!</h3>
                            <p class="text-muted">Proses pengajuan peminjaman kini lebih mudah dan cepat.</p>
                            <button class="btn shopee-orange px-4 py-2" wire:click="$set('search', '')">Pinjam Sekarang</button>
                        </div>
                        <i class="fas fa-book-reader fa-8x opacity-10 d-none d-md-block"></i>
                    </div>
                </div>

                <!-- Products -->
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-3 bg-white p-3 border-bottom shadow-sm">
                        <div class="fw-bold text-uppercase fs-6">SEMUA BUKU</div>
                        <div>
                            <select wire:model.live="sortBy" class="form-select form-select-sm border shadow-none" style="cursor: pointer; width: auto; font-size: 0.875rem;">
                                <option value="latest">Terbaru</option>
                                <option value="available">Buku Tersedia (Stok > 0)</option>
                                <option value="title">Judul Buku (A-Z)</option>
                                <option value="year">Tahun Terbit (Terbaru)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-6 g-2">
                        @forelse($books as $book)
                            <div class="col" wire:key="book-{{ $book->id }}">
                                <div class="product-card">
                                    @if($book->cover_image)
                                        <img src="{{ Storage::url($book->cover_image) }}" class="product-img" alt="{{ $book->title }}">
                                    @else
                                        <div class="product-img d-flex flex-column align-items-center justify-content-center p-3 text-center">
                                            <i class="fas fa-book fa-3x text-light mb-2"></i>
                                            <small class="text-muted opacity-50">{{ $book->title }}</small>
                                        </div>
                                    @endif
                                    
                                    <div class="product-info">
                                        <div class="product-title">{{ $book->title }}</div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="product-price">Tersedia {{ $book->available_stock }}</div>
                                        </div>
                                        <div class="mt-3">
                                            @if($book->available_stock > 0)
                                                <button wire:click="openBorrowModal({{ $book->id }})" class="btn-shopee shadow-sm">Ajukan Peminjaman</button>
                                            @else
                                                <button disabled class="btn btn-sm btn-secondary w-100 disabled text-white">Stok Habis</button>
                                            @endif
                                        </div>
                                        {{-- <div class="product-stock text-center mt-2 pt-2 border-top">
                                            <i class="fas fa-star text-warning" style="font-size: 10px;"></i>
                                            <i class="fas fa-star text-warning" style="font-size: 10px;"></i>
                                            <i class="fas fa-star text-warning" style="font-size: 10px;"></i>
                                            <i class="fas fa-star text-warning" style="font-size: 10px;"></i>
                                            <i class="fas fa-star text-warning" style="font-size: 10px;"></i>
                                            <span class="ms-1" style="font-size: 10px;">Terpinjam 50+</span>
                                        </div> --}}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 py-5 text-center bg-white border w-100">
                                <i class="fas fa-search fa-4x text-light mb-3 d-block"></i>
                                <p class="text-muted">Oops, buku yang anda cari tidak ditemukan.</p>
                            </div>
                        @endforelse
                    </div>

                    <div class="d-flex justify-content-center mt-5">
                        {{ $books->links() }}
                    </div>
                </div>
            </div>

        @elseif($activeTab == 'login')
            <!-- LOGIN TAB -->
            <div class="row justify-content-center py-5">
                <div class="col-md-5">
                    <div class="card border-0 shadow-sm p-4">
                        <div class="text-center mb-4">
                            <h4 class="fw-bold shopee-text-orange mb-1">Login Siswa</h4>
                            <p class="small text-muted">Akses Katalog & Peminjaman RumahBaca</p>
                        </div>
                        <form wire:submit.prevent="login">
                            <div class="mb-3">
                                <label class="small fw-bold">Masukkan Nomor Induk Siswa (NIS)</label>
                                <input type="text" wire:model="loginNis" class="form-control rounded-0 p-3 mt-1 @error('loginNis') is-invalid @enderror" required placeholder="Contoh: 12345">
                                @error('loginNis') <div class="invalid-feedback small">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="small fw-bold">Password Akun</label>
                                <input type="password" wire:model="loginPassword" class="form-control rounded-0 p-3 mt-1 @error('loginPassword') is-invalid @enderror" required placeholder="*******">
                                @error('loginPassword') <div class="invalid-feedback small">{{ $message }}</div> @enderror
                            </div>
                            <button type="submit" class="btn-shopee fw-bold py-3 mt-2 shadow-sm">LOG IN</button>
                        </form>
                        <div class="text-center mt-4 pt-3 border-top">
                            <span class="small text-muted">Belum terdaftar?</span>
                            <a href="#" wire:click="setTab('register')" class="small text-primary fw-bold ms-1 text-decoration-none">Daftar Sekarang</a>
                        </div>
                    </div>
                </div>
            </div>

        @elseif($activeTab == 'register')
            <!-- REGISTER TAB -->
            <div class="row justify-content-center py-3">
                <div class="col-md-7">
                    <div class="card border-0 shadow-sm p-4">
                        <div class="text-center mb-4">
                            <h4 class="fw-bold shopee-text-orange mb-1">Daftar Anggota Baru</h4>
                            <p class="small text-muted">Lengkapi data untuk mulai meminjam buku</p>
                        </div>
                        <form wire:submit.prevent="register">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="small fw-bold">Nomor Induk Siswa (NIS)</label>
                                    <input type="text" wire:model="regNis" class="form-control rounded-0 mt-1 @error('regNis') is-invalid @enderror" required>
                                    @error('regNis') <div class="invalid-feedback small">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="small fw-bold">Nama Lengkap</label>
                                    <input type="text" wire:model="regName" class="form-control rounded-0 mt-1 @error('regName') is-invalid @enderror" required>
                                    @error('regName') <div class="invalid-feedback small">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="small fw-bold">Buat Password Baru</label>
                                    <input type="password" wire:model="regPassword" class="form-control rounded-0 mt-1 @error('regPassword') is-invalid @enderror" required placeholder="Min. 4 Karakter">
                                    @error('regPassword') <div class="invalid-feedback small">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="small fw-bold">Kelas</label>
                                    <select wire:model="regClass" class="form-select rounded-0 mt-1 @error('regClass') is-invalid @enderror" required>
                                        <option value="">Pilih Kelas</option>
                                        <option value="VII">VII</option>
                                        <option value="VIII">VIII</option>
                                        <option value="IX">IX</option>
                                    </select>
                                    @error('regClass') <div class="invalid-feedback small">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="small fw-bold">No. WhatsApp</label>
                                    <input type="text" wire:model="regPhone" class="form-control rounded-0 mt-1">
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="small fw-bold">Alamat Lengkap</label>
                                    <textarea wire:model="regAddress" class="form-control rounded-0 mt-1" rows="2"></textarea>
                                </div>
                            </div>
                            <button type="submit" class="btn-shopee fw-bold py-3 mt-3 shadow-sm">DAFTAR SEKARANG</button>
                        </form>
                        <div class="text-center mt-4 pt-3 border-top">
                            <span class="small text-muted">Sudah punya akun?</span>
                            <a href="#" wire:click="setTab('login')" class="small text-primary fw-bold ms-1 text-decoration-none">Login</a>
                        </div>
                    </div>
                </div>
            </div>

        @elseif($activeTab == 'account')
            <!-- ACCOUNT / HISTORY TAB -->
            <div class="row py-3">
                <!-- User Profile Sidebar -->
                <div class="col-md-3">
                    <div class="bg-white p-3 shadow-sm border-0 mb-4 rounded-1">
                        <div class="d-flex align-items-center mb-4">
                            <div class="shopee-orange rounded-circle p-2 me-3 flex-shrink-0">
                                <i class="fas fa-user fa-xl"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="fw-bold truncate text-dark">{{ $activeStudent?->name ?? 'User' }}</div>
                                <div class="small text-muted"><i class="fas fa-pen fs-8 opacity-50"></i> Ubah Profil</div>
                            </div>
                        </div>
                        <hr class="opacity-10">
                        <nav class="nav flex-column small fw-bold">
                            <a class="nav-link shopee-text-orange p-2" href="#"><i class="fas fa-user-circle me-2"></i> Akun Saya</a>
                            <a class="nav-link text-dark p-2" href="#" wire:click="setTab('home')"><i class="fas fa-shopping-bag me-2"></i> Beranda</a>
                            <a class="nav-link text-dark p-2" href="#" wire:click="logout"><i class="fas fa-sign-out-alt me-2"></i> Keluar</a>
                        </nav>
                    </div>
                </div>

                <!-- History Main -->
                <div class="col-md-9">
                    <div class="bg-white shadow-sm rounded-1 overflow-hidden">
                        <!-- Horizontal Tab -->
                        <div class="d-flex border-bottom text-center small fw-medium">
                            <div class="flex-grow-1 p-3 border-bottom border-3 border-danger shopee-text-orange">Semua</div>
                            <div class="flex-grow-1 p-3 border-bottom border-3 border-transparent">Menunggu</div>
                            <div class="flex-grow-1 p-3 border-bottom border-3 border-transparent">Dipinjam</div>
                            <div class="flex-grow-1 p-3 border-bottom border-3 border-transparent">Selesai</div>
                            <div class="flex-grow-1 p-3 border-bottom border-3 border-transparent">Dibatalkan</div>
                        </div>

                        <!-- List -->
                        <div class="p-3 bg-light">
                            @forelse($myBorrowings as $borrow)
                                <div class="bg-white border rounded shadow-sm mb-3">
                                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                                        <div class="fw-bold text-uppercase text-dark" style="font-size: 13px;">{{ $borrow->book->publisher->name ?? 'RumahBaca Sfourtem' }}</div>
                                        <div class="shopee-text-orange small fw-bold text-uppercase">
                                            @php
                                                $labels = [
                                                    'pending' => 'Menunggu Verifikasi',
                                                    'borrowed' => 'Sedang Dipinjam',
                                                    'returning' => 'Proses Pengembalian',
                                                    'returned' => 'Selesai',
                                                    'rejected' => 'Dibatalkan/Ditolak',
                                                ];
                                                echo $labels[$borrow->status] ?? $borrow->status;
                                            @endphp
                                        </div>
                                    </div>
                                    <div class="p-3 d-flex border-bottom">
                                        <div class="me-3" style="width: 80px; height: 100px; background: #f8f8f8;">
                                            @if($borrow->book->cover_image)
                                                <img src="{{ Storage::url($borrow->book->cover_image) }}" class="w-100 h-100 object-fit-cover shadow-sm">
                                            @else
                                                <div class="d-flex align-items-center justify-content-center h-100 text-light"><i class="fas fa-book fa-2x"></i></div>
                                            @endif
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 fw-bold fs-7 text-dark">{{ $borrow->book->title }}</h6>
                                            <div class="text-muted small">Penulis: {{ $borrow->book->author?->name }}</div>
                                            <div class="mt-1 small">
                                                Batas Kembali: {{ $borrow->return_date->format('d/m/Y') }} 
                                                @php
                                                    $diff = now()->startOfDay()->diffInDays($borrow->return_date->startOfDay(), false);
                                                    $diffText = $diff == 0 ? '(Hari ini)' : ($diff > 0 ? "($diff hari lagi)" : "(Terlambat " . abs($diff) . " hari)");
                                                @endphp
                                                <span class="fw-bold {{ $diff < 0 ? 'text-danger' : ($diff == 0 ? 'text-warning' : 'text-success') }}">{{ $diffText }}</span>
                                            </div>
                                            <div class="mt-2 text-end fw-bold text-dark">x1</div>
                                        </div>
                                    </div>
                                    <div class="p-3 bg-light d-flex justify-content-between align-items-center">
                                        <div class="small text-muted">Total Pinjaman: <span class="shopee-text-orange fw-bold">1 Buku</span></div>
                                        <div>
                                            @if($borrow->status == 'borrowed')
                                                <button wire:click="requestReturn({{ $borrow->id }})" class="btn btn-sm shopee-orange px-4">Kembalikan Sekarang</button>
                                            @endif
                                            @if($borrow->status == 'rejected')
                                                <small class="text-danger italic">Alasan: {{ $borrow->rejection_reason }}</small>
                                            @endif
                                            <button class="btn btn-sm btn-outline-secondary px-3 ms-2">Chat Petugas</button>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="bg-white py-5 text-center">
                                    <i class="fas fa-receipt fa-4x text-light mb-3 d-block"></i>
                                    <p class="text-muted">Belum ada peminjaman.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        @endif
        </div>
    </main>

    <!-- Borrow Confirmation Modal -->
    @if($showBorrowModal && $selectedBookItem)
        <div class="modal fade show" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-0 border-0 shadow-lg">
                    <div class="modal-header border-bottom-0 pb-0">
                        <h6 class="fw-bold shopee-text-orange mb-0 uppercase">Konfirmasi Peminjaman</h6>
                        <button type="button" class="btn-close" wire:click="$set('showBorrowModal', false)"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="d-flex mb-4 p-3 bg-light border rounded-1">
                            <div class="me-3" style="width: 60px; height: 80px; background: #eee; flex-shrink: 0;">
                                @if($selectedBookItem->cover_image)
                                    <img src="{{ Storage::url($selectedBookItem->cover_image) }}" class="w-100 h-100 object-fit-cover shadow-sm">
                                @else
                                    <div class="d-flex align-items-center justify-content-center h-100 text-light"><i class="fas fa-book"></i></div>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <h6 class="fw-bold text-dark mb-1 truncate">{{ $selectedBookItem->title }}</h6>
                                <p class="small text-muted mb-0">Penulis: {{ $selectedBookItem->author->name }}</p>
                                <p class="small text-muted mb-0">Penerbit: {{ $selectedBookItem->publisher->name ?? '-' }}</p>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold text-dark mb-2">Batas Tanggal Pengembalian</label>
                            <input type="date" wire:model="returnDate" class="form-control rounded-0 p-3" min="{{ date('Y-m-d') }}">
                            <div class="form-text small opacity-75">Siswa wajib mengembalikan buku pada atau sebelum tanggal ini.</div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <div class="row g-2 w-100">
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-secondary w-100 rounded-0 py-2 border-0" wire:click="$set('showBorrowModal', false)">BATAL</button>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-shopee w-100 rounded-0 py-2" wire:click="confirmBorrow">KONFIRMASI PINJAM</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <footer class="bg-white pt-5 pb-3 border-top mt-5">
        <div class="container overflow-hidden">
            <div class="row g-4">
                <div class="col-md-8">
                    <h6 class="fw-bold mb-4 uppercase text-dark">Tentang RumahBaca</h6>
                    <p>RumahBaca adalah platform peminjaman buku online yang memudahkan siswa untuk meminjam buku dari perpustakaan. Dengan RumahBaca, siswa dapat mengajukan peminjaman buku kapan saja dan di mana saja.</p>
                </div>
                {{-- <div class="col-md-3">
                    <h6 class="fw-bold mb-4 uppercase text-dark">Metode Terverifikasi</h6>
                    <div class="d-flex gap-2 opacity-75">
                        <i class="fab fa-cc-visa fa-2x"></i>
                        <i class="fab fa-cc-mastercard fa-2x"></i>
                    </div>
                </div>
                <div class="col-md-3 text-center">
                    <h6 class="fw-bold mb-4 uppercase text-dark">Download Apps</h6>
                    <div class="opacity-50 border p-3">
                        <i class="fas fa-qrcode fa-5x"></i>
                    </div>
                </div> --}}
            </div>
            <hr class="my-4 opacity-5">
            <div class="text-center text-muted small">
                &copy; {{ date('Y') }} RumahBaca Sfourtem. Seluas Pengetahuan, Semudah Sentuhan.
            </div>
        </div>
    </footer>
</div>
