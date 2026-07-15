<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Broadcast;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class CommunicationController extends Controller
{
    public function index(Request $request)
    {
        $channel = $request->get('channel', 'all');

        $historyQuery = Broadcast::with('sender:id,name,email')->latest();
        if (in_array($channel, ['notification', 'email'], true)) {
            $historyQuery->where('channel', $channel);
        }

        $history = $historyQuery->paginate(15)->withQueryString();

        $stats = [
            'notifications_sent' => Broadcast::where('channel', 'notification')->count(),
            'emails_sent' => Broadcast::where('channel', 'email')->count(),
            'customers' => User::where('role', 'user')->where('is_banned', false)->count(),
            'providers' => User::where('role', 'driver')->where('is_banned', false)->count(),
        ];

        $automation = [
            'auto_notify_subscription_expiry' => PlatformSetting::getValue('auto_notify_subscription_expiry', '1') === '1',
            'auto_notify_job_status' => PlatformSetting::getValue('auto_notify_job_status', '1') === '1',
            'auto_notify_customer_reviews' => PlatformSetting::getValue('auto_notify_customer_reviews', '1') === '1',
            'auto_email_account_updates' => PlatformSetting::getValue('auto_email_account_updates', '1') === '1',
            'auto_email_promotions' => PlatformSetting::getValue('auto_email_promotions', '0') === '1',
        ];

        $categories = [
            'service_update' => 'Service updates',
            'feature' => 'New feature announcements',
            'promo' => 'Promotional offers',
            'policy' => 'Policy changes',
            'custom' => 'Custom',
        ];

        return view('admin.communication.index', compact(
            'history',
            'stats',
            'automation',
            'categories',
            'channel'
        ));
    }

    public function sendNotification(Request $request)
    {
        $data = $request->validate([
            'audience' => 'required|in:all,customers,providers',
            'category' => 'required|in:service_update,feature,promo,policy,custom',
            'title' => 'required|string|max:180',
            'message' => 'required|string|max:5000',
        ]);

        $recipients = $this->audienceQuery($data['audience'])->get(['id', 'email', 'name', 'role']);

        if ($recipients->isEmpty()) {
            return back()->with('error', 'No recipients found for the selected audience.');
        }

        Broadcast::create([
            'sent_by' => Auth::id(),
            'channel' => 'notification',
            'category' => $data['category'],
            'audience' => $data['audience'],
            'title' => $data['title'],
            'message' => $data['message'],
            'recipients_count' => $recipients->count(),
            'status' => 'sent',
            'meta' => [
                'recipient_ids' => $recipients->pluck('id')->take(100)->values()->all(),
            ],
        ]);

        return back()->with('success', "Platform notification sent to {$recipients->count()} recipient(s).");
    }

    public function sendEmail(Request $request)
    {
        $data = $request->validate([
            'audience' => 'required|in:all,customers,providers',
            'category' => 'required|in:service_update,feature,promo,policy,account_update,custom',
            'subject' => 'required|string|max:180',
            'title' => 'required|string|max:180',
            'message' => 'required|string|max:10000',
        ]);

        $recipients = $this->audienceQuery($data['audience'])->get(['id', 'email', 'name']);

        if ($recipients->isEmpty()) {
            return back()->with('error', 'No recipients found for the selected audience.');
        }

        $sent = 0;
        $failed = 0;

        foreach ($recipients as $recipient) {
            if (!$recipient->email) {
                $failed++;
                continue;
            }

            try {
                Mail::raw($data['message'], function ($mail) use ($recipient, $data) {
                    $mail->to($recipient->email, $recipient->name)
                        ->subject($data['subject']);
                });
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
            }
        }

        $status = $failed === 0 ? 'sent' : ($sent === 0 ? 'failed' : 'partial');

        Broadcast::create([
            'sent_by' => Auth::id(),
            'channel' => 'email',
            'category' => $data['category'],
            'audience' => $data['audience'],
            'title' => $data['title'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'recipients_count' => $sent,
            'status' => $status,
            'meta' => [
                'attempted' => $recipients->count(),
                'failed' => $failed,
            ],
        ]);

        if ($status === 'failed') {
            return back()->with('error', 'Bulk email failed to send.');
        }

        $msg = "Bulk email sent to {$sent} recipient(s).";
        if ($failed > 0) {
            $msg .= " {$failed} failed.";
        }

        return back()->with('success', $msg);
    }

    public function updateAutomation(Request $request)
    {
        $data = $request->validate([
            'auto_notify_subscription_expiry' => 'nullable|boolean',
            'auto_notify_job_status' => 'nullable|boolean',
            'auto_notify_customer_reviews' => 'nullable|boolean',
            'auto_email_account_updates' => 'nullable|boolean',
            'auto_email_promotions' => 'nullable|boolean',
        ]);

        $keys = [
            'auto_notify_subscription_expiry',
            'auto_notify_job_status',
            'auto_notify_customer_reviews',
            'auto_email_account_updates',
            'auto_email_promotions',
        ];

        foreach ($keys as $key) {
            PlatformSetting::setValue($key, $request->boolean($key) ? '1' : '0');
        }

        return back()->with('success', 'Automated notification settings updated.');
    }

    private function audienceQuery(string $audience)
    {
        $query = User::query()
            ->where('is_banned', false)
            ->whereIn('role', ['user', 'driver']);

        if ($audience === 'customers') {
            $query->where('role', 'user');
        } elseif ($audience === 'providers') {
            $query->where('role', 'driver');
        }

        return $query;
    }
}
