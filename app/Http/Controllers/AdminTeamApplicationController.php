<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Notifications\TeamApplicationReviewed;
use App\Support\RosterRules;
use App\Support\SafeNotifier;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminTeamApplicationController extends Controller
{
    /**
     * Ensure only admins can manage team applications.
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
     * Display submitted team applications.
     */
    public function index(): View
    {
        $this->authorizeAdmin();

        $applications = Team::with([
                'tournament',
                'manager',
                'roster' => fn ($query) => $query->orderBy('name'),
            ])
            ->whereIn('application_status', [
                'pending',
                'approved',
                'rejected',
            ])
            ->orderByRaw("
                CASE
                    WHEN application_status = 'pending' THEN 0
                    WHEN application_status = 'rejected' THEN 1
                    WHEN application_status = 'approved' THEN 2
                    ELSE 3
                END
            ")
            ->orderByDesc('applied_at')
            ->get();

        return view('admin.team-applications', compact('applications'));
    }

    /**
     * Approve a pending team application.
     */
    public function approve(Request $request, Team $team): RedirectResponse
    {
        $this->authorizeAdmin();

        if ($team->application_status !== 'pending') {
            return redirect()
                ->route('admin.team-applications.index')
                ->with('error', 'Only pending applications can be approved.');
        }

        $validated = $request->validate([
            'admin_review_note' => 'nullable|string|max:1000',
        ]);

        if ($problems = RosterRules::approvalProblems($team)) {
            return redirect()
                ->route('admin.team-applications.index')
                ->with('error', $team->name . ': ' . implode(' ', $problems) . ' Reject it with a reason so the manager can fix the roster.');
        }

        $team->application_status = 'approved';
        $team->reviewed_at = now();
        $team->admin_review_note = $validated['admin_review_note'] ?? null;
        $team->save();

        SafeNotifier::send($team->manager, new TeamApplicationReviewed($team));

        return redirect()
            ->route('admin.team-applications.index')
            ->with('success', 'Team application approved successfully.');
    }

    /**
     * Reject a pending team application.
     */
    public function reject(Request $request, Team $team): RedirectResponse
    {
        $this->authorizeAdmin();

        if ($team->application_status !== 'pending') {
            return redirect()
                ->route('admin.team-applications.index')
                ->with('error', 'Only pending applications can be rejected.');
        }

        $validated = $request->validate([
            'admin_review_note' => 'required|string|max:1000',
        ]);

        $team->application_status = 'rejected';
        $team->admin_review_note = $validated['admin_review_note'];
        $team->reviewed_at = now();
        $team->save();

        SafeNotifier::send($team->manager, new TeamApplicationReviewed($team));

        return redirect()
            ->route('admin.team-applications.index')
            ->with(
                'success',
                'Team application rejected. The manager can review the reason and resubmit.'
            );
    }
}