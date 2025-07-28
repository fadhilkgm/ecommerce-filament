<?php

namespace App\Filament\Resources\MasterDataResource\Pages;

use App\Filament\Resources\MasterDataResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMasterData extends ViewRecord
{
    protected static string $resource = MasterDataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}