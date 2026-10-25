<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\JobOffer;
use App\Services\PublicApplicationService;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Pages PUBLIQUES de candidature (aucune connexion) : les offres affichées sont celles créées dans l'application
 * et marquées « externe » ; il n'y a rien à saisir deux fois.
 */
class CareersController extends Controller
{
    public function index(string $slug): View
    {
        $company = Company::where('career_slug', $slug)->firstOrFail();

        $offers = JobOffer::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('is_public', true)
            ->where('status', 'open')
            ->where(fn ($q) => $q->whereNull('closes_at')->orWhereDate('closes_at', '>=', today()))
            ->latest('published_at')
            ->get();

        $departments = DB::table('departments')
            ->whereIn('id', $offers->pluck('department_id')->filter()->unique())
            ->pluck('name', 'id');

        return view('careers.index', [
            'company' => $company,
            'offers' => $offers,
            'departments' => $departments,
            'slug' => $slug,
        ]);
    }

    public function show(string $slug, string $reference): View
    {
        $company = Company::where('career_slug', $slug)->firstOrFail();

        $offer = JobOffer::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('reference', $reference)
            ->where('is_public', true)
            ->whereIn('status', ['open', 'closed', 'filled'])
            ->firstOrFail();

        return view('careers.show', [
            'company' => $company,
            'offer' => $offer,
            'open' => PublicApplicationService::isOpen($offer),
            'department' => $offer->department_id ? DB::table('departments')->where('id', $offer->department_id)->value('name') : null,
            'slug' => $slug,
        ]);
    }
}
