<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('nombre', 60);
            $table->enum('grupo', ['fud', 'aprobador']);
            $table->tinyInteger('nivel')->unsigned()->default(1);
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });

        Schema::create('zonas', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('nombre', 80);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('tiendas', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->unsignedSmallInteger('zona_id');
            $table->string('nombre', 100);
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('zona_id')->references('id')->on('zonas');
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('email', 180)->unique();
            $table->string('password');
            $table->unsignedTinyInteger('rol_id');
            $table->unsignedSmallInteger('tienda_id')->nullable();
            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
            $table->foreign('rol_id')->references('id')->on('roles');
            $table->foreign('tienda_id')->references('id')->on('tiendas');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('tiendas');
        Schema::dropIfExists('zonas');
        Schema::dropIfExists('roles');
    }
};