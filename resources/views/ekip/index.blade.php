@extends('layouts.uygulama')

@section('baslik', 'Ekip')

@section('icerik')
<div class="mb-5">
    <h1 class="text-xl font-semibold tracking-tight">Ekip</h1>
    <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ $tenant->name }}</p>
</div>

@error('uye')<x-notice tone="uyari">{{ $message }}</x-notice>@enderror
@error('role')<x-notice tone="uyari">{{ $message }}</x-notice>@enderror

<x-card class="mb-4">
    <h2 class="mb-4 text-base font-semibold">Ekip arkadaşı davet et</h2>

    <form method="POST" action="{{ route('ekip.davet') }}">
        @csrf
        <div class="grid gap-x-4 sm:grid-cols-2">
            <x-text-field name="email" label="E-posta" type="email" required />

            <div class="mb-4">
                <label for="role" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-200">Rol</label>
                <select id="role" name="role"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
                    @foreach ($atanabilirRoller as $rol)
                        <option value="{{ $rol->value }}" @selected(old('role') === $rol->value)>{{ $rol->label() }}</option>
                    @endforeach
                </select>
                @error('role')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
        </div>

        <x-button type="submit">Davet gönder</x-button>
    </form>
</x-card>

<x-card class="mb-4 !p-0 overflow-hidden">
    <h2 class="border-b border-slate-100 px-7 py-4 text-base font-semibold dark:border-slate-800">
        Üyeler ({{ $uyeler->count() }})
    </h2>

    <table class="w-full text-left text-sm">
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            @foreach ($uyeler as $uye)
                <tr>
                    <td class="px-7 py-3">
                        <div class="font-medium">
                            {{ $uye->name }}
                            @if ($uye->is(auth()->user()))
                                <span class="text-xs font-normal text-slate-400">(siz)</span>
                            @endif
                        </div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">{{ $uye->email }}</div>
                    </td>
                    <td class="px-7 py-3">
                        @if ($uye->is(auth()->user()) || ! auth()->user()->role->canActOn($uye->role))
                            <span class="text-slate-500 dark:text-slate-400">{{ $uye->role->label() }}</span>
                        @else
                            <form method="POST" action="{{ route('ekip.rol', $uye) }}">
                                @csrf
                                @method('PATCH')
                                <select name="role" onchange="this.form.submit()"
                                        class="rounded-lg border border-slate-300 bg-white px-2 py-1 text-sm dark:border-slate-700 dark:bg-slate-900">
                                    @foreach ($atanabilirRoller as $rol)
                                        <option value="{{ $rol->value }}" @selected($uye->role === $rol)>{{ $rol->label() }}</option>
                                    @endforeach
                                </select>
                            </form>
                        @endif
                    </td>
                    <td class="px-7 py-3 text-right">
                        @if (! $uye->is(auth()->user()) && auth()->user()->role->canActOn($uye->role))
                            <form method="POST" action="{{ route('ekip.cikar', $uye) }}"
                                  onsubmit="return confirm('{{ $uye->name }} ekipten çıkarılacak. Onaylıyor musunuz?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-medium text-red-600 hover:underline dark:text-red-400">
                                    Çıkar
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</x-card>

@if ($davetler->isNotEmpty())
    <x-card class="!p-0 overflow-hidden">
        <h2 class="border-b border-slate-100 px-7 py-4 text-base font-semibold dark:border-slate-800">
            Bekleyen davetler ({{ $davetler->count() }})
        </h2>

        <table class="w-full text-left text-sm">
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach ($davetler as $davet)
                    <tr>
                        <td class="px-7 py-3">
                            <div class="font-medium">{{ $davet->email }}</div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">
                                {{ $davet->role->label() }} ·
                                {{ $davet->expires_at->format('d.m.Y') }} tarihine kadar geçerli
                                @if ($davet->invitedBy)
                                    · {{ $davet->invitedBy->name }} davet etti
                                @endif
                            </div>
                        </td>
                        <td class="px-7 py-3 text-right">
                            <form method="POST" action="{{ route('ekip.davet.iptal', $davet) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-medium text-slate-500 hover:underline dark:text-slate-400">
                                    İptal et
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-card>
@endif
@endsection
