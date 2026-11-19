<?php

namespace App\Http\Middleware\budget_mw;

use App\Support\budget_s\BudgetPeran;
use Closure;
use Illuminate\Http\Request;

/**
 * Tanpa parameter  : user wajib punya akses budget aktif (kalau tidak, ke halaman no-access).
 * Dengan parameter : CekAksesBudget::class.':admin,dept_head'  -> peran harus salah satunya (kalau tidak, 403).
 */
class CekAksesBudget
{
    public function handle(Request $request, Closure $next, string ...$peranBoleh)
    {
        $peran = BudgetPeran::peran($request->user());

        if ($peran === null) {
            return redirect()->route('no-access');
        }
        if ($peranBoleh && !in_array($peran, $peranBoleh, true)) {
            abort(403, 'Anda tidak memiliki hak untuk halaman ini.');
        }

        view()->share('budgetPeran', $peran);
        return $next($request);
    }
}
