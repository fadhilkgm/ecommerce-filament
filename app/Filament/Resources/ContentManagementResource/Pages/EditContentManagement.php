<?php

namespace App\Filament\Resources\ContentManagementResource\Pages;

use App\Filament\Resources\ContentManagementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContentManagement extends EditRecord
{
    protected static string $resource = ContentManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
