<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->bigInteger('user_id')->change();
        });
    }

    public function down()
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->integer('user_id')->change();
        });
    }
};