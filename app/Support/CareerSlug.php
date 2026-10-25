<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CareerSlug
{
    /** Adresse publique d'une entreprise ; créée à la volée pour une entreprise qui n'en a pas encore. */
    public static function ensure(int $companyId): string
    {
        $row = DB::table('companies')->where('id', $companyId)->first(['name', 'career_slug']);

        if (! $row) {
            abort(404);
        }
        if ($row->career_slug) {
            return $row->career_slug;
        }

        $base = Str::slug((string) $row->name) ?: 'entreprise';
        $slug = $base;
        $i = 2;

        while (DB::table('companies')->where('career_slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        DB::table('companies')->where('id', $companyId)->update(['career_slug' => $slug]);

        return $slug;
    }
}
