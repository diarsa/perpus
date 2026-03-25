<?php

use App\Models\Student;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] #[Title('Data Siswa')] class extends Component {
    use WithPagination;

    public $search = '';
    public $nis = '';
    public $name = '';
    public $class = '';
    public $gender = '';
    public $phone = '';
    public $address = '';
    public $studentId = null;

    public $isModalOpen = false;

    public function rules()
    {
        return [
            'nis' => 'required|string|max:20|unique:students,nis,' . $this->studentId,
            'name' => 'required|string|max:255',
            'class' => 'required|string|max:50',
            'gender' => 'nullable|string|in:L,P',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ];
    }

    public function create()
    {
        $this->reset(['nis', 'name', 'class', 'gender', 'phone', 'address', 'studentId']);
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        $student = Student::findOrFail($id);
        $this->studentId = $student->id;
        $this->nis = $student->nis;
        $this->name = $student->name;
        $this->class = $student->class;
        $this->gender = $student->gender;
        $this->phone = $student->phone;
        $this->address = $student->address;
        
        $this->isModalOpen = true;
    }

    public function save()
    {
        $this->validate();

        Student::updateOrCreate(
            ['id' => $this->studentId],
            [
                'nis' => $this->nis,
                'name' => $this->name,
                'class' => $this->class,
                'gender' => $this->gender,
                'phone' => $this->phone,
                'address' => $this->address,
            ]
        );

        $this->isModalOpen = false;
        $this->reset(['nis', 'name', 'class', 'gender', 'phone', 'address', 'studentId']);
        $this->dispatch('showToast', 'Berhasil', 'Data siswa berhasil disimpan.');
    }

    #[Livewire\Attributes\On('deleteStudent')]
    public function delete($id)
    {
        Student::find($id)->delete();
        $this->dispatch('showToast', 'Berhasil', 'Data siswa berhasil dihapus.');
    }

    public function with()
    {
        return [
            'students' => Student::where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('nis', 'like', '%'.$this->search.'%')
                        ->orWhere('class', 'like', '%'.$this->search.'%')
                        ->latest()
                        ->paginate(10),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold">Data Siswa</h1>
            <flux:button variant="primary" wire:click="create">Tambah Siswa</flux:button>
        </div>

        <div class="flex mb-4">
            <flux:input wire:model.live.debounce.300ms="search" autocomplete="off" placeholder="Cari NIS, Nama, Kelas..." icon="magnifying-glass" class="w-full md:w-1/3" />
        </div>

        <div class="bg-white dark:bg-zinc-900 shadow rounded-lg overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-6 py-3">NIS</th>
                        <th class="px-6 py-3">Nama Siswa</th>
                        <th class="px-6 py-3 text-center">Kelas / Gender</th>
                        <th class="px-6 py-3 text-center">No HP</th>
                        <th class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        <tr class="border-b dark:border-zinc-700 hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors">
                            <td class="px-6 py-4 font-bold text-zinc-900 dark:text-white">{{ $student->nis }}</td>
                            <td class="px-6 py-4">
                                <span class="font-medium text-zinc-900 dark:text-white">{{ $student->name }}</span>
                            </td>
                            <td class="px-6 py-4 text-center text-zinc-600 dark:text-zinc-400 text-xs">
                                {{ $student->class }} <br> 
                                <span class="text-zinc-400 capitalize">{{ $student->gender == 'L' ? 'Laki-laki' : ($student->gender == 'P' ? 'Perempuan' : '-') }}</span>
                            </td>
                            <td class="px-6 py-4 text-center text-zinc-600 dark:text-zinc-400 text-xs">{{ $student->phone ?? '-' }}</td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <flux:button size="sm" variant="outline" wire:click="edit({{ $student->id }})">Edit</flux:button>
                                <flux:button size="sm" variant="danger" wire:click="$dispatch('showConfirmModal', { title: 'Hapus Siswa', message: 'Anda yakin ingin menghapus data siswa ini secara permanen?', actionEvent: 'deleteStudent', actionParams: [{{ $student->id }}] })">Hapus</flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-zinc-500">Pencarian tidak menemukan data siswa.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4">
                {{ $students->links() }}
            </div>
        </div>

        <flux:modal wire:model="isModalOpen" class="w-full max-w-lg">
            <div class="p-6">
                <h2 class="text-xl font-bold mb-4">{{ $studentId ? 'Edit Siswa' : 'Tambah Siswa' }}</h2>
                <form wire:submit="save" class="space-y-6 mt-4">
                    <div class="grid grid-cols-2 gap-6">
                        <flux:field>
                            <flux:label>NIS <span class="text-red-500 ml-1">*</span></flux:label>
                            <flux:input wire:model="nis" required />
                        </flux:field>
                        <flux:field>
                            <flux:label>Kelas <span class="text-red-500 ml-1">*</span></flux:label>
                            <flux:select wire:model="class" placeholder="Pilih Kelas..." required>
                                <flux:select.option value="VII">VII</flux:select.option>
                                <flux:select.option value="VIII">VIII</flux:select.option>
                                <flux:select.option value="IX">IX</flux:select.option>
                                <flux:select.option value="Lainnya">Lainnya</flux:select.option>
                            </flux:select>
                        </flux:field>
                    </div>
                    
                    <flux:field>
                        <flux:label>Nama Lengkap <span class="text-red-500 ml-1">*</span></flux:label>
                        <flux:input wire:model="name" required />
                    </flux:field>
                    
                    <flux:input wire:model="phone" label="No Telepon/HP" />
                    
                    <flux:field>
                        <flux:label>Jenis Kelamin</flux:label>
                        <div class="flex gap-4 mt-3">
                            <label class="flex items-center gap-2"><input type="radio" wire:model="gender" value="L" class="border-zinc-300 rounded focus:ring-zinc-800"> Laki-laki</label>
                            <label class="flex items-center gap-2"><input type="radio" wire:model="gender" value="P" class="border-zinc-300 rounded focus:ring-zinc-800"> Perempuan</label>
                        </div>
                    </flux:field>
                    
                    <flux:textarea wire:model="address" label="Alamat" rows="4" />
                    
                    <div class="flex justify-end gap-3 mt-8">
                        <flux:button variant="ghost" wire:click="$set('isModalOpen', false)">Batal</flux:button>
                        <flux:button variant="primary" type="submit">Simpan</flux:button>
                    </div>
                </form>
            </div>
        </flux:modal>
</div>
