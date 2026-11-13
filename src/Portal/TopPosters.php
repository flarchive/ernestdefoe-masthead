<?php

/*
 * This file is part of ernestdefoe/masthead.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Masthead\Portal;

use Carbon\Carbon;
use Flarum\Http\SlugManager;
use Flarum\Post\Post;
use Flarum\User\Guest;
use Flarum\User\User;
use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * Top posters this week: one grouped COUNT and one user lookup, cached.
 *
 * 🚨 Counted as a GUEST sees the forum, not as the actor does. The result is
 * cached once for everyone, so it may only contain what everyone may know: a
 * post in a private discussion or a restricted tag adds nothing here, and a
 * moderator's view can never leak into a guest's widget through the cache.
 */
final class TopPosters
{
    public const CACHE_KEY = 'ernestdefoe-masthead.top-posters';
    public const LIMIT = 5;

    public function __construct(protected Cache $cache, protected SlugManager $slugs)
    {
    }

    /**
     * @return list<array{id: int, slug: string, displayName: string, avatarUrl: ?string, posts: int}>
     */
    public function get(): array
    {
        return $this->cache->remember(self::CACHE_KEY, 600, fn () => $this->compute());
    }

    public function forget(): void
    {
        $this->cache->forget(self::CACHE_KEY);
    }

    /**
     * @return list<array{id: int, slug: string, displayName: string, avatarUrl: ?string, posts: int}>
     */
    protected function compute(): array
    {
        $counts = Post::query()
            ->whereVisibleTo(new Guest())
            ->where('posts.type', 'comment')
            ->whereNotNull('posts.user_id')
            ->where('posts.created_at', '>=', Carbon::now()->subDays(7))
            ->select('posts.user_id')
            // No table named inside the raw fragment, so the prefix is moot.
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('posts.user_id')
            ->orderByDesc('aggregate')
            ->limit(self::LIMIT)
            ->pluck('aggregate', 'user_id');

        if ($counts->isEmpty()) {
            return [];
        }

        $users = User::query()->whereIn('id', $counts->keys())->get()->keyBy('id');
        $slugs = $this->slugs->forResource(User::class);
        $rows = [];

        foreach ($counts as $userId => $posts) {
            /** @var User|null $user */
            $user = $users->get($userId);

            if (! $user) {
                continue;
            }

            $rows[] = [
                'id' => (int) $user->id,
                'slug' => $slugs->toSlug($user),
                'displayName' => $user->display_name,
                'avatarUrl' => $user->avatar_url,
                'posts' => (int) $posts,
            ];
        }

        return $rows;
    }
}
