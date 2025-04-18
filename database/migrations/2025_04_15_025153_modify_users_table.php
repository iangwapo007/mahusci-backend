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
        Schema::table('users', function (Blueprint $table) {
            $table->string('username');
            $table->renameColumn('name', 'firstname');
            $table->string('middlename')->nullable();
            $table->string('lastname');
            $table->integer('age');
            $table->string('grade_level');
            $table->string('school_name');
            $table->string('section');
            $table->string('address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
