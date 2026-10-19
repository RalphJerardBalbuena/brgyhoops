<?php

namespace App\Support;

use App\Models\Tournament;
use App\Models\TournamentApplication;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Manager tournament applications: submission, approval and rejection.
 *
 * Eligibility is rechecked inside each transaction. The *Problems() methods
 * return reasons an action is not allowed (empty when it is), so later rules
 * (e.g. S3.11 scheduling conflicts) can be added in one place.
 *
 * Workspace access is granted only by approve(), which adds the
 * tournament_user row in the same transaction as the status change.
 */
class TournamentApplications
{
    public const DUPLICATE = 'You have already applied to this tournament.';

    /**
     * Reasons the manager may not apply to the tournament.
     *
     * @return array<int, string>
     */
    public static function submissionProblems(User $manager, Tournament $tournament): array
    {
        if ($manager->role !== 'team_manager') {
            return ['Only team managers can apply to tournaments.'];
        }

        if (!$tournament->isOpenForApplications()) {
            return ['This tournament is no longer accepting applications.'];
        }

        if ($tournament->users()->whereKey($manager->id)->exists()) {
            return ['You are already assigned to this tournament.'];
        }

        if ($tournament->applications()->where('user_id', $manager->id)->exists()) {
            return [self::DUPLICATE];
        }

        return [];
    }

    /**
     * Submit a pending application. Returns the problems, empty on success.
     *
     * @return array<int, string>
     */
    public static function submit(User $manager, Tournament $tournament): array
    {
        try {
            return DB::transaction(function () use ($manager, $tournament) {
                $tournament = Tournament::whereKey($tournament->id)->lockForUpdate()->first();

                if (!$tournament) {
                    return ['This tournament is no longer accepting applications.'];
                }

                if ($problems = self::submissionProblems($manager, $tournament)) {
                    return $problems;
                }

                $tournament->applications()->create(['user_id' => $manager->id]);

                return [];
            });
        } catch (UniqueConstraintViolationException) {
            // A concurrent submission won the race; the unique index kept one row.
            return [self::DUPLICATE];
        }
    }

    /**
     * Reasons the application may not be approved.
     *
     * @return array<int, string>
     */
    public static function approvalProblems(TournamentApplication $application): array
    {
        if ($application->status !== 'pending') {
            return ['This application has already been reviewed.'];
        }

        if ($application->manager?->role !== 'team_manager') {
            return ['The applicant is no longer a team manager. Reject the application instead.'];
        }

        if (!$application->tournament?->isOpenForApplications()) {
            return ['This tournament has already started, so the application can no longer be approved. Reject it instead.'];
        }

        return [];
    }

    /**
     * Approve a pending application and grant workspace access.
     * Returns the problems, empty on success.
     *
     * @return array<int, string>
     */
    public static function approve(TournamentApplication $application): array
    {
        return DB::transaction(function () use ($application) {
            $locked = TournamentApplication::with(['tournament', 'manager'])
                ->whereKey($application->id)
                ->lockForUpdate()
                ->first();

            if (!$locked) {
                return ['This application no longer exists.'];
            }

            if ($problems = self::approvalProblems($locked)) {
                return $problems;
            }

            if (!self::markReviewed($locked, 'approved', null)) {
                return ['This application has already been reviewed.'];
            }

            $locked->tournament->users()->syncWithoutDetaching([$locked->user_id]);

            return [];
        });
    }

    /**
     * Reject a pending application, with an optional reason.
     * Returns the problems, empty on success.
     *
     * @return array<int, string>
     */
    public static function reject(TournamentApplication $application, ?string $note): array
    {
        return DB::transaction(function () use ($application, $note) {
            $locked = TournamentApplication::whereKey($application->id)->lockForUpdate()->first();

            if (!$locked) {
                return ['This application no longer exists.'];
            }

            if ($locked->status !== 'pending' || !self::markReviewed($locked, 'rejected', $note)) {
                return ['This application has already been reviewed.'];
            }

            return [];
        });
    }

    /**
     * Move a pending application to a reviewed status. The status condition
     * makes this a compare-and-set, so only one review can ever succeed.
     */
    private static function markReviewed(TournamentApplication $application, string $status, ?string $note): bool
    {
        $now = now();

        return TournamentApplication::whereKey($application->id)
            ->where('status', 'pending')
            ->update([
                'status' => $status,
                'admin_review_note' => $note,
                'reviewed_at' => $now,
                'updated_at' => $now,
            ]) === 1;
    }
}
