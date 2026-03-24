<?php

use App\Models\Book;
use App\Models\Student;
use App\Models\Borrowing;
use App\Models\Author;
use App\Models\Publisher;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] #[Title('Dashboard')] class extends Component {
    public function with()
    {
        return [
            'stats' => [
                'total_books' => Book::sum('stock'),
                'total_titles' => Book::count(),
                'total_students' => Student::count(),
                'active_borrowings' => Borrowing::where('status', 'borrowed')->count(),
                'pending_requests' => Borrowing::where('status', 'pending')->count(),
                'overdue_borrowings' => Borrowing::where('status', 'borrowed')
                    ->where('return_date', '<', now()->startOfDay())
                    ->count(),
            ],
            'recent_borrowings' => Borrowing::with(['student', 'book'])
                ->latest()
                ->take(5)
                ->get(),
            'popular_books' => Book::withCount(['borrowings' => function($q) {
                    $q->whereIn('status', ['borrowed', 'returned']);
                }])
                ->orderBy('borrowings_count', 'desc')
                ->take(5)
                ->get(),
            'low_stock_books' => Book::where('available_stock', '<=', 3)
                ->take(5)
                ->get(),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-2 lg:p-4 transition-all duration-300">
    <div class="flex justify-between items-end">
        <div class="flex flex-col gap-1">
            <h1 class="text-2xl font-black text-zinc-900 dark:text-zinc-100 italic tracking-tight">DASHBOARD ADMIN</h1>
            <p class="text-[10px] text-zinc-500 font-bold uppercase tracking-widest leading-none">Ringkasan Sirkulasi Literasi Sekolah</p>
        </div>
        <div class="text-right hidden sm:block">
            <div class="text-sm font-black text-zinc-900 dark:text-zinc-200 uppercase tracking-tighter italic border-b-2 border-blue-600 inline-block px-1">{{ now()->translatedFormat('d F Y') }}</div>
            <div class="text-[10px] text-zinc-400 font-bold mt-1">{{ now()->format('H:i') }} WIB</div>
        </div>
    </div>

    @if($stats['pending_requests'] > 0)
        <a href="{{ route('admin.borrowings') }}" class="relative group overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-700 p-6 text-white shadow-xl shadow-blue-200 dark:shadow-none animate-in fade-in slide-in-from-top duration-500">
            <div class="relative z-10 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-white/20 rounded-xl backdrop-blur-md animate-pulse">
                        <flux:icon.bell-alert class="size-6" />
                    </div>
                    <div>
                        <h4 class="text-lg font-black italic tracking-tight uppercase">Permintaan Baru!</h4>
                        <p class="text-blue-100 text-xs font-bold leading-none">Ada {{ $stats['pending_requests'] }} pengajuan peminjaman dari siswa yang menunggu persetujuan Anda.</p>
                    </div>
                </div>
                <div class="font-black text-xs uppercase tracking-widest bg-white text-blue-600 px-4 py-2 rounded-lg group-hover:bg-blue-50 transition-colors shadow-lg">
                    CEK SEKARANG &raquo;
                </div>
            </div>
            <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:scale-110 transition-transform">
                <flux:icon.bell-alert class="size-32" />
            </div>
        </a>
    @endif

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        {{-- Row 1: Collection & Active --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="rounded-2xl border border-zinc-100 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 group hover:border-blue-500 transition-colors">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Total Koleksi</span>
                    <div class="rounded-xl bg-blue-50 p-2 dark:bg-blue-900/20">
                        <flux:icon.book-open class="size-5 text-blue-600" />
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <h3 class="text-4xl font-black text-zinc-900 dark:text-zinc-100 italic">{{ number_format($stats['total_books']) }}</h3>
                    <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Buku</span>
                </div>
                <div class="mt-4 flex items-center gap-2">
                    <span class="text-[10px] font-bold text-zinc-400 italic">{{ $stats['total_titles'] }} JUDUL TERSEDIA</span>
                </div>
            </div>

            <div class="rounded-2xl border border-zinc-100 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 group hover:border-amber-500 transition-colors">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Sirkulasi Aktif</span>
                    <div class="rounded-xl bg-amber-50 p-2 dark:bg-amber-900/20 text-amber-600">
                        <flux:icon.clipboard-document-list class="size-5" />
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <h3 class="text-4xl font-black text-zinc-900 dark:text-zinc-100 italic">{{ number_format($stats['active_borrowings']) }}</h3>
                    <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Siswa</span>
                </div>
                <div class="mt-4">
                    <span class="text-[10px] font-black uppercase py-0.5 px-2 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded-lg">Dipinjam</span>
                </div>
            </div>
        </div>

        {{-- Row 2: Members & Overdue --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="rounded-2xl border border-zinc-100 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 group hover:border-emerald-500 transition-colors">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Anggota</span>
                    <div class="rounded-xl bg-emerald-50 p-2 dark:bg-emerald-900/20 text-emerald-600">
                        <flux:icon.users class="size-5" />
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <h3 class="text-4xl font-black text-zinc-900 dark:text-zinc-100 italic">{{ number_format($stats['total_students']) }}</h3>
                    <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Terdaftar</span>
                </div>
            </div>

            <div class="rounded-2xl border border-zinc-100 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 group hover:border-red-500 transition-colors">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Terlambat</span>
                    <div class="rounded-xl bg-red-50 p-2 dark:bg-red-900/20 text-red-600">
                        <flux:icon.clock class="size-5" />
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <h3 class="text-4xl font-black text-red-600 italic">{{ $stats['overdue_borrowings'] }}</h3>
                    <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Transaksi</span>
                </div>
                <div class="mt-4">
                     @if($stats['overdue_borrowings'] > 0)
                        <span class="text-[10px] font-black uppercase text-red-600 animate-pulse">Butuh Perhatian!</span>
                     @else
                        <span class="text-[10px] font-bold text-zinc-400 uppercase">Tepat Waktu</span>
                     @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Content Sections --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        
        {{-- Popular --}}
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-100 dark:border-zinc-800 shadow-sm flex flex-col overflow-hidden">
            <div class="p-4 border-b border-zinc-50 dark:border-zinc-800 bg-zinc-50/30 dark:bg-zinc-800/20">
                <h4 class="text-xs font-black uppercase tracking-widest italic flex items-center gap-2">
                    <flux:icon.presentation-chart-line class="size-4 text-blue-600" />
                    TOP 5 POPULER
                </h4>
            </div>
            <div class="divide-y divide-zinc-50 dark:divide-zinc-800">
                @forelse($popular_books as $book)
                    <div class="p-4 flex items-center gap-3">
                        <div class="size-10 rounded-xl bg-zinc-50 dark:bg-zinc-800 flex flex-col items-center justify-center border border-zinc-100 dark:border-zinc-800 shrink-0">
                            <span class="text-sm font-black text-blue-600">{{ $book->borrowings_count }}</span>
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs font-black text-zinc-800 dark:text-zinc-100 truncate line-clamp-1 italic">{{ $book->title }}</div>
                            <div class="text-[9px] font-bold text-zinc-400 uppercase tracking-tighter">{{ $book->isbn }}</div>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-xs text-zinc-400 italic uppercase">Belum ada tren peminjaman.</div>
                @endforelse
            </div>
        </div>

        {{-- Stock --}}
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-100 dark:border-zinc-800 shadow-sm flex flex-col overflow-hidden">
            <div class="p-4 border-b border-zinc-50 dark:border-zinc-800 bg-zinc-50/30 dark:bg-zinc-800/20">
                <h4 class="text-xs font-black uppercase tracking-widest italic flex items-center gap-2 text-amber-600">
                    <flux:icon.exclamation-triangle class="size-4" />
                    STOK KRITIS
                </h4>
            </div>
            <div class="divide-y divide-zinc-50 dark:divide-zinc-800">
                @forelse($low_stock_books as $book)
                    <div class="p-4 flex items-center justify-between">
                        <div class="min-w-0">
                            <div class="text-xs font-black text-zinc-800 dark:text-zinc-100 truncate line-clamp-1 italic capitalize">{{ $book->title }}</div>
                            <div class="text-[9px] font-bold text-zinc-400 uppercase tracking-tighter">ISBN: {{ $book->isbn }}</div>
                        </div>
                        <div class="px-2 py-1 rounded bg-amber-50 dark:bg-amber-900/20 text-amber-600 font-black text-xs border border-amber-100 dark:border-amber-800">
                            {{ $book->available_stock }}
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-xs text-zinc-400 italic uppercase">Logistik aman terkendali.</div>
                @endforelse
            </div>
        </div>

        {{-- Activity --}}
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-100 dark:border-zinc-800 shadow-sm flex flex-col overflow-hidden">
            <div class="p-4 border-b border-zinc-50 dark:border-zinc-800 bg-zinc-50/30 dark:bg-zinc-800/20 flex justify-between items-center">
                <h4 class="text-xs font-black uppercase tracking-widest italic flex items-center gap-2 text-emerald-600">
                    <flux:icon.arrow-path class="size-4" />
                    AKTIVITAS
                </h4>
                <a href="{{ route('admin.borrowings') }}" class="text-[9px] font-black text-blue-600 hover:underline tracking-tighter uppercase">Detail &raquo;</a>
            </div>
            <div class="divide-y divide-zinc-50 dark:divide-zinc-800">
                @forelse($recent_borrowings as $borrow)
                    <div class="p-4 flex items-center gap-3">
                        <div class="size-10 rounded-full border border-zinc-100 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800 flex items-center justify-center font-black text-[10px] text-zinc-400 uppercase italic">
                            {{ substr($borrow->student->name, 0, 2) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-[10px] font-black text-zinc-800 dark:text-zinc-100 truncate italic uppercase leading-tight">{{ $borrow->student->name }}</div>
                            <div class="text-[9px] font-bold text-zinc-500 truncate mt-0.5 line-clamp-1">{{ $borrow->book->title }}</div>
                        </div>
                        <div>
                             @php
                                $statusDot = [
                                    'pending' => 'bg-blue-500',
                                    'borrowed' => 'bg-amber-500',
                                    'returned' => 'bg-emerald-500',
                                    'rejected' => 'bg-red-500',
                                ];
                            @endphp
                            <div class="h-2 w-2 rounded-full {{ $statusDot[$borrow->status] ?? 'bg-zinc-300' }} shadow-sm"></div>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-xs text-zinc-400 italic uppercase">Hening... belum ada log sirkulasi.</div>
                @endforelse
            </div>
        </div>

    </div>
</div>
