<x-layouts.app title="Ganti Password">
    <form method="post" class="panel max-w-xl">
        @csrf
        <label class="label mb-3">Password saat ini <input class="input" type="password" name="current_password" required></label>
        <label class="label mb-3">Password baru <input class="input" type="password" name="password" required></label>
        <label class="label mb-5">Konfirmasi password <input class="input" type="password" name="password_confirmation" required></label>
        <button class="btn">Simpan Password</button>
    </form>
</x-layouts.app>
