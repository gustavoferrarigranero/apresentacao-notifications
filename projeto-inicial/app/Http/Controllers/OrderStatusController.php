<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

final class OrderStatusController extends Controller
{
    public function __invoke(): Response
    {
        return response('Implemente o fluxo durante a aula.', 501);
    }
}
