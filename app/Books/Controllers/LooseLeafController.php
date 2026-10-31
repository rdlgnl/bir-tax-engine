<?php

namespace App\Books\Controllers;

use App\Books\Enums\BooksType;
use App\Http\Controllers\Controller;
use App\Profiles\Models\TaxProfile;
use App\Returns\Forms\Items\Form1900Items;
use App\Returns\Forms\OfficialFormFiller;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Registering loose-leaf books, so the journals can be printed from the
 * app instead of copied into stamped books by hand.
 */
class LooseLeafController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('books/loose-leaf', [
            'booksType' => (TaxProfile::current()->books_type ?? BooksType::Manual)->value,
            'books' => Form1900Items::BOOKS,
            'bindWithinDays' => config('tax.books.bind_within_days'),
            'retentionYears' => config('tax.books.retention_years'),
            'edition' => config('tax.forms.1900.edition'),
        ]);
    }

    /**
     * BIR Form 1900, the application for a Permit to Use, filled from the profile.
     */
    public function application(OfficialFormFiller $filler, Form1900Items $items): HttpResponse
    {
        return response($filler->render('1900', $items->looseLeafBooks(TaxProfile::current())), headers: [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename=1900-loose-leaf-books.pdf',
        ]);
    }
}
