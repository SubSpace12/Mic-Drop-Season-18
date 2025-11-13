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
        // Add Judge_App field to existing users table
        Schema::table('users', function (Blueprint $table) {
            $table->text('judge_app')->nullable();
        });

        // Create awards table
        Schema::create('awards', function (Blueprint $table) {
            $table->smallIncrements('award_id');
            $table->text('name')->unique();
            $table->text('description');
            $table->text('role_id')->unique();
            $table->text('role_color')->nullable();
            $table->text('award_icon');
        });

        // Create season table
        Schema::create('season', function (Blueprint $table) {
            $table->smallIncrements('season_id');
            $table->text('name');
            $table->boolean('active');
        });

        // Create round table
        Schema::create('round', function (Blueprint $table) {
            $table->smallInteger('season_id');
            $table->smallInteger('round_number');
            $table->text('title');
            $table->text('description');
            $table->text('mode')->default('default');
            $table->smallInteger('eliminate_number');
            $table->boolean('is_leave')->default(false);
            $table->date('deadline');
            
            $table->primary(['season_id', 'round_number']);
            $table->foreign('season_id')->references('season_id')->on('season');
        });

        // Create contestants table
        Schema::create('contestants', function (Blueprint $table) {
            $table->integer('id');
            $table->smallInteger('season_id');
            $table->boolean('eliminated')->default(false);
            $table->smallInteger('md_group');
            $table->smallInteger('extension_hours')->default(0);
            $table->boolean('special')->default(false);
            
            $table->primary(['id', 'season_id']);
            $table->foreign('id')->references('id')->on('users');
            $table->foreign('season_id')->references('season_id')->on('season');
        });

        // Create judges table
        Schema::create('judges', function (Blueprint $table) {
            $table->integer('id');
            $table->smallInteger('season_id');
            $table->smallInteger('round');
            $table->smallInteger('md_group');
            
            $table->primary(['id', 'season_id', 'round']);
            $table->foreign('id')->references('id')->on('users');
            $table->foreign(['season_id', 'round'])->references(['season_id', 'round_number'])->on('round');
        });

        // Create staff table
        Schema::create('staff', function (Blueprint $table) {
            $table->integer('id');
            $table->smallInteger('season_id');
            $table->boolean('host')->default(false);
            
            $table->primary(['id', 'season_id']);
            $table->foreign('id')->references('id')->on('users');
            $table->foreign('season_id')->references('season_id')->on('season');
        });

        // Create submissions table
        Schema::create('submissions', function (Blueprint $table) {
            $table->bigIncrements('submission_id');
            $table->integer('id');
            $table->smallInteger('season_id');
            $table->integer('judge_id');
            $table->smallInteger('round');
            $table->smallInteger('md_group');
            $table->text('artist');
            $table->text('title');
            $table->text('url');
            $table->integer('status')->default(0);
            $table->double('score')->nullable();
            $table->text('review')->nullable();
            $table->boolean('override')->default(false);
            $table->text('special')->nullable();
            
            $table->foreign('id')->references('id')->on('users');
            $table->foreign('judge_id')->references('id')->on('users');
            $table->foreign(['season_id', 'round'])->references(['season_id', 'round_number'])->on('round');
        });

        // Create blacklist table
        Schema::create('blacklist', function (Blueprint $table) {
            $table->integer('bad')->primary();
            $table->foreign('bad')->references('id')->on('users');
        });

        // Create player_artists table
        Schema::create('player_artists', function (Blueprint $table) {
            $table->integer('id');
            $table->text('artist');
            $table->boolean('favorite')->default(true);
            $table->boolean('banned')->default(false);
            
            $table->primary(['id', 'artist']);
            $table->foreign('id')->references('id')->on('users');
        });

        // Create player_awards table
        Schema::create('player_awards', function (Blueprint $table) {
            $table->integer('id');
            $table->smallInteger('award_id');
            
            $table->primary(['id', 'award_id']);
            $table->foreign('id')->references('id')->on('users');
            $table->foreign('award_id')->references('award_id')->on('awards');
        });

        // Create player_genres table
        Schema::create('player_genres', function (Blueprint $table) {
            $table->integer('id');
            $table->text('genre');
            $table->boolean('favorite')->default(true);
            
            $table->primary(['id', 'genre']);
            $table->foreign('id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_genres');
        Schema::dropIfExists('player_awards');
        Schema::dropIfExists('player_artists');
        Schema::dropIfExists('blacklist');
        Schema::dropIfExists('submissions');
        Schema::dropIfExists('staff');
        Schema::dropIfExists('judges');
        Schema::dropIfExists('contestants');
        Schema::dropIfExists('round');
        Schema::dropIfExists('season');
        Schema::dropIfExists('awards');
        
        // Remove Judge_App field from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('judge_app');
        });
    }
};