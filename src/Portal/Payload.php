<?php

/*
 * This file is part of ernestdefoe/masthead.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Masthead\Portal;

use Flarum\User\User;

/**
 * Everything the portal needs beyond what the forum payload already carries.
 *
 * The tag grid is drawn from the tags the forum payload loads anyway, and the
 * latest list is core's own discussion endpoint, so this is only the two
 * things nothing else provides: the hero, the week's top posters and, with
 * ernestdefoe/picks, the next games.
 */
final class Payload
{
    public function __construct(protected Hero $hero, protected TopPosters $topPosters, protected PicksGames $games)
    {
    }

    /**
     * @return array{hero: list<array<string, mixed>>, topPosters: list<array<string, mixed>>, games: list<array<string, mixed>>|null}
     */
    public function for(User $actor): array
    {
        return [
            'hero' => $this->hero->for($actor),
            'topPosters' => $this->topPosters->get(),
            // null, not [], when Picks is absent: "no games this week" and "no
            // pick'em here" draw differently (the second draws nothing).
            'games' => $this->games->upcoming($actor),
        ];
    }
}
