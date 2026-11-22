<?php

namespace App\Http\Controllers;

use App\Services\CongregationSupportReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CongregationSupportReportController extends Controller
{
    public function show(Request $request, CongregationSupportReportService $service): View
    {
        $this->authorizeAdmin();

        [$from, $to] = $this->period($request, $service);
        $rows = $service->rows($from, $to);

        return view('congregation-support-report', [
            'rows' => $rows,
            'totals' => $service->totals($rows),
            'from' => $from,
            'to' => $to,
            'pdf' => false,
        ]);
    }

    public function pdf(Request $request, CongregationSupportReportService $service): Response
    {
        $this->authorizeAdmin();

        [$from, $to] = $this->period($request, $service);
        $rows = $service->rows($from, $to);

        return Pdf::loadView('congregation-support-report', [
            'rows' => $rows,
            'totals' => $service->totals($rows),
            'from' => $from,
            'to' => $to,
            'pdf' => true,
        ])
            ->setPaper('a4', 'landscape')
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('enableRemote', false)
            ->setOption('enablePhp', false)
            ->download('sprijin-pe-congregatii.pdf');
    }

    /**
     * @return array{?string, ?string}
     */
    private function period(Request $request, CongregationSupportReportService $service): array
    {
        return [
            $service->normalizeDate($request->query('from')),
            $service->normalizeDate($request->query('to')),
        ];
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin() ?? false, 403);
    }
}
