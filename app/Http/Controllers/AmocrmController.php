<?php

namespace App\Http\Controllers;

use App\Services\AmocrmService;

class AmocrmController
{
    public function getToken(AmocrmService $service)
    {
        $service->getToken();
    }
}
