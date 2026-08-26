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
        // 1. Support Tickets (linked to Gmail thread_id, optional website, project, task)
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('gmail_thread_id')->unique()->index();
            $table->string('customer_email')->index();
            $table->string('recipient_email')->nullable();
            $table->string('subject')->nullable();
            $table->string('status')->default('open'); // open, awaiting_review, closed
            $table->json('categories')->nullable();     // ['refund', 'chargeback', 'complaint']
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('website_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('task_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        // 2. Ticket Messages (desynchronized/deduplicated via gmail_message_id)
        Schema::create('support_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->string('gmail_message_id')->unique();
            $table->string('from');
            $table->string('to')->nullable();
            $table->longText('body_text')->nullable();
            $table->timestamp('sent_at');
            $table->timestamps();
        });

        // 3. Ticket Attachments
        Schema::create('support_ticket_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('support_ticket_message_id')->constrained()->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('storage_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_ticket_attachments');
        Schema::dropIfExists('support_ticket_messages');
        Schema::dropIfExists('support_tickets');
    }
};
