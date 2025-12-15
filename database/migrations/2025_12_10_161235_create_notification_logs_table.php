<?php

use App\Enums\NotificationLogStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_id')->constrained('alerts');
            $table->string('channel');
            $table->string('recipient');
            $table->enum('status', NotificationLogStatus::values());
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('notification_logs');
    }
};
