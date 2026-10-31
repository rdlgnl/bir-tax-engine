<?php

namespace App\Notices\Controllers;

use App\Http\Controllers\Controller;
use App\Notices\Services\Notices;
use App\Profiles\Models\TaxProfile;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class NoticeController extends Controller
{
    public function __construct(private Notices $notices) {}

    public function index(): Response
    {
        return Inertia::render('notifications', $this->notices->feed(TaxProfile::current(), CarbonImmutable::now()));
    }

    public function read(): RedirectResponse
    {
        $this->notices->markAllRead(TaxProfile::current(), CarbonImmutable::now());

        return back();
    }
}
