<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Finance\CashBook;
use App\Finance\FinanceException;
use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionRequest;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Modules\PaymentMethod;
use App\Modules\TransactionType;
use App\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TransactionController extends Controller
{
    public function __construct(
        private readonly CashBook $kasa,
        private readonly TenantContext $context,
    ) {}

    public function index(Request $request): View
    {
        $aralik = $this->aralik($request);
        $tur = TransactionType::tryFrom((string) $request->query('tur'));
        $kategoriId = $request->integer('kategori');

        $transactions = Transaction::query()
            ->with('category')
            ->between($aralik['baslangic'], $aralik['bitis'])
            ->when($tur !== null, fn (Builder $q) => $q->where('type', $tur))
            ->when($kategoriId > 0, fn (Builder $q) => $q->where('category_id', $kategoriId))
            ->orderByDesc('occurred_on')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('finans.index', [
            'transactions' => $transactions,
            'ozet' => $this->kasa->summary($aralik['baslangic'], $aralik['bitis']),
            'kategoriler' => TransactionCategory::query()->orderBy('type')->orderBy('name')->get(),
            'aralik' => $aralik,
            'tur' => $tur,
            'kategoriId' => $kategoriId,
            'kategoriDagilimi' => $this->kategoriDagilimi($aralik),
        ]);
    }

    public function create(Request $request): View
    {
        $this->kasa->ensureCategories($this->context->getOrFail());

        return view('finans.form', [
            'transaction' => new Transaction,
            'kategoriler' => TransactionCategory::query()->orderBy('name')->get(),
            'seciliTur' => TransactionType::tryFrom((string) $request->query('tur')) ?? TransactionType::Expense,
        ]);
    }

    public function store(TransactionRequest $request): RedirectResponse
    {
        try {
            $this->kasa->record(
                tenant: $this->context->getOrFail(),
                type: $request->transactionType(),
                amountMinor: $request->amountMinor(),
                occurredOn: Carbon::parse($request->validated('occurred_on')),
                categoryId: $request->categoryId(),
                description: $request->validated('description'),
                method: $request->validated('method') !== null
                    ? PaymentMethod::from($request->validated('method'))
                    : null,
                user: $request->user(),
            );
        } catch (FinanceException $e) {
            throw ValidationException::withMessages(['amount' => $e->getMessage()]);
        }

        return redirect()
            ->route('finans.index')
            ->with('durum', $request->transactionType()->label().' kaydedildi.');
    }

    public function edit(Transaction $transaction): View
    {
        $this->elleDuzenlenebilirOlmali($transaction);

        return view('finans.form', [
            'transaction' => $transaction,
            'kategoriler' => TransactionCategory::query()->orderBy('name')->get(),
            'seciliTur' => $transaction->type,
        ]);
    }

    public function update(TransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        $this->elleDuzenlenebilirOlmali($transaction);

        $transaction->update([
            'type' => $request->transactionType(),
            'category_id' => $request->categoryId(),
            'amount_minor' => $request->amountMinor(),
            'occurred_on' => Carbon::parse($request->validated('occurred_on'))->toDateString(),
            'description' => $request->validated('description'),
            'method' => $request->validated('method'),
        ]);

        return redirect()->route('finans.index')->with('durum', 'Kayıt güncellendi.');
    }

    public function destroy(Transaction $transaction): RedirectResponse
    {
        $this->elleDuzenlenebilirOlmali($transaction);

        $transaction->delete();

        return redirect()->route('finans.index')->with('durum', 'Kayıt silindi.');
    }

    /**
     * Satış tahsilatından doğan kayıtlar burada değiştirilemez; kaynağı satıştır.
     */
    private function elleDuzenlenebilirOlmali(Transaction $transaction): void
    {
        if ($transaction->isAutomatic()) {
            throw new HttpException(403, 'Satıştan gelen kayıtlar buradan değiştirilemez. İlgili satışa gidin.');
        }
    }

    /**
     * @return array{baslangic: Carbon, bitis: Carbon, anahtar: string}
     */
    private function aralik(Request $request): array
    {
        return match ((string) $request->query('donem')) {
            'hafta' => ['baslangic' => now()->startOfWeek(), 'bitis' => now()->endOfWeek(), 'anahtar' => 'hafta'],
            'yil' => ['baslangic' => now()->startOfYear(), 'bitis' => now()->endOfYear(), 'anahtar' => 'yil'],
            'gecenay' => [
                'baslangic' => now()->subMonthNoOverflow()->startOfMonth(),
                'bitis' => now()->subMonthNoOverflow()->endOfMonth(),
                'anahtar' => 'gecenay',
            ],
            default => ['baslangic' => now()->startOfMonth(), 'bitis' => now()->endOfMonth(), 'anahtar' => 'ay'],
        };
    }

    /**
     * @param  array{baslangic: Carbon, bitis: Carbon}  $aralik
     * @return array<string, array{ad: string, tutar: int}>
     */
    private function kategoriDagilimi(array $aralik): array
    {
        return Transaction::query()
            ->expense()
            ->between($aralik['baslangic'], $aralik['bitis'])
            ->with('category')
            ->get()
            ->groupBy(fn (Transaction $islem) => $islem->category?->name ?? 'Kategorisiz')
            ->map(fn ($grup, $ad) => ['ad' => $ad, 'tutar' => (int) $grup->sum('amount_minor')])
            ->sortByDesc('tutar')
            ->take(6)
            ->all();
    }
}
