<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ReceivesInstallments;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StoreSetting;
use App\Services\Birthdays;
use App\Services\Dashboard\DashboardMetrics;
use App\Services\Dashboard\Period;
use App\Services\MessageBuilder;
use App\Services\Receivables;
use App\Services\WhatsAppLink;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.admin')]
class Dashboard extends Component
{
    use ReceivesInstallments;

    #[Url(as: 'dias')]
    public int $range = 30;

    public string $metric = 'revenue';

    public bool $showTable = false;

    public function setRange(int $days): void
    {
        $this->range = in_array($days, Period::ALLOWED, true) ? $days : 30;
    }

    public function setMetric(string $metric): void
    {
        $this->metric = in_array($metric, ['revenue', 'count', 'pieces'], true) ? $metric : 'revenue';
    }

    public function toggleTable(): void
    {
        $this->showTable = ! $this->showTable;
    }

    public function render(DashboardMetrics $metrics, Receivables $receivables, Birthdays $birthdays, MessageBuilder $messages)
    {
        $period = Period::last($this->range);
        $previous = $period->previous();
        $start = CarbonImmutable::instance($period->start);
        $end = CarbonImmutable::instance($period->end);

        $current = $metrics->totals($start, $end);
        $before = $metrics->totals($previous->start, $previous->end);

        $kpis = [
            ['Faturamento', 'R$ '.number_format((float) round($current['revenue']->amount() / 100), 0, ',', '.'), $metrics->delta($current['revenue']->amount(), $before['revenue']->amount())],
            ['Vendas', number_format($current['count'], 0, ',', '.'), $metrics->delta($current['count'], $before['count'])],
            ['Ticket médio', $current['average']->format(), $metrics->delta($current['average']->amount(), $before['average']->amount())],
            ['Peças vendidas', number_format($current['pieces'], 0, ',', '.'), $metrics->delta($current['pieces'], $before['pieces'])],
        ];

        $topProducts = $metrics->topProducts($start, $end);
        $covers = Product::query()->with('media')->whereIn('id', collect($topProducts)->pluck('product_id')->filter())->get()->keyBy('id');

        $giftPercent = StoreSetting::current()->birthday_gift_percent;
        $birthdayRows = $birthdays->forMonth()->map(function (array $row) use ($messages, $giftPercent) {
            $customer = $row['customer'];
            $row['label'] = Birthdays::label($row['diff']);
            $row['whatsapp'] = $customer->whatsapp ? WhatsAppLink::make($customer->whatsapp, $messages->birthday($customer, $giftPercent)) : null;

            return $row;
        });

        return view('livewire.admin.dashboard', [
            'kpis' => $kpis,
            'revenue' => $current['revenue'],
            'series' => $metrics->series($period),
            'topProducts' => $topProducts,
            'covers' => $covers,
            'categories' => $metrics->revenueByCategory($start, $end),
            'payments' => $metrics->paymentBreakdown($start, $end),
            'channels' => $metrics->channels($start, $end),
            'bestCustomers' => $metrics->topCustomers($start, $end),
            'salesCount' => $current['count'],
            'receivableTotal' => $receivables->openTotal(),
            'receivableCount' => $receivables->openCount(),
            'overdueTotal' => $receivables->overdueTotal(),
            'overdueCount' => $receivables->overdueCount(),
            'nextInstallments' => $receivables->next(5),
            'recentSales' => Sale::query()->notCancelled()->with(['items', 'customer', 'paymentMethod', 'channel'])->latest('sold_at')->latest('id')->limit(6)->get(),
            'birthdays' => $birthdayRows,
            'giftPercent' => $giftPercent,
            'monthName' => ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'][today()->month - 1],
            'zero' => Money::zero(),
        ])->layoutData(['current' => 'dash', 'title' => 'Painel']);
    }
}
