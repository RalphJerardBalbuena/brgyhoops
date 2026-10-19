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
 * return reasons an action is not allowed (empty when it is).
 *
 * Scheduling rule (S3.11): a manager may not take part in two tournaments
 * whose dates overlap (inclusive) while still active in one of them. A
 * manager is released from a tournament once all of their approved teams
 * there have been officially eliminated (teams.eliminated_at). Only
 * participation (a tournament_user row) counts; pending applications do not,
 * but every approval rechecks the rule. Submit and approve lock the manager's
 * user row so concurrent requests for the same manager run one at a time.
 *
 * A manager may have only one pending application at a time. When one is
 * approved, the manager's other pending applications that now overlap it
 * are closed with status "conflict" (kept, not deleted). A conflict-closed
 * application can be resubmitted once the manager is eligible again (e.g.
 * after elimination); rejected applications stay final.
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

        // One application per manager and tournament; only a conflict-closed
        // one may be resubmitted.
        $existing = $tournament->applications()->where('user_id', $manager->id)->first();

        if ($existing && $existing->status !== 'conflict') {
            return [self::DUPLICATE];
        }

        $pending = $manager->tournamentApplications()
            ->where('status', 'pending')
            ->with('tournament')
            ->first();

        if ($pending) {
            return [
                'You already have a pending application for ' . ($pending->tournament?->name ?? 'another tournament') . '. '
                . 'You can apply to another tournament once an administrator has reviewed it.',
            ];
        }

        if ($conflicts = self::conflictingTournaments($manager, $tournament)) {
            return [
                'You are still taking part in ' . self::names($conflicts)
                . self::overlapReason($conflicts, $tournament, ', which overlaps these dates. ')
                . 'You can apply once your team has been eliminated there or that tournament has ended.',
            ];
        }

        return [];
    }

    /**
     * Tournaments the manager still actively takes part in that overlap the
     * given tournament. Dated ranges are compared inclusively (a shared
     * start/end day counts).
     *
     * Where either side has no dates (a legacy record), an overlap cannot be
     * ruled out, so participation that has not ended (upcoming or ongoing)
     * counts as a conflict. No dates are assumed or written.
     *
     * @return array<int, Tournament>
     */
    public static function conflictingTournaments(User $manager, Tournament $tournament): array
    {
        $query = $manager->tournaments()->where('tournaments.id', '!=', $tournament->id);

        if (self::hasDates($tournament)) {
            $query->where(function ($query) use ($tournament) {
                $query->where(function ($dated) use ($tournament) {
                    $dated->whereNotNull('tournaments.start_date')
                        ->whereNotNull('tournaments.end_date')
                        ->whereDate('tournaments.start_date', '<=', $tournament->end_date->toDateString())
                        ->whereDate('tournaments.end_date', '>=', $tournament->start_date->toDateString());
                })->orWhere(function ($undated) {
                    $undated->where(fn ($missing) => $missing->whereNull('tournaments.start_date')->orWhereNull('tournaments.end_date'))
                        ->whereIn('tournaments.status', Tournament::MANAGEABLE_STATUSES);
                });
            });
        } else {
            $query->manageable();
        }

        return $query
            ->orderBy('tournaments.start_date')
            ->get()
            ->reject(fn (Tournament $other) => self::isReleasedFrom($manager, $other))
            ->values()
            ->all();
    }

    /**
     * Whether the manager is released from a tournament they are assigned to:
     * they have approved teams there and every one has been eliminated.
     * Assigned without an approved team still counts as taking part.
     */
    public static function isReleasedFrom(User $manager, Tournament $tournament): bool
    {
        $teams = $tournament->teams()
            ->where('user_id', $manager->id)
            ->where('application_status', 'approved')
            ->get();

        return $teams->isNotEmpty() && $teams->every(fn ($team) => $team->isEliminated());
    }

    private static function hasDates(Tournament $tournament): bool
    {
        return $tournament->start_date !== null && $tournament->end_date !== null;
    }

    /**
     * Why the conflicting tournaments block the given one: a real date overlap,
     * or missing dates on either side (an overlap cannot be ruled out).
     *
     * @param  array<int, Tournament>  $conflicts
     */
    private static function overlapReason(array $conflicts, Tournament $tournament, string $datedOverlap): string
    {
        if (!self::hasDates($tournament)) {
            return '. This tournament has no dates set yet, so it may overlap. ';
        }

        $undated = array_values(array_filter($conflicts, fn (Tournament $other) => !self::hasDates($other)));

        if ($undated === []) {
            return $datedOverlap;
        }

        return '. ' . self::names($undated) . (count($undated) === 1 ? ' has' : ' have')
            . ' no dates set, so an overlap cannot be ruled out. ';
    }

    /**
     * @param  array<int, Tournament>  $tournaments
     */
    private static function names(array $tournaments): string
    {
        return collect($tournaments)->pluck('name')->join(', ', ' and ');
    }

    /**
     * Lock the manager's user row so concurrent submissions and approvals for
     * the same manager are serialized (a no-op on SQLite, which serializes
     * writes itself).
     */
    private static function lockManager(int $managerId): ?User
    {
        return User::whereKey($managerId)->lockForUpdate()->first();
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
                $manager = self::lockManager($manager->id) ?? $manager;
                $tournament = Tournament::whereKey($tournament->id)->lockForUpdate()->first();

                if (!$tournament) {
                    return ['This tournament is no longer accepting applications.'];
                }

                if ($problems = self::submissionProblems($manager, $tournament)) {
                    return $problems;
                }

                $existing = $tournament->applications()->where('user_id', $manager->id)->first();

                if ($existing) {
                    // Reopen the conflict-closed application (same row, so the
                    // one-per-tournament rule still holds).
                    $now = now();
                    $reopened = TournamentApplication::whereKey($existing->id)
                        ->where('status', 'conflict')
                        ->update([
                            'status' => 'pending',
                            'admin_review_note' => null,
                            'reviewed_at' => null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);

                    return $reopened === 1 ? [] : [self::DUPLICATE];
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

        if ($conflicts = self::conflictingTournaments($application->manager, $application->tournament)) {
            return [
                "{$application->manager->name} is still taking part in " . self::names($conflicts)
                . self::overlapReason($conflicts, $application->tournament, ', which overlaps this tournament. ')
                . 'Approve only after their team has been eliminated there, or reject the application.',
            ];
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
            // Lock the manager first (same order as submit()), then the application.
            self::lockManager($application->user_id);

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

            self::closeOverlappingPending($locked);

            return [];
        });
    }

    /**
     * After an approval, close the manager's other pending applications that
     * the overlap rule now blocks (status "conflict", with a note). Runs inside
     * the approval transaction; each is closed only if still pending.
     */
    private static function closeOverlappingPending(TournamentApplication $approved): void
    {
        $others = TournamentApplication::with('tournament')
            ->where('user_id', $approved->user_id)
            ->where('status', 'pending')
            ->whereKeyNot($approved->id)
            ->get();

        foreach ($others as $other) {
            if (!$other->tournament) {
                continue;
            }

            $conflictIds = collect(self::conflictingTournaments($approved->manager, $other->tournament))->pluck('id');

            if ($conflictIds->contains($approved->tournament_id)) {
                self::markReviewed(
                    $other,
                    'conflict',
                    "Closed automatically: it overlaps {$approved->tournament->name}, which was approved for this manager."
                );
            }
        }
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
