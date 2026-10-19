<?php

namespace App\Http\Controllers;

use App\Models\TournamentApplication;
use App\Support\TournamentApplications;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin review of managers' tournament applications.
 *
 * Separate from AdminTeamApplicationController, which reviews team rosters.
 */
class AdminTournamentApplicationController extends Controller
{
    /**
     * Ensure only admins can review tournament applications.
     */
    private function authorizeAdmin(): void
    {
        abort_unless(
            auth()->check() && auth()->user()->role === 'admin',
            403,
            'Access denied. Administrators only.'
        );
    }

    /**
     * List applications, pending first, newest first within each status.
     */
    public function index(): View
    {
        $this->authorizeAdmin();

        $applications = TournamentApplication::with(['tournament', 'manager'])
            ->orderByRaw("
                CASE
                    WHEN status = 'pending' THEN 0
                    WHEN status = 'rejected' THEN 1
                    WHEN status = 'conflict' THEN 2
                    WHEN status = 'approved' THEN 3
                    ELSE 4
                END
            ")
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return view('admin.tournament-applications', compact('applications'));
    }

    /**
     * Approve a pending application, granting the manager workspace access.
     */
    public function approve(TournamentApplication $application): RedirectResponse
    {
        $this->authorizeAdmin();

        $problems = TournamentApplications::approve($application);

        return $this->result($problems, 'Application approved. The manager can now open the tournament workspace.');
    }

    /**
     * Reject a pending application, with an optional reason.
     */
    public function reject(Request $request, TournamentApplication $application): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'admin_review_note' => 'nullable|string|max:1000',
        ]);

        $problems = TournamentApplications::reject($application, $validated['admin_review_note'] ?? null);

        return $this->result($problems, 'Application rejected.');
    }

    /**
     * @param  array<int, string>  $problems
     */
    private function result(array $problems, string $success): RedirectResponse
    {
        $redirect = redirect()->route('admin.tournament-applications.index');

        return $problems === []
            ? $redirect->with('success', $success)
            : $redirect->with('error', implode(' ', $problems));
    }
}
