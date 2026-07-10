<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Barryvdh\DomPDF\Facade\Pdf;

class ProjectPdfController extends Controller
{
    /**
     * Export the project compliance details as a PDF.
     */
    public function export(Project $project)
    {
        // Load relationships
        $project->load(['director.manager', 'manager', 'websites', 'boarding', 'report']);

        $pdf = Pdf::loadView('pdf.project-pdf', compact('project'));

        return $pdf->download(str_replace(' ', '_', $project->name) . '_compliance_report.pdf');
    }
}
