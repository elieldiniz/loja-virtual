<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\MessageBuilder;
use App\Services\Storefront\StorefrontLinks;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GiftController extends Controller
{
    public const RECIPIENTS = ['Mãe', 'Namorada', 'Amiga', 'Filha', 'Para mim'];

    public const OCCASIONS = ['Aniversário', 'Dia das Mães', 'Natal', 'Namoro', 'Formatura', 'Só porque sim'];

    public const BUDGETS = ['Até R$ 100', 'R$ 100 a R$ 200', 'Acima de R$ 200'];

    /** GET /presente: monta a mensagem de ajuda para presente e abre o WhatsApp da loja. */
    public function __invoke(Request $request, MessageBuilder $messages, StorefrontLinks $links): RedirectResponse
    {
        $data = $request->validate([
            'para' => ['required', 'in:'.implode(',', self::RECIPIENTS)],
            'ocasiao' => ['nullable', 'in:'.implode(',', self::OCCASIONS)],
            'valor' => ['required', 'in:'.implode(',', self::BUDGETS)],
            'detalhe' => ['nullable', 'string', 'max:140'],
        ]);

        if (! $links->hasWhatsapp()) {
            return redirect()->route('home')->withErrors(['whatsapp' => 'O atendimento por WhatsApp ainda não está configurado.']);
        }

        return redirect()->away($links->whatsapp($messages->gift([
            'recipient' => $data['para'],
            'occasion' => $data['ocasiao'] ?? null,
            'budget' => $data['valor'],
            'detail' => $data['detalhe'] ?? null,
        ])));
    }
}
