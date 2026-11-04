<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 60);
            $table->string('description')->nullable();
            $table->string('color', 9)->default('#6366f1');
            $table->unsignedInteger('max_employees')->nullable();          // null = illimité
            $table->unsignedSmallInteger('storage_gb')->default(2);
            $table->unsignedSmallInteger('audit_retention_months')->default(3);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('plan_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('months');                          // 3, 6 ou 12
            $table->unsignedBigInteger('price')->nullable();                // prix de la période, en devise de l'entreprise
            $table->timestamps();
            $table->unique(['plan_id', 'months']);
        });

        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('feature', 40);
            $table->boolean('enabled')->default(false);
            $table->timestamps();
            $table->unique(['plan_id', 'feature']);
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained();
            $table->unsignedTinyInteger('period_months');
            $table->string('status', 12)->default('pending');               // trial | pending | active | past_due | suspended | expired | cancelled | replaced
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->date('trial_ends_at')->nullable();
            $table->unsignedBigInteger('list_price')->default(0);           // prix catalogue au moment de la souscription (figé)
            $table->unsignedTinyInteger('discount_percent')->default(0);
            $table->unsignedBigInteger('price')->default(0);                // prix net de la période (figé)
            $table->char('currency', 3)->default('XOF');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['company_id', 'status']);
        });

        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_number', 20)->unique();
            $table->unsignedBigInteger('amount');
            $table->date('paid_at');
            $table->string('method', 20);
            $table->string('reference', 80)->nullable();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('paid_at');
        });

        Schema::create('platform_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('action', 40)->index();
            $table->string('summary');
            $table->json('meta')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_audit_logs');
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('plan_prices');
        Schema::dropIfExists('plans');
    }
};
