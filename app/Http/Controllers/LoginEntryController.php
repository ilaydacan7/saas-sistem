<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Central\WorkspaceController;
use App\Http\Controllers\Tenant\LoginController;
use App\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * /giris adresi iki ayrı işe hizmet eder:
 *  - Kiracı adresinde  : o şirketin giriş formu
 *  - Merkezi adreste   : şirket adresini soran giriş kapısı
 *
 * Aynı yolu iki rotaya bölemediğimiz için (Laravel ilk eşleşen rotayı seçer)
 * yönlendirme burada yapılır.
 */
class LoginEntryController extends Controller
{
    public function __construct(private readonly TenantContext $context) {}

    public function create(Request $request): View
    {
        return $this->context->has()
            ? app(LoginController::class)->create($request)
            : app(WorkspaceController::class)->create();
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->context->has()
            ? app(LoginController::class)->store($request)
            : app(WorkspaceController::class)->store($request);
    }
}
