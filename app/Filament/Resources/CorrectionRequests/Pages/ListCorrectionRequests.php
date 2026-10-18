<?php

namespace App\Filament\Resources\CorrectionRequests\Pages;

use App\Filament\Resources\CorrectionRequests\CorrectionRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListCorrectionRequests extends ListRecords
{
    protected static string $resource = CorrectionRequestResource::class;
}
