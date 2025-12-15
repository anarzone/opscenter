<?php

use App\Enums\WebhookEventStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_source_id')->constrained('webhook_sources');
            $table->string('event_type');
            $table->json('payload');
            $table->json('headers');
            $table->string('signature')->nullable();
            $table->string('ip_address');
            $table->enum('status', WebhookEventStatus::values());
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('webhook_events');
    }
};
