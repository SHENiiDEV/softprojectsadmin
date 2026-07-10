@component('mail::message')
# New Task Assigned 📝

Hello **{{ $assignee->name }}**,

A new task has been assigned to you by **{{ $actor ? $actor->name : 'System' }}**.

## Task Details:
* **Title:** {{ $task->title }}
* **Priority:** {{ ucfirst($task->priority) }}
* **Due Date:** {{ $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('d M Y') : 'No due date' }}
* **Project/Company:** {{ $task->project ? $task->project->name : 'No Project' }}

@if($task->description)
### Description:
{{ $task->description }}
@endif

@component('mail::button', ['url' => route('tasks.kanban', ['task_id' => $task->id])])
Open Task in Kanban Board
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
