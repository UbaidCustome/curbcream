@extends('admin.layouts.app')

@section('title', 'Communication')

@section('content')
    <div class="mb-4">
        <h1 class="display-font h3 mb-1">Communication & Notifications</h1>
        <p class="text-secondary mb-0">Send platform notifications, bulk emails, and manage automation</p>
    </div>

    <div class="row g-3 mb-4">
        @foreach([
            ['Notifications Sent', $stats['notifications_sent']],
            ['Emails Sent', $stats['emails_sent']],
            ['Customers', $stats['customers']],
            ['Providers', $stats['providers']],
        ] as [$label, $value])
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="label">{{ $label }}</div>
                    <div class="value">{{ $value }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="panel h-100">
                <div class="panel-head"><strong class="display-font">Send Platform Notification</strong></div>
                <div class="panel-body">
                    <form action="{{ route('admin.communication.notifications.send') }}" method="POST" class="js-action-form">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Audience</label>
                            <select name="audience" class="form-select" required>
                                <option value="all">All users & providers</option>
                                <option value="customers">Customers only</option>
                                <option value="providers">Providers only</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Category</label>
                            <select name="category" class="form-select" required>
                                @foreach($categories as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Title</label>
                            <input type="text" name="title" class="form-control" placeholder="Service update title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Message</label>
                            <textarea name="message" class="form-control" rows="4" placeholder="Write your announcement..." required></textarea>
                        </div>
                        <button class="btn btn-success btn-action" style="background:#2bb673;border:0">
                            <span class="btn-label">Send notification</span>
                            <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span> Sending...</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="panel h-100">
                <div class="panel-head"><strong class="display-font">Send Bulk Email</strong></div>
                <div class="panel-body">
                    <form action="{{ route('admin.communication.emails.send') }}" method="POST" class="js-action-form">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Audience</label>
                            <select name="audience" class="form-select" required>
                                <option value="all">All users & providers</option>
                                <option value="customers">Customers only</option>
                                <option value="providers">Providers only</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Category</label>
                            <select name="category" class="form-select" required>
                                <option value="promo">Promotional offers</option>
                                <option value="account_update">Account updates</option>
                                <option value="service_update">Service updates</option>
                                <option value="feature">New features</option>
                                <option value="policy">Policy changes</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email subject</label>
                            <input type="text" name="subject" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Title (internal)</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email body</label>
                            <textarea name="message" class="form-control" rows="4" required></textarea>
                        </div>
                        <button class="btn btn-success btn-action" style="background:#2bb673;border:0">
                            <span class="btn-label">Send bulk email</span>
                            <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span> Sending...</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="panel mb-4">
        <div class="panel-head"><strong class="display-font">Automated Notifications</strong></div>
        <div class="panel-body">
            <form action="{{ route('admin.communication.automation.update') }}" method="POST" class="js-action-form">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="d-flex align-items-start gap-2 p-3 border rounded">
                            <input type="checkbox" name="auto_notify_subscription_expiry" value="1" class="mt-1" @checked($automation['auto_notify_subscription_expiry'])>
                            <span>
                                <strong>Subscription expiry reminders</strong>
                                <div class="small text-secondary">Auto notify providers before subscription expiry</div>
                            </span>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <label class="d-flex align-items-start gap-2 p-3 border rounded">
                            <input type="checkbox" name="auto_notify_job_status" value="1" class="mt-1" @checked($automation['auto_notify_job_status'])>
                            <span>
                                <strong>Job status updates</strong>
                                <div class="small text-secondary">Auto notify on booking status changes</div>
                            </span>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <label class="d-flex align-items-start gap-2 p-3 border rounded">
                            <input type="checkbox" name="auto_notify_customer_reviews" value="1" class="mt-1" @checked($automation['auto_notify_customer_reviews'])>
                            <span>
                                <strong>Customer review reminders</strong>
                                <div class="small text-secondary">Remind customers to leave reviews after jobs</div>
                            </span>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <label class="d-flex align-items-start gap-2 p-3 border rounded">
                            <input type="checkbox" name="auto_email_account_updates" value="1" class="mt-1" @checked($automation['auto_email_account_updates'])>
                            <span>
                                <strong>Account update emails</strong>
                                <div class="small text-secondary">Automated email when account details are updated</div>
                            </span>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <label class="d-flex align-items-start gap-2 p-3 border rounded">
                            <input type="checkbox" name="auto_email_promotions" value="1" class="mt-1" @checked($automation['auto_email_promotions'])>
                            <span>
                                <strong>Promotional emails</strong>
                                <div class="small text-secondary">Allow automated promotional email campaigns</div>
                            </span>
                        </label>
                    </div>
                </div>
                <button class="btn btn-success btn-action mt-3" style="background:#2bb673;border:0">
                    <span class="btn-label">Save automation settings</span>
                    <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span> Saving...</span>
                </button>
            </form>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <strong class="display-font">Send History</strong>
            <div class="d-flex gap-2 flex-wrap">
                @foreach(['all' => 'All', 'notification' => 'Notifications', 'email' => 'Emails'] as $key => $label)
                    <a href="{{ route('admin.communication.index', ['channel' => $key]) }}"
                       class="filter-chip {{ $channel === $key ? 'active' : '' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Title</th>
                        <th>Audience</th>
                        <th>Category</th>
                        <th>Recipients</th>
                        <th>Status</th>
                        <th>Sent</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($history as $item)
                        @php
                            $statusClass = match($item->status) {
                                'sent' => 'approved',
                                'partial' => 'pending',
                                default => 'rejected',
                            };
                        @endphp
                        <tr>
                            <td>
                                <span class="badge-soft {{ $item->channel === 'email' ? 'more_info' : 'approved' }}">
                                    {{ $item->channel }}
                                </span>
                            </td>
                            <td>
                                <strong>{{ $item->title }}</strong>
                                @if($item->subject)
                                    <div class="small text-secondary">{{ $item->subject }}</div>
                                @endif
                                <div class="small text-secondary">{{ \Illuminate\Support\Str::limit($item->message, 70) }}</div>
                            </td>
                            <td class="text-capitalize">{{ $item->audience }}</td>
                            <td class="text-capitalize">{{ str_replace('_', ' ', $item->category) }}</td>
                            <td>{{ $item->recipients_count }}</td>
                            <td><span class="badge-soft {{ $statusClass }}">{{ $item->status }}</span></td>
                            <td>
                                <div>{{ $item->created_at?->format('Y-m-d H:i') }}</div>
                                <div class="small text-secondary">{{ $item->sender->name ?? 'Admin' }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-secondary py-4">No broadcasts yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($history->hasPages())
            <div class="panel-body">{{ $history->links() }}</div>
        @endif
    </div>
@endsection
