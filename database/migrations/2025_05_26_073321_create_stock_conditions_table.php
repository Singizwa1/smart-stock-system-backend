<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('stock_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('bean_type', 255);
            $table->integer('quantity');
            $table->double('temperature');
            $table->double('humidity');
            $table->string('status')->comment('Good, Warning, Critical');
            $table->string('location', 255);
            $table->string('air_condition', 255);
            $table->text('action_taken')->nullable();
            $table->timestamp('last_updated')->useCurrent();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_conditions');
    }
};
