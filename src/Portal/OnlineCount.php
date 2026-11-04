<?php

/*
 * This file is part of ernestdefoe/masthead.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Masthead\Portal;

use Carbon\Carbon;
use Flarum\User\User;
use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * "87 members online", for the utility strip on every page.
 *
 * One COUNT, cached for a minute, so the forum payload costs one query a
 * minute however many people load pages. A number, never a list: who is
 * online is something each member can hide, how many is not about anyone.
 */
final class OnlineCount
{
    public const CACHE_KEY = 'ernestdefoe-masthead.online';

    /*
     * Five minutes. Core writes last_seen_at at most every three, so a window
     * any shorter would drop people who are reading right now.
     */
    public const WINDOW_MINUTES = 5;

    public function __construct(protected Cache $cache)
    {
    }

    public function get(): int
    {
        return (int) $this->cache->remember(self::CACHE_KEY, 60, fn () => User::query()
            ->where('last_seen_at', '>=', Carbon::now()->subMinutes(self::WINDOW_MINUTES))
            ->count());
    }
}
