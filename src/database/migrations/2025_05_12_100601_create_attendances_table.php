<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
            Schema::create('attendances', function (Blueprint $table) {
                $table->id(); 
                $table->foreignId('user_id')->constrained()->onDelete('cascade'); 
                $table->date('work_date');
                $table->timestamp('started_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->enum('status', ['off', 'working', 'on_break', 'ended'])->default('off');
                $table->text('note')->nullable();
                $table->enum('approval_status', ['Unsubmitted','pending', 'approved'])->default('Unsubmitted');
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'work_date']); // ✅ ユニーク制約
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendances');
    }
}
