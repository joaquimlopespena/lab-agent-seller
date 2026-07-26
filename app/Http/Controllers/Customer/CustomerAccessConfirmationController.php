<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class CustomerAccessConfirmationController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('customer/access/confirmation');
    }
}
