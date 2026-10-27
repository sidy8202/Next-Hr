<?php

use App\Http\Controllers\ApplicationDocumentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\CandidateCvController;
use App\Http\Controllers\CareersController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Http\Controllers\ExplanationFileController;
use App\Http\Controllers\LeaveSheetController;
use App\Http\Controllers\PermissionFileController;
use App\Livewire\Access\AccessMatrix;
use App\Livewire\Departments\DepartmentForm;
use App\Livewire\Departments\DepartmentIndex;
use App\Livewire\Departments\DepartmentShow;
use App\Livewire\Assignments\AssignmentIndex;
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
use App\Livewire\Notifications\NotificationIndex;
use App\Livewire\Permissions\PermissionForm;
use App\Livewire\Permissions\PermissionIndex;
use App\Livewire\Permissions\PermissionShow;
use App\Livewire\Permissions\PermissionTypeIndex;
use App\Livewire\Positions\PositionForm;
use App\Livewire\Positions\PositionIndex;
use App\Livewire\Recruitment\CandidateForm;
use App\Livewire\Recruitment\CandidateIndex;
use App\Livewire\Recruitment\ConvertCandidate;
use App\Livewire\Recruitment\InterviewAgenda;
use App\Livewire\Recruitment\JobOfferForm;
use App\Livewire\Recruitment\JobOfferIndex;
use App\Livewire\Recruitment\JobOfferShow;
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

    Route::get('/platform', fn () => 'Espace plateforme')->name('platform');

    // Utilisateurs rattachés à une entreprise cliente
    Route::middleware('company')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/welcome/dismiss', [DashboardController::class, 'dismissWelcome'])->name('welcome.dismiss');

        // ATTENTION à l'ordre : les adresses fixes (/nouveau…) AVANT les adresses à paramètre ({id}).

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
    });
});
