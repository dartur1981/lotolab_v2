<?php

namespace App\Filament\App\Concerns;

trait HasJogosStatusCards
{
    public function getStatusStats(): array
    {
        $owner = $this->getOwnerRecord();

        $counts = $owner->jogos()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $pendente = (int) ($counts['0'] ?? $counts[0] ?? 0);
        $divergente = (int) ($counts['1'] ?? $counts[1] ?? 0);
        $lancado = (int) ($counts['3'] ?? $counts[3] ?? 0);
        $apostado = (int) ($counts['4'] ?? $counts[4] ?? 0);

        $total = array_sum(array_map('intval', $counts));

        $pct = fn (int $n): float => $total > 0 ? round(($n / $total) * 100, 1) : 0.0;

        return [
            'total' => $total,
            'apostado' => $apostado,
            'lancado' => $lancado,
            'pendente' => $pendente,
            'divergente' => $divergente,
            'pct_apostado' => $pct($apostado),
            'pct_lancado' => $pct($lancado),
            'pct_pendente' => $pct($pendente),
            'pct_divergente' => $pct($divergente),
        ];
    }

    public function getActiveStatusFilter(): ?string
    {
        $values = $this->tableFilters['status']['values'] ?? [];
        if (is_array($values) && count($values) === 1) {
            return (string) reset($values);
        }

        return null;
    }

    public function filtrarPorStatus(string $status): void
    {
        $current = $this->getActiveStatusFilter();

        if ($current === $status) {
            $this->tableFilters['status']['values'] = [];
        } else {
            $this->tableFilters['status']['values'] = [$status];
        }

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }
}
