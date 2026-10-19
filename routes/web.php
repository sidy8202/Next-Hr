<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\CandidateCvController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Livewire\Access\AccessMatrix;
use App\Livewire\Departments\DepartmentForm;
use App\Livewire\Departments\DepartmentIndex;
use App\Livewire\Departments\DepartmentShow;
use App\Livewire\Employees\EmployeeBatch;
use App\Livewire\Employees\EmployeeForm;
use App\Livewire\Employees\EmployeeIndex;
use App\Livewire\Employees\EmployeeShow;
use App\Livewire\Notifications\NotificationIndex;
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
        Route::get('/employes/{employee}/documents/{document}/voir', [EmployeeDocumentController::class, 'show'])->name('employees.documents.show');
        Route::get('/employes/{employee}/documents/{document}', [EmployeeDocumentController::class, 'download'])->name('employees.documents.download');

        // Administration
        Route::livewire('/utilisateurs', UserIndex::class)->name('users.index');
        Route::livewire('/droits', AccessMatrix::class)->name('access.matrix');

        // Notifications
        Route::livewire('/notifications', NotificationIndex::class)->name('notifications.index');

        Route::get('/recrutement/candidats', CandidateIndex::class)->name('candidates.index');
        Route::get('/recrutement/candidats/nouveau', CandidateForm::class)->name('candidates.create');
        Route::get('/recrutement/candidats/{candidate}/modifier', CandidateForm::class)->name('candidates.edit');
        Route::get('/recrutement/candidats/{candidate}/cv', CandidateCvController::class)->name('candidates.cv');

        Route::get('/recrutement/offres', JobOfferIndex::class)->name('job-offers.index');
        Route::get('/recrutement/offres/nouvelle', JobOfferForm::class)->name('job-offers.create');
        Route::get('/recrutement/offres/{jobOffer}', JobOfferShow::class)->name('job-offers.show');
        Route::get('/recrutement/offres/{jobOffer}/modifier', JobOfferForm::class)->name('job-offers.edit');

        // 2) Dans le même groupe de routes authentifiées que le recrutement (lot 8) :
        Route::get('/recrutement/entretiens', InterviewAgenda::class)->name('interviews.index');
        Route::get('/recrutement/candidatures/{application}/embaucher', ConvertCandidate::class)->name('applications.convert');

    });
});
