<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('pci_dss_documents')
            ->where('document_type', 'Scan')
            ->update(['document_type' => 'ASV']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('pci_dss_documents')
            ->where('document_type', 'ASV')
            ->update(['document_type' => 'Scan']);
    }
};
