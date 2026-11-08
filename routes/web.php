<?php

use App\Http\Controllers\ApplicationDocumentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\CandidateCvController;
use App\Http\Controllers\CareersController;
use App\Http\Controllers\CompanyInvoiceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Http\Controllers\ExplanationFileController;
use App\Http\Controllers\LeaveSheetController;
use App\Http\Controllers\MissionFileController;
use App\Http\Controllers\MissionPaymentFileController;
use App\Http\Controllers\PermissionFileController;
use App\Http\Controllers\PlatformInvoiceController;
use App\Http\Middleware\EnsurePlatformAdmin;
use App\Http\Middleware\EnsurePlatformPermission;
use App\Livewire\Access\AccessMatrix;
use App\Livewire\Assignments\AssignmentIndex;
use App\Livewire\Departments\DepartmentForm;
use App\Livewire\Departments\DepartmentIndex;
use App\Livewire\Departments\DepartmentShow;
use App\Livewire\Discipline\ExplanationForm;
use App\Livewire\Discipline\ExplanationIndex;
use App\Livewire\Discipline\ExplanationShow;
use App\Livewire\Discipline\LetterheadPage;
use App\Livewire\Employees\EmployeeBatch;
use App\Livewire\Employees\EmployeeForm;
use App\Livewire\Employees\EmployeeIndex;
use App\Livewire\Employees\EmployeeShow;
use App\Livewire\Leaves\AbsenceIndex;
use App\Livewire\Leaves\Balances;
use App\Livewire\Leaves\Inbox;
use App\Livewire\Leaves\LeaveForm;
use App\Livewire\Leaves\LeaveIndex;
use App\Livewire\Leaves\LeaveSettingsPage;
use App\Livewire\Leaves\LeaveShow;
use App\Livewire\Leaves\MyLeaves;
use App\Livewire\Leaves\OvertimeIndex;
use App\Livewire\Missions\MissionForm;
use App\Livewire\Missions\MissionIndex;
use App\Livewire\Missions\MissionPaymentIndex;
use App\Livewire\Missions\MissionShow;
use App\Livewire\Notifications\NotificationIndex;
use App\Livewire\Payroll\EmployeeLoans;
use App\Livewire\Payroll\EmployeePayItems;
use App\Livewire\Payroll\EmployeeSalaries;
use App\Livewire\Payroll\MonthlyVariables;
use App\Livewire\Payroll\PayAssignments;
use App\Livewire\Payroll\PayItems;
use App\Livewire\Payroll\PayProfiles;
use App\Livewire\Payroll\PayrollSimulation;
use App\Livewire\Payroll\PayslipTemplates;
use App\Livewire\Payroll\SalaryGrids;
use App\Livewire\Payroll\Settings;
use App\Livewire\Payroll\VariableSettings;
use App\Livewire\Permissions\PermissionForm;
use App\Livewire\Permissions\PermissionIndex;
use App\Livewire\Permissions\PermissionShow;
use App\Livewire\Permissions\PermissionTypeIndex;
use App\Livewire\Platform\AuditIndex;
use App\Livewire\Platform\BillingSettings;
use App\Livewire\Platform\CompanyForm;
use App\Livewire\Platform\CompanyShow;
use App\Livewire\Platform\PlanMatrix;
use App\Livewire\Platform\Reports;
use App\Livewire\Platform\StaffIndex;
use App\Livewire\Positions\PositionForm;
use App\Livewire\Positions\PositionIndex;
use App\Livewire\Recruitment\CandidateForm;
use App\Livewire\Recruitment\CandidateIndex;
use App\Livewire\Recruitment\ConvertCandidate;
use App\Livewire\Recruitment\InterviewAgenda;
use App\Livewire\Recruitment\JobOfferForm;
use App\Livewire\Recruitment\JobOfferIndex;
use App\Livewire\Recruitment\JobOfferShow;
use App\Livewire\Reports\HrReports;
use App\Livewire\Users\UserIndex;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

    // Pages PUBLIQUES de candidature (sans connexion) : /carrieres/nom-entreprise et /carrieres/nom-entreprise/OFF-2026-001
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/carrieres/{slug}', [CareersController::class, 'index'])->name('careers.company');
        Route::get('/carrieres/{slug}/{reference}', [CareersController::class, 'show'])->name('careers.offer');
    });

    // Invités (non authentifiés)
    Route::middleware('guest')->group(function () {
        Route::get('/login', [LoginController::class, 'create'])->name('login');
        Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login');
    });

    // Authentifiés ET « prêts » (compte actif, mot de passe à jour)
    Route::middleware(['auth', 'ready'])->group(function () {
        Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

        Route::get('/password/change', [PasswordController::class, 'edit'])->name('password.change');
        Route::put('/password/change', [PasswordController::class, 'update'])->name('password.update');


        //place de lancienne route
        Route::redirect('/platform', '/plateforme');

            Route::prefix('plateforme')->middleware(EnsurePlatformAdmin::class)->group(function () {
            $can = fn (string $p) => EnsurePlatformPermission::class.':'.$p;
            Route::livewire('/rapports', Reports::class)->middleware($can('reports.view'))->name('platform.reports');

            Route::livewire('/', \App\Livewire\Platform\PlatformDashboard::class)->middleware($can('dashboard.view'))->name('platform');
            Route::livewire('/entreprises', \App\Livewire\Platform\CompanyIndex::class)->middleware($can('companies.view'))->name('platform.companies');
            Route::livewire('/entreprises/nouvelle', \App\Livewire\Platform\CompanyForm::class)->middleware($can('companies.manage'))->name('platform.companies.create');   // AVANT {company}
            Route::livewire('/entreprises/{company}', CompanyShow::class)->middleware($can('companies.view'))->name('platform.companies.show');
            Route::livewire('/entreprises/{company}/modifier', CompanyForm::class)->middleware($can('companies.manage'))->name('platform.companies.edit');
            Route::livewire('/formules', PlanMatrix::class)->middleware($can('plans.manage'))->name('platform.plans');
            Route::livewire('/equipe', StaffIndex::class)->middleware($can('staff.manage'))->name('platform.staff');
            Route::livewire('/audit', AuditIndex::class)->middleware($can('audit.view'))->name('platform.audit');
            Route::livewire('/facturation', BillingSettings::class)->middleware($can('plans.manage'))->name('platform.billing');
            Route::get('/factures/{payment}', [PlatformInvoiceController::class, 'show'])->middleware($can('subscriptions.view'))->name('platform.invoice');
            Route::livewire('/regles-paie', \App\Livewire\Platform\PayrollRules::class)->middleware($can('plans.manage'))->name('platform.payroll-rules');
        });

        // Utilisateurs rattachés à une entreprise cliente
        Route::middleware('company')->group(function () {
            Route::livewire('/dashboard', \App\Livewire\Dashboard\CompanyDashboard::class)->name('dashboard');
            Route::post('/welcome/dismiss', [DashboardController::class, 'dismissWelcome'])->name('welcome.dismiss');

            // ATTENTION à l'ordre : les adresses fixes (/nouveau…) AVANT les adresses à paramètre ({id}).
            //Gestion des abonnement cotés compagnie
            Route::livewire('/mon-abonnement', \App\Livewire\Subscription\MySubscription::class)->name('subscription.mine');
            Route::get('/mon-abonnement/factures/{payment}', [CompanyInvoiceController::class, 'show'])->name('subscription.invoice');

            // Départements
            Route::livewire('/departements', DepartmentIndex::class)->name('departments.index');
            Route::livewire('/departements/nouveau', DepartmentForm::class)->name('departments.create');
            Route::livewire('/departements/{department}', DepartmentShow::class)->name('departments.show');
            Route::livewire('/departements/{department}/modifier', DepartmentForm::class)->name('departments.edit');

            // Postes
            Route::livewire('/postes', PositionIndex::class)->name('positions.index');
            Route::livewire('/postes/nouveau', PositionForm::class)->name('positions.create');
            Route::livewire('/postes/{position}/modifier', PositionForm::class)->name('positions.edit');

            // Employés
            Route::livewire('/employes', EmployeeIndex::class)->name('employees.index');
            Route::livewire('/employes/nouveau', EmployeeForm::class)->name('employees.create');
            Route::livewire('/employes/nouvelles-recrues', EmployeeBatch::class)->name('employees.batch');
            Route::livewire('/employes/{employee}', EmployeeShow::class)->name('employees.show');
            Route::livewire('/employes/{employee}/modifier', EmployeeForm::class)->name('employees.edit');
            Route::get('/employes/{employee}/documents/{document}', [EmployeeDocumentController::class, 'download'])->name('employees.documents.download');
            Route::get('/employes/{employee}/documents/{document}/voir', [EmployeeDocumentController::class, 'view'])->name('employees.documents.view');

            // Administration
            Route::livewire('/utilisateurs', UserIndex::class)->name('users.index');
            Route::livewire('/droits', AccessMatrix::class)->name('access.matrix');

            // Notifications
            Route::livewire('/notifications', NotificationIndex::class)->name('notifications.index');


            // Congés, absences, jours travaillés (adresses fixes AVANT /conges/{leave})
            Route::livewire('/conges', MyLeaves::class)->name('leaves.mine');
            Route::livewire('/conges/nouvelle', LeaveForm::class)->name('leaves.create');
            Route::livewire('/conges/a-valider', Inbox::class)->name('leaves.inbox');
            Route::livewire('/conges/toutes', LeaveIndex::class)->name('leaves.index');
            Route::livewire('/conges/soldes', Balances::class)->name('leaves.balances');
            Route::livewire('/conges/absences', AbsenceIndex::class)->name('leaves.absences');
            Route::livewire('/conges/jours-travailles', OvertimeIndex::class)->name('leaves.overtime');
            Route::livewire('/conges/parametres', LeaveSettingsPage::class)->name('leaves.settings');
            Route::livewire('/conges/{leave}', LeaveShow::class)->name('leaves.show');
            Route::get('/conges/{leave}/fiche', [LeaveSheetController::class, 'sheet'])->name('leaves.sheet');
            Route::get('/conges/{leave}/fiche/voir', [LeaveSheetController::class, 'sheetView'])->name('leaves.sheet.view');
            Route::get('/conges/{leave}/justificatif', [LeaveSheetController::class, 'attachment'])->name('leaves.attachment');
            Route::get('/conges/{leave}/justificatif/voir', [LeaveSheetController::class, 'attachmentView'])->name('leaves.attachment.view');

            // Permissions d'absence (adresses fixes AVANT /permissions/{permission})
            Route::livewire('/permissions', PermissionIndex::class)->name('permissions.index');
            Route::livewire('/permissions/nouvelle', PermissionForm::class)->name('permissions.create');
            Route::livewire('/permissions/types', PermissionTypeIndex::class)->name('permissions.types');
            Route::livewire('/permissions/{permission}', PermissionShow::class)->name('permissions.show');
            Route::get('/permissions/{permission}/fiche', [PermissionFileController::class, 'sheet'])->name('permissions.sheet');
            Route::get('/permissions/{permission}/fiche/voir', [PermissionFileController::class, 'sheetView'])->name('permissions.sheet.view');
            Route::get('/permissions/{permission}/justificatif', [PermissionFileController::class, 'attachment'])->name('permissions.attachment');
            Route::get('/permissions/{permission}/justificatif/voir', [PermissionFileController::class, 'attachmentView'])->name('permissions.attachment.view');

            // Demandes d'explication (adresses fixes AVANT /explications/{case}) et papier à en-tête
            Route::livewire('/explications', ExplanationIndex::class)->name('explanations.index');
            Route::livewire('/explications/nouvelle', ExplanationForm::class)->name('explanations.create');
            Route::livewire('/explications/{case}', ExplanationShow::class)->name('explanations.show');
            Route::get('/explications/{case}/lettres/{document}', [ExplanationFileController::class, 'letter'])->name('explanations.letter');
            Route::get('/explications/{case}/lettres/{document}/voir', [ExplanationFileController::class, 'letterView'])->name('explanations.letter.view');
            Route::get('/explications/{case}/piece-jointe', [ExplanationFileController::class, 'attachment'])->name('explanations.attachment');
            Route::get('/explications/{case}/piece-jointe/voir', [ExplanationFileController::class, 'attachmentView'])->name('explanations.attachment.view');
            Route::livewire('/parametres/entete', LetterheadPage::class)->name('letterhead.edit');

            //

            //Ordres de Mission

            Route::get('/missions', MissionIndex::class)->name('missions.index');
            Route::get('/missions/nouvelle', MissionForm::class)->name('missions.create');   // AVANT {mission}
            Route::livewire('/missions/paiements', MissionPaymentIndex::class)->name('missions.payments');   // AVANT /missions/{mission}
            Route::get('/missions/{mission}/modifier', MissionForm::class)->name('missions.edit');
            Route::get('/missions/{mission}', MissionShow::class)->name('missions.show');

            Route::get('/missions/{mission}/documents/{document}', [MissionFileController::class, 'download'])->name('missions.document');
            Route::get('/missions/{mission}/documents/{document}/voir', [MissionFileController::class, 'view'])->name('missions.document.view');

            // Après les routes missions.document* :
            Route::get('/missions/{mission}/participants/{participant}/fiche-perdiem', [MissionPaymentFileController::class, 'sheet'])->name('missions.perdiem-sheet');
            Route::get('/missions/{mission}/paiements/{payment}/recu', [MissionPaymentFileController::class, 'receipt'])->name('missions.payment.receipt');
            Route::get('/missions/{mission}/paiements/{payment}/recu/voir', [MissionPaymentFileController::class, 'receiptView'])->name('missions.payment.receipt.view');


            // Affectations et intérims
            Route::livewire('/affectations', AssignmentIndex::class)->name('assignments.index');

            // Recrutement
            Route::get('/recrutement/candidats', CandidateIndex::class)->name('candidates.index');
            Route::get('/recrutement/candidats/nouveau', CandidateForm::class)->name('candidates.create');
            Route::get('/recrutement/candidats/{candidate}/modifier', CandidateForm::class)->name('candidates.edit');
            Route::get('/recrutement/candidats/{candidate}/cv', CandidateCvController::class)->name('candidates.cv');
            Route::get('/recrutement/candidats/{candidate}/cv/voir', [CandidateCvController::class, 'view'])->name('candidates.cv.view');

            Route::get('/recrutement/offres', JobOfferIndex::class)->name('job-offers.index');
            Route::get('/recrutement/offres/nouvelle', JobOfferForm::class)->name('job-offers.create');
            Route::get('/recrutement/offres/{jobOffer}', JobOfferShow::class)->name('job-offers.show');
            Route::get('/recrutement/offres/{jobOffer}/modifier', JobOfferForm::class)->name('job-offers.edit');

            Route::get('/recrutement/entretiens', InterviewAgenda::class)->name('interviews.index');
            Route::get('/recrutement/candidatures/{application}/embaucher', ConvertCandidate::class)->name('applications.convert');
            Route::get('/recrutement/candidatures/{application}/lettre', [ApplicationDocumentController::class, 'download'])->name('applications.cover.download');
            Route::get('/recrutement/candidatures/{application}/lettre/voir', [ApplicationDocumentController::class, 'view'])->name('applications.cover.view');

            Route::livewire('/paie/parametres', Settings::class)->name('payroll.settings');
            Route::livewire('/paie/grilles', SalaryGrids::class)->name('payroll.grids');
            Route::livewire('/paie/salaires', EmployeeSalaries::class)->name('payroll.salaries');
            Route::livewire('/paie/salaires', \App\Livewire\Payroll\EmployeeSalaries::class)->name('payroll.salaries');
            Route::livewire('/paie/rubriques', PayItems::class)->name('payroll.items');
            Route::livewire('/paie/modeles', PayProfiles::class)->name('payroll.profiles');
            Route::livewire('/paie/affectations', PayAssignments::class)->name('payroll.assignments');
            Route::livewire('/paie/primes', EmployeePayItems::class)->name('payroll.bonuses');
            Route::livewire('/paie/modeles-bulletin', \App\Livewire\Payroll\PayslipTemplates::class)->name('payroll.templates');
            Route::livewire('/paie/variables', MonthlyVariables::class)->name('payroll.variables');
            Route::livewire('/paie/prets', EmployeeLoans::class)->name('payroll.loans');
            Route::livewire('/paie/reglages-variables', VariableSettings::class)->name('payroll.variable-settings');
            Route::livewire('/paie/simulation', PayrollSimulation::class)->name('payroll.simulation');

            Route::livewire('/rapports-rh', HrReports::class)->name('reports.hr');
        });
});
