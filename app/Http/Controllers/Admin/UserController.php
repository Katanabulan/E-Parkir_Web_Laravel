<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Tampilkan daftar akun Admin dan Owner (tanpa client)
     */
    public function index(Request $request)
    {
        $search = trim($request->query('search', ''));
        $roleFilter = trim($request->query('role', 'all'));

        $query = User::whereIn('role', ['admin', 'owner']);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        if (in_array($roleFilter, ['admin', 'owner'])) {
            $query->where('role', $roleFilter);
        }

        $users = $query->orderBy('role', 'asc')
                      ->orderBy('id', 'desc')
                      ->get();

        $adminCount = User::where('role', 'admin')->count();
        $ownerCount = User::where('role', 'owner')->count();
        $totalStaff = $adminCount + $ownerCount;

        return view('admin.users', compact(
            'users',
            'adminCount',
            'ownerCount',
            'totalStaff',
            'search',
            'roleFilter'
        ));
    }

    /**
     * Tambah akun Admin atau Owner baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'role' => ['required', Rule::in(['admin', 'owner'])],
            'password' => 'required|string|min:6',
        ], [
            'name.required' => 'Nama lengkap wajib diisi!',
            'email.required' => 'Email wajib diisi!',
            'email.email' => 'Format email tidak valid!',
            'email.unique' => 'Email ini sudah terdaftar di sistem!',
            'role.required' => 'Pilih role akun (Admin / Owner)!',
            'role.in' => 'Role hanya boleh Administrator atau Owner!',
            'password.required' => 'Password wajib diisi!',
            'password.min' => 'Password minimal terdiri dari 6 karakter!',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);

        $roleLabel = $user->role === 'admin' ? 'Administrator' : 'Owner';
        return redirect()->route('admin.users.index')
            ->with('success', "Akun {$roleLabel} '{$user->name}' berhasil ditambahkan ke sistem!");
    }

    /**
     * Perbarui data akun Admin atau Owner
     */
    public function update(Request $request, $id)
    {
        $user = User::whereIn('role', ['admin', 'owner'])->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'role' => ['required', Rule::in(['admin', 'owner'])],
            'password' => 'nullable|string|min:6',
        ], [
            'name.required' => 'Nama lengkap wajib diisi!',
            'email.required' => 'Email wajib diisi!',
            'email.email' => 'Format email tidak valid!',
            'email.unique' => 'Email ini sudah digunakan oleh akun lain!',
            'role.required' => 'Pilih role akun (Admin / Owner)!',
            'role.in' => 'Role hanya boleh Administrator atau Owner!',
            'password.min' => 'Password baru minimal terdiri dari 6 karakter!',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;
        $user->role = $validated['role'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        $roleLabel = $user->role === 'admin' ? 'Administrator' : 'Owner';
        return redirect()->route('admin.users.index')
            ->with('success', "Data akun {$roleLabel} '{$user->name}' berhasil diperbarui!");
    }

    /**
     * Hapus akun Admin atau Owner
     */
    public function destroy($id)
    {
        $user = User::whereIn('role', ['admin', 'owner'])->findOrFail($id);

        // Keamanan 1: Cegah menghapus akun sendiri yang sedang login
        if ($user->id === Auth::id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Aksi ditolak: Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif login!');
        }

        // Keamanan 2: Cegah jika admin tersisa hanya 1
        if ($user->role === 'admin') {
            $remainingAdmins = User::where('role', 'admin')->where('id', '!=', $user->id)->count();
            if ($remainingAdmins === 0) {
                return redirect()->route('admin.users.index')
                    ->with('error', 'Aksi ditolak: Sistem harus memiliki minimal satu akun Administrator aktif!');
            }
        }

        $userName = $user->name;
        $roleLabel = $user->role === 'admin' ? 'Administrator' : 'Owner';

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', "Akun {$roleLabel} '{$userName}' telah berhasil dihapus dari sistem.");
    }
}
