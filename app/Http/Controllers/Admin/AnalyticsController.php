<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    public function index()
    {
        $data = $this->buildMetrics();

        return view('admin.analytics.index', $data);
    }

    public function export(Request $request)
    {
        $format = $request->get('format', 'csv');
        $data = $this->buildMetrics();

        if ($format === 'pdf') {
            return response()
                ->view('admin.analytics.export-pdf', $data)
                ->header('Content-Type', 'text/html');
        }

        $filename = 'curbcream-analytics-' . now()->format('Y-m-d-His') . '.csv';

        return new StreamedResponse(function () use ($data) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Metric', 'Value']);
            fputcsv($handle, ['Active Users (Daily)', $data['activeUsers']['daily']]);
            fputcsv($handle, ['Active Users (Weekly)', $data['activeUsers']['weekly']]);
            fputcsv($handle, ['Active Users (Monthly)', $data['activeUsers']['monthly']]);
            fputcsv($handle, ['Avg Job Completion (hours)', $data['avgCompletionHours']]);
            fputcsv($handle, []);
            fputcsv($handle, ['Most Booked Services']);
            fputcsv($handle, ['Service', 'Jobs']);
            foreach ($data['mostBookedServices'] as $row) {
                fputcsv($handle, [$row->name, $row->jobs_count]);
            }
            fputcsv($handle, []);
            fputcsv($handle, ['Peak Booking Hours']);
            fputcsv($handle, ['Hour', 'Bookings']);
            foreach ($data['peakHours'] as $row) {
                fputcsv($handle, [$row->hour . ':00', $row->total]);
            }
            fputcsv($handle, []);
            fputcsv($handle, ['Provider Performance']);
            fputcsv($handle, ['Provider', 'Completed Jobs', 'Avg Rating']);
            foreach ($data['providerPerformance'] as $row) {
                fputcsv($handle, [
                    $row->business_name ?: $row->name,
                    $row->completed_jobs,
                    $row->avg_rating ?? 0,
                ]);
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function buildMetrics(): array
    {
        $daily = User::whereIn('role', ['user', 'driver'])
            ->where('updated_at', '>=', now()->subDay())
            ->count();
        $weekly = User::whereIn('role', ['user', 'driver'])
            ->where('updated_at', '>=', now()->subDays(7))
            ->count();
        $monthly = User::whereIn('role', ['user', 'driver'])
            ->where('updated_at', '>=', now()->subDays(30))
            ->count();

        $mostBookedServices = Product::query()
            ->select('products.name', DB::raw('COUNT(bookings.id) as jobs_count'))
            ->leftJoin('bookings', function ($join) {
                $join->on('bookings.driver_id', '=', 'products.user_id');
            })
            ->groupBy('products.name')
            ->orderByDesc('jobs_count')
            ->limit(8)
            ->get();

        $peakHours = Booking::query()
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('COUNT(*) as total'))
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $avgSeconds = Booking::where('status', 'Completed')
            ->whereNotNull('updated_at')
            ->whereNotNull('created_at')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as avg_seconds'))
            ->value('avg_seconds');

        $avgCompletionHours = $avgSeconds ? round(((float) $avgSeconds) / 3600, 2) : 0;

        $providerPerformance = User::where('role', 'driver')
            ->withCount(['driverBookings as completed_jobs' => function ($q) {
                $q->where('status', 'Completed');
            }])
            ->withAvg('reviews', 'rating')
            ->orderByDesc('completed_jobs')
            ->limit(10)
            ->get()
            ->map(function ($provider) {
                $provider->avg_rating = $provider->reviews_avg_rating
                    ? round((float) $provider->reviews_avg_rating, 1)
                    : 0;
                return $provider;
            });

        return [
            'activeUsers' => [
                'daily' => $daily,
                'weekly' => $weekly,
                'monthly' => $monthly,
            ],
            'mostBookedServices' => $mostBookedServices,
            'peakHours' => $peakHours,
            'avgCompletionHours' => $avgCompletionHours,
            'providerPerformance' => $providerPerformance,
            'reviewStats' => [
                'avg_rating' => round((float) (Review::avg('rating') ?? 0), 1),
                'total' => Review::count(),
            ],
        ];
    }
}
