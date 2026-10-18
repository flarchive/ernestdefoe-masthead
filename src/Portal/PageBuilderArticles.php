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
 * Page Builder articles as the hero, when Page Builder is enabled.
 *
 * 🚨 A soft integration: Page Builder is never required and none of its
 * classes is imported. The names are strings, checked with class_exists, so
 * a forum without it never autoloads anything of its, and a version whose
 * internals moved costs the hero its source (Hero falls back to
 * discussions), never the page.
 */
final class PageBuilderArticles
{
    private const RECORD = 'Ernestdefoe\\PageBuilder\\Content\\ContentRecord';
    private const TYPE = 'Ernestdefoe\\PageBuilder\\Content\\ContentType';
    private const ACCESS = 'Ernestdefoe\\PageBuilder\\Content\\ContentAccess';

    public function __construct(protected Images $images)
    {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function latest(User $actor, string $typeSlug, int $count): array
    {
        if (! class_exists(self::RECORD) || ! class_exists(self::TYPE) || ! class_exists(self::ACCESS)) {
            return [];
        }

        try {
            $type = call_user_func([self::TYPE, 'query'])->where('slug', $typeSlug)->first();

            // The type's own view permission, the one Page Builder's listing
            // enforces. A members-only article type stays off a guest's front
            // page.
            if (! $type || ! call_user_func([self::ACCESS, 'canView'], $actor, $type)) {
                return [];
            }

            $records = call_user_func([self::RECORD, 'query'])
                ->where('content_type_id', $type->id)
                ->where('status', 'published')
                ->with('user')
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->limit($count)
                ->get();
        } catch (\Throwable) {
            return [];
        }

        $rows = [];

        foreach ($records as $record) {
            $rows[] = [
                'kind' => 'article',
                'id' => (int) $record->id,
                'url' => '/c/'.rawurlencode($type->slug).'/'.rawurlencode($record->slug),
                'title' => (string) $record->title,
                'excerpt' => (string) ($record->excerpt(180) ?? ''),
                'image' => Images::safe($record->coverUrl()) ?? $this->images->cover($record->user),
                'author' => $record->user?->display_name,
                'replies' => null,
                'createdAt' => $record->published_at?->toAtomString(),
                'tag' => ['name' => (string) $type->name, 'color' => null],
            ];
        }

        return $rows;
    }
}
