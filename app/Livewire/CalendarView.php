<?php

namespace App\Livewire;

use App\Models\Task;
use App\Models\Report;
use Livewire\Component;

class CalendarView extends Component
{
    /**
     * Render the calendar view.
     */
    public function render()
    {
        // Fetch active tasks with due dates
        $tasks = Task::whereNotNull('due_date')
            ->whereNotIn('status', ['done'])
            ->with('project')
            ->get();

        // Fetch reports with deadlines
        $reports = Report::where(function ($query) {
                $query->whereNotNull('accounts_due_by')
                      ->orWhereNotNull('statements_due_by');
            })
            ->with('project')
            ->get();

        $events = [];

        foreach ($tasks as $task) {
            $projectName = $task->project ? $task->project->name : 'No Project';
            
            // Priority-based coloring
            $color = match ($task->priority) {
                'critical' => '#EF4444', // Red
                'high' => '#F97316',     // Orange
                'medium' => '#0EA5E9',   // Sky
                default => '#64748B',    // Slate
            };

            $events[] = [
                'id' => 'task_' . $task->id,
                'title' => '📝 ' . $task->title . ' (' . $projectName . ')',
                'start' => $task->due_date->format('Y-m-d'),
                'url' => route('tasks.kanban', ['task_id' => $task->id]),
                'color' => $color,
            ];
        }

        foreach ($reports as $report) {
            $projectName = $report->project ? $report->project->name : 'Unknown';
            $url = route('projects.show', $report->project_id) . '?tab=reports';

            if ($report->accounts_due_by) {
                $events[] = [
                    'id' => 'accounts_' . $report->id,
                    'title' => '🏦 Accounts: ' . $projectName,
                    'start' => $report->accounts_due_by->format('Y-m-d'),
                    'url' => $url,
                    'color' => '#8B5CF6', // Purple
                ];
            }

            if ($report->statements_due_by) {
                $events[] = [
                    'id' => 'statements_' . $report->id,
                    'title' => '📄 Statement: ' . $projectName,
                    'start' => $report->statements_due_by->format('Y-m-d'),
                    'url' => $url,
                    'color' => '#EC4899', // Pink
                ];
            }
        }

        return view('livewire.calendar-view', [
            'eventsJson' => json_encode($events),
        ])->layout('layouts.app');
    }
}
