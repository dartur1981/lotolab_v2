<div class="fi-resource-relation-manager">
    @include('filament.app.components.status-jogos-cards', [
        'stats' => $this->getStatusStats(),
        'activeStatusFilter' => $this->getActiveStatusFilter(),
    ])

    {{ $this->content }}

    <x-filament-panels::unsaved-action-changes-alert />
</div>
