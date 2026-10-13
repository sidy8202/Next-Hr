<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $welcomeMessage = null;

        if ($user->first_login_at === null) {
            $company = $user->company;
            $firstName = $user->employee?->first_name ?? $user->name;
            $companyName = $company?->name ?? 'votre entreprise';

            $rawSetting = Setting::where('company_id', $user->company_id)
                ->where('key', 'welcome_message')
                ->value('value');

            $template = $rawSetting ?? 'Bienvenue {PRENOM} chez {ENTREPRISE} ! Nous sommes ravis de vous compter parmi nous.';

            $welcomeMessage = str_replace(
                ['{PRENOM}', '{ENTREPRISE}'],
                [$firstName, $companyName],
                $template
            );
        }

        return view('dashboard', [
            'welcomeMessage' => $welcomeMessage,
        ]);
    }

    public function dismissWelcome(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->first_login_at = now();
        $user->save();

        return back();
    }
}
