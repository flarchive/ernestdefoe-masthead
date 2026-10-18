<?php

/*
 * This file is part of ernestdefoe/masthead.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Masthead\Portal;

use ErnestDefoe\Masthead\Settings;
use Flarum\Discussion\Discussion;
use Flarum\Extension\ExtensionManager;
use Flarum\Http\SlugManager;
use Flarum\Post\CommentPost;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use s9e\TextFormatter\Utils;

/**
 * The featured story and the three beside it.
 *
 * Four discussions (or Page Builder articles), the actor's own view of the
 * forum: nothing they could not open from the discussion list.
 *
 * 🚨 Bounded at four, and eager-loaded as a set — the opening posts, their
 * authors and their tags are three queries whatever the source, never one
 * per story.
 */
final class Hero
{
    public const COUNT = 4;

    public function __construct(
        protected Settings $settings,
        protected ExtensionManager $extensions,
        protected SlugManager $slugs,
        protected Images $images,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function for(User $actor): array
    {
        $source = $this->settings->heroSource();

        if ($source === 'pagebuilder' && $this->extensions->isEnabled('ernestdefoe-page-builder')) {
            $articles = (new PageBuilderArticles($this->images))->latest($actor, $this->settings->heroType(), self::COUNT);

            // An empty article list (a new install, a type with nothing
            // published yet) falls through to discussions, so the front page
            // never opens on an empty hero.
            if ($articles !== []) {
                return $articles;
            }
        }

        $stories = $this->discussions($actor, $source)->take(self::COUNT);

        // Top up from the newest discussions when a tag or the sticky list
        // has fewer than four, so the layout is always whole.
        if ($stories->count() < self::COUNT && $source !== 'latest') {
            $stories = $stories->concat(
                $this->query($actor)->whereNotIn('discussions.id', $stories->pluck('id'))
                    ->orderByDesc('discussions.created_at')
                    ->limit(self::COUNT - $stories->count())
                    ->get()
            );
        }

        $stories = new Collection($stories->all());
        $stories->load($this->relations());

        return $stories->map(fn (Discussion $d) => $this->present($d))->values()->all();
    }

    /**
     * @return Collection<int, Discussion>
     */
    protected function discussions(User $actor, string $source): Collection
    {
        $query = $this->query($actor)->limit(self::COUNT);

        if ($source === 'tag' && $this->extensions->isEnabled('flarum-tags') && $tag = $this->settings->heroTag()) {
            $query->whereHas('tags', fn (Builder $q) => $q->where('tags.id', $tag))
                ->orderByDesc('discussions.created_at');
        } elseif ($source === 'sticky' && $this->extensions->isEnabled('flarum-sticky')) {
            $query->where('discussions.is_sticky', true)->orderByDesc('discussions.last_posted_at');
        } else {
            $query->orderByDesc('discussions.created_at');
        }

        return $query->get();
    }

    /**
     * @return Builder<Discussion>
     */
    protected function query(User $actor): Builder
    {
        return Discussion::query()
            ->whereVisibleTo($actor)
            // A moderator sees hidden discussions in lists; a front page
            // featuring one would show everyone else a story that 404s.
            ->whereNull('discussions.hidden_at')
            ->where('discussions.comment_count', '>', 0);
    }

    /**
     * @return list<string>
     */
    protected function relations(): array
    {
        return $this->extensions->isEnabled('flarum-tags')
            ? ['firstPost', 'user', 'tags']
            : ['firstPost', 'user'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function present(Discussion $discussion): array
    {
        $post = $discussion->firstPost;
        $xml = $post instanceof CommentPost ? (string) $post->parsed_content : '';

        $tag = null;
        if ($discussion->relationLoaded('tags')) {
            // The most specific tag: a child before its parent, a primary tag
            // before a secondary one.
            $tag = $discussion->getRelation('tags')
                ->sortBy(fn ($t) => [$t->position === null ? 1 : 0, $t->parent_id === null ? 1 : 0])
                ->first();
        }

        return [
            'kind' => 'discussion',
            'id' => (int) $discussion->id,
            'slug' => $this->slugs->forResource(Discussion::class)->toSlug($discussion),
            'title' => $discussion->title,
            'excerpt' => $this->excerpt($xml),
            'image' => $this->images->first($xml) ?? $this->images->cover($discussion->user),
            'author' => $discussion->user?->display_name,
            'replies' => max(0, (int) $discussion->comment_count - 1),
            'createdAt' => $discussion->created_at?->toAtomString(),
            'tag' => $tag ? ['name' => $tag->name, 'color' => $tag->color ?: null] : null,
        ];
    }

    protected function excerpt(string $xml): string
    {
        if ($xml === '') {
            return '';
        }

        // An image's address is its tag's text, and reads as noise in prose.
        $xml = (string) preg_replace('#<(IMG|UPL-IMAGE-PREVIEW)\b[^>]*>.*?</\1>#s', '', $xml);
        // Headings are left out of an excerpt: in a single line of prose a
        // heading reads as the start of the next sentence ("…at once. How the
        // vote worked Every member…"). Block ends become spaces so the
        // paragraphs either side never run together.
        $xml = (string) preg_replace('#<(H[1-6])\b[^>]*>.*?</\1>#s', '', $xml);
        $xml = (string) preg_replace('#(</(?:p|LI|QUOTE|CODE)>|<br\s*/>)#i', ' $1', $xml);

        try {
            $text = Utils::removeFormatting($xml);
        } catch (\Throwable) {
            // Stored XML that does not parse (an import, a hand edit) costs
            // the story its excerpt, never the front page.
            return '';
        }

        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        return mb_strlen($text) > 180 ? rtrim(mb_substr($text, 0, 177)).'…' : $text;
    }
}
