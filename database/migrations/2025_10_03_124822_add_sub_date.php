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
        Schema::table('contestants', function (Blueprint $table) {
            $table->dateTime('submission_date')->nullable()->after('eliminated');
        });
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn('sub_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contestants', function (Blueprint $table) {
            $table->dropColumn('submission_date');
        });
        Schema::table('submissions', function (Blueprint $table) {
            $table->integer('sub_order')->nullable();
        });
    }
};
