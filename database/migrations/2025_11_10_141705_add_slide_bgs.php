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
        Schema::table('round', function (Blueprint $table) {
            $table->string('slidebg_first')->nullable();
            $table->string('slidebg_second')->nullable();
            $table->string('slidebg_third')->nullable();
            $table->string('slidebg_normal')->nullable();
            $table->string('slidebg_elim')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('round', function (Blueprint $table) {
            $table->dropColumn('slidebg_first');
            $table->dropColumn('slidebg_second');
            $table->dropColumn('slidebg_third');
            $table->dropColumn('slidebg_normal');
            $table->dropColumn('slidebg_elim');
        });
    }
};
