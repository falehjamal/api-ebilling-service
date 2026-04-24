<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('warga_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account');
            $table->unsignedInteger('id_warga_legacy');
            $table->string('username');
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table->unique(['account', 'id_warga_legacy']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warga_accounts');
    }
};
