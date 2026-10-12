<?php

namespace App\Services;

class HomeSummaryIcons
{
    /** @var array<string, array{label: string, path: string}> */
    public const OPTIONS = [
        'archive' => ['label' => 'Arxiu', 'path' => 'seed/summary-organization.svg'],
        'search' => ['label' => 'Recerca', 'path' => 'seed/summary-research.svg'],
        'scan' => ['label' => 'Digitalització', 'path' => 'seed/summary-digitization.svg'],
        'book-open' => ['label' => 'Llibre obert', 'path' => 'seed/service-advice.svg'],
    ];
}
