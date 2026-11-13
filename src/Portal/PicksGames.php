<?php

/*
 * This file is part of ernestdefoe/masthead.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Masthead\Portal;

use Carbon\Carbon;
use Flarum\Extension\ExtensionManager;
use Flarum\User\User;

/**
 * The next few pick'em games, for the portal sidebar, when ernestdefoe/picks
 * is enabled.
 *
 * 🚨 Soft, like Page Builder: no import, the model named as a string and
 * checked with class_exists, every failure an empty list. Gated by Picks' own
 * `picks.view`, the permission its board answers to, so a forum that keeps
 * its pick'em to members does not show the schedule to guests here.
 *
 * Three queries when Picks is enabled (the games and their two teams, eager),
 * none when it is not.
 */
final class PicksGames
{
    private const EVENT = 'Resofire\\Picks\\PickEvent';

    public const COUNT = 4;

    public function __construct(protected ExtensionManager $extensions)
    {
    }

    /**
     * @return list<array{id: int, home: string, away: string, homeLogo: ?string, awayLogo: ?string, at: ?string, status: string}>|null
     */
    public function upcoming(User $actor): ?array
    {
        if (! $this->extensions->isEnabled('ernestdefoe-picks') || ! class_exists(self::EVENT) || ! $actor->can('picks.view')) {
            return null;
        }

        try {
            $events = call_user_func([self::EVENT, 'query'])
                ->with(['homeTeam', 'awayTeam'])
                ->whereIn('status', ['scheduled', 'in_progress'])
                // A game that kicked off in the last few hours is still the
                // one people are talking about.
                ->where('match_date', '>=', Carbon::now()->subHours(4))
                ->orderBy('match_date')
                ->limit(self::COUNT)
                ->get();
        } catch (\Throwable) {
            return null;
        }

        $rows = [];

        foreach ($events as $event) {
            if (! $event->homeTeam || ! $event->awayTeam) {
                continue;
            }

            $rows[] = [
                'id' => (int) $event->id,
                'home' => (string) $event->homeTeam->name,
                'away' => (string) $event->awayTeam->name,
                'homeLogo' => Images::safe($event->homeTeam->logo_url ?? null),
                'awayLogo' => Images::safe($event->awayTeam->logo_url ?? null),
                'at' => $event->match_date?->toAtomString(),
                'status' => (string) $event->status,
            ];
        }

        return $rows;
    }
}
