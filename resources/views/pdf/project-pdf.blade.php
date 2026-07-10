<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Compliance Report: {{ $project->name }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #334155;
            font-size: 13px;
            line-height: 1.5;
            margin: 20px;
        }
        .header {
            border-bottom: 2px solid #0ea5e9;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        .header-title {
            font-size: 24px;
            font-weight: bold;
            color: #0f172a;
            margin: 0;
        }
        .header-subtitle {
            font-size: 12px;
            color: #64748b;
            margin: 5px 0 0 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #0ea5e9;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 5px;
            margin-bottom: 12px;
            text-transform: uppercase;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
        }
        th {
            font-weight: bold;
            color: #475569;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            width: 30%;
        }
        td {
            border: 1px solid #e2e8f0;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            border-radius: 4px;
        }
        .badge-success { background-color: #dcfce7; color: #15803d; }
        .badge-warning { background-color: #fef3c7; color: #b45309; }
        .badge-danger { background-color: #fee2e2; color: #b91c1c; }
        .badge-info { background-color: #e0f2fe; color: #0369a1; }
        .badge-neutral { background-color: #f1f5f9; color: #475569; }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
            border-top: 1px solid #f1f5f9;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="header-title">{{ $project->name }}</h1>
        <div class="header-subtitle">Compliance & KYB/KYC Audit Report</div>
    </div>

    <!-- Company Details -->
    <div class="section">
        <div class="section-title">General Company Details</div>
        <table>
            <tr>
                <th>Project Status</th>
                <td>
                    <span class="badge {{ $project->status === 'active' ? 'badge-success' : ($project->status === 'onboarding' ? 'badge-warning' : 'badge-danger') }}">
                        {{ $project->status }}
                    </span>
                </td>
            </tr>
            <tr>
                <th>Integration Status</th>
                <td>
                    <span class="badge {{ $project->integration_status === 'completed' ? 'badge-success' : ($project->integration_status === 'in_progress' ? 'badge-warning' : 'badge-neutral') }}">
                        {{ $project->integration_status ?: 'Pending' }}
                    </span>
                </td>
            </tr>
            <tr>
                <th>Industry MCC Code</th>
                <td><code>{{ $project->mcc ?: 'Not specified' }}</code></td>
            </tr>
            <tr>
                <th>Beneficiary (UBO)</th>
                <td>{{ $project->ubo ?: 'Not specified' }}</td>
            </tr>
            <tr>
                <th>Project Manager</th>
                <td>{{ $project->manager?->name ?: 'Not assigned' }}</td>
            </tr>
            <tr>
                <th>Websites</th>
                <td>
                    @forelse($project->websites as $web)
                        {{ $web->name }}: {{ $web->url }}<br>
                    @empty
                        None registered
                    @endforelse
                </td>
            </tr>
        </table>
    </div>

    <!-- Director Info -->
    <div class="section">
        <div class="section-title">Director Information</div>
        <table>
            <tr>
                <th>Full Name</th>
                <td>{{ $project->director?->name ?: 'Not specified' }}</td>
            </tr>
            <tr>
                <th>Fee Paid Status</th>
                <td>
                    <span class="badge {{ ($project->director?->fee_paid_status ?? '') === 'paid' ? 'badge-success' : (($project->director?->fee_paid_status ?? '') === 'pending' ? 'badge-warning' : 'badge-danger') }}">
                        {{ $project->director?->fee_paid_status ?: 'unpaid' }}
                    </span>
                </td>
            </tr>
            <tr>
                <th>Curator / Manager</th>
                <td>{{ $project->director?->manager?->name ?: 'Not assigned' }}</td>
            </tr>
        </table>
    </div>

    <!-- Compliance Checklist -->
    @if($project->boarding)
    <div class="section">
        <div class="section-title">Compliance Verification Checklist (KYB/KYC)</div>
        <table>
            <tr>
                <th>KYB Verification Completed</th>
                <td>{{ $project->boarding->kyb_completed_at ? $project->boarding->kyb_completed_at->format('d M Y') : 'Pending' }}</td>
            </tr>
            <tr>
                <th>Boarding Completed</th>
                <td>{{ $project->boarding->boarding_completed_at ? $project->boarding->boarding_completed_at->format('d M Y') : 'Pending' }}</td>
            </tr>
            <tr>
                <th>CFS Verification</th>
                <td>
                    <span class="badge {{ $project->boarding->cfs_verification === 'completed' ? 'badge-success' : ($project->boarding->cfs_verification === 'pending' ? 'badge-warning' : 'badge-danger') }}">
                        {{ str_replace('_', ' ', $project->boarding->cfs_verification) }}
                    </span>
                </td>
            </tr>
            <tr>
                <th>Cardaq / SumSub Status</th>
                <td>
                    <span class="badge {{ $project->boarding->cardaq_sumsub === 'approved' ? 'badge-success' : ($project->boarding->cardaq_sumsub === 'pending' ? 'badge-warning' : 'badge-danger') }}">
                        {{ str_replace('_', ' ', $project->boarding->cardaq_sumsub) }}
                    </span>
                </td>
            </tr>
            <tr>
                <th>Bank Verification</th>
                <td>
                    <span class="badge {{ $project->boarding->bank_verification === 'completed' ? 'badge-success' : ($project->boarding->bank_verification === 'pending' ? 'badge-warning' : 'badge-danger') }}">
                        {{ str_replace('_', ' ', $project->boarding->bank_verification) }}
                    </span>
                </td>
            </tr>
            <tr>
                <th>Companies House Registry</th>
                <td>
                    <span class="badge {{ $project->boarding->companies_house_verification === 'completed' ? 'badge-success' : ($project->boarding->companies_house_verification === 'pending' ? 'badge-warning' : 'badge-danger') }}">
                        {{ str_replace('_', ' ', $project->boarding->companies_house_verification) }}
                    </span>
                </td>
            </tr>
        </table>
    </div>
    @endif

    <!-- Reports / Registers Info -->
    @if($project->report)
    <div class="section">
        <div class="section-title">Company Registers & Tax Filings</div>
        <table>
            <tr>
                <th>Registration Number</th>
                <td>{{ $project->report->reg_number ?: 'Not specified' }}</td>
            </tr>
            <tr>
                <th>Registered Address</th>
                <td>{{ $project->report->registered_address ?: 'Not specified' }}</td>
            </tr>
            <tr>
                <th>Accounts Due By</th>
                <td>{{ $project->report->accounts_due_by ? $project->report->accounts_due_by->format('d M Y') : 'Not scheduled' }}</td>
            </tr>
            <tr>
                <th>Statements Due By</th>
                <td>{{ $project->report->statements_due_by ? $project->report->statements_due_by->format('d M Y') : 'Not scheduled' }}</td>
            </tr>
        </table>
    </div>
    @endif

    <div class="footer">
        Generated on {{ now()->format('Y-m-d H:i:s') }} | Project Manager Hub
    </div>
</body>
</html>
