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
use Flarum\Tags\Tag;
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
        $source = $this->source();
        $fill = $this->settings->heroFill();

        if ($source === 'pagebuilder') {
            $articles = (new PageBuilderArticles($this->images))->latest($actor, $this->settings->heroType(), self::COUNT);

            // The admin chose articles: only articles are featured, unless
            // they asked for the gaps to be filled.
            if ($articles !== [] || ! $fill) {
                return $articles;
            }

            $source = 'latest';
        }

        $stories = $this->discussions($actor, $source);

        // 🚨 Only when the admin asked for it: a tag or the sticky list with
        // fewer than four keeps to its own stories otherwise (RelicSystem,
        // d/40113/7 — an untagged discussion led a "Frontpage" hero).
        if ($fill && $source !== 'latest' && $stories->count() < self::COUNT) {
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
     * What an admin is told when the chosen source has nothing to feature:
     * the source and, for a tag or a content type, its name. Null for
     * everyone else, and for the newest discussions (the latest panel
     * already says the forum is empty).
     *
     * @return array{source: string, name: string|null}|null
     */
    public function emptyHint(User $actor): ?array
    {
        $source = $this->source();

        if (! $actor->isAdmin() || $source === 'latest') {
            return null;
        }

        $name = match ($source) {
            'tag' => Tag::query()->whereKey($this->settings->heroTag())->value('name'),
            'pagebuilder' => $this->settings->heroType(),
            default => null,
        };

        return ['source' => $source, 'name' => $name];
    }

    /**
     * The source the admin chose, or the newest discussions when the
     * extension behind it is off or no tag has been picked: a choice that
     * cannot be honoured at all is no choice.
     */
    protected function source(): string
    {
        $source = $this->settings->heroSource();

        return match ($source) {
            'tag' => $this->extensions->isEnabled('flarum-tags') && $this->settings->heroTag() ? 'tag' : 'latest',
            'sticky' => $this->extensions->isEnabled('flarum-sticky') ? 'sticky' : 'latest',
            'pagebuilder' => $this->extensions->isEnabled('ernestdefoe-page-builder') ? 'pagebuilder' : 'latest',
            default => 'latest',
        };
    }

    /**
     * @return Collection<int, Discussion>
     */
    protected function discussions(User $actor, string $source): Collection
    {
        $query = $this->query($actor)->limit(self::COUNT);

        if ($source === 'tag') {
            $query->whereHas('tags', fn (Builder $q) => $q->where('tags.id', $this->settings->heroTag()))
                ->orderByDesc('discussions.created_at');
        } elseif ($source === 'sticky') {
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
            'image' => $this->images->pick($this->images->first($xml), $discussion->user),
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
