<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $t) {
            $t->string('career_slug', 80)->nullable()->unique();
        });

        // Adresse publique des entreprises existantes : /carrieres/nom-de-l-entreprise
        foreach (DB::table('companies')->orderBy('id')->get(['id', 'name']) as $company) {
            $base = Str::slug((string) $company->name) ?: 'entreprise';
            $slug = $base;
            $i = 2;

            while (DB::table('companies')->where('career_slug', $slug)->exists()) {
                $slug = $base.'-'.$i++;
            }

            DB::table('companies')->where('id', $company->id)->update(['career_slug' => $slug]);
        }

        Schema::table('job_offers', function (Blueprint $t) {
            $t->boolean('is_public')->default(false)->after('status');
            $t->json('required_skills')->nullable()->after('requirements');
        });

        Schema::table('candidates', function (Blueprint $t) {
            // Le candidat accepte-t-il que son CV serve à d'autres offres ? (profils existants : oui)
            $t->boolean('bank_consent')->default(true)->after('retention_until');
        });

        Schema::table('applications', function (Blueprint $t) {
            $t->unsignedTinyInteger('match_score')->nullable()->after('rating');
            $t->json('match_details')->nullable()->after('match_score');
            $t->string('channel', 12)->default('internal')->after('match_details'); // internal | portal | email
            $t->string('cover_letter_path')->nullable()->after('channel');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $t) {
            $t->dropColumn(['match_score', 'match_details', 'channel', 'cover_letter_path']);
        });
        Schema::table('candidates', function (Blueprint $t) {
            $t->dropColumn('bank_consent');
        });
        Schema::table('job_offers', function (Blueprint $t) {
            $t->dropColumn(['is_public', 'required_skills']);
        });
        Schema::table('companies', function (Blueprint $t) {
            $t->dropUnique(['career_slug']);
            $t->dropColumn('career_slug');
        });
    }
};
