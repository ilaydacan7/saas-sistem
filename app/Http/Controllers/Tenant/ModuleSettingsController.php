<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Modules\Module;
use App\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ModuleSettingsController extends Controller
{
    public function __construct(private readonly TenantContext $context) {}

    public function index(): View
    {
        return view('ayarlar.moduller', [
            'tenant' => $this->context->getOrFail(),
            'moduller' => Module::cases(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $veri = $request->validate([
            'module' => ['required', Rule::enum(Module::class)],
            'acik' => ['required', 'boolean'],
        ], [], ['module' => 'modül']);

        $tenant = $this->context->getOrFail();
        $module = Module::from($veri['module']);

        if (! $module->isAvailable()) {
            return back()->withErrors(['module' => $module->label().' modülü henüz kullanıma açılmadı.']);
        }

        if ($module->isCore() && ! $request->boolean('acik')) {
            return back()->withErrors(['module' => $module->label().' modülü kapatılamaz, diğer modüller buna bağlı.']);
        }

        if ($request->boolean('acik')) {
            $tenant->enableModule($module);
            $mesaj = $module->label().' modülü açıldı.';
        } else {
            $tenant->disableModule($module);
            $mesaj = $module->label().' modülü kapatıldı.';
        }

        return back()->with('durum', $mesaj);
    }
}
