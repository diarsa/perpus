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
        Schema::table('borrowings', function (Blueprint $row) {
            $row->text('rejection_reason')->nullable()->after('status');
            // Status was already present as string but now I'll use it to handle flow:
            // pending, borrowed, rejected, returned
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('borrowings', function (Blueprint $row) {
            $row->dropColumn('rejection_reason');
        });
    }
};
