<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->string('firstname');
            $table->string('middlename')->nullable();
            $table->text('profile_picture')->nullable();
            $table->string('lastname');
            $table->integer('age');
            $table->date('birthdate');
            $table->string('school_name');
            $table->string('email')->unique();
            $table->string('address');
            $table->string('username')->unique();
            $table->string('password');
            $table->string('role')->default('Teacher');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('teachers');
    }
};