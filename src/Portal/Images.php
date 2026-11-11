<?php

/*
 * This file is part of ernestdefoe/masthead.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Masthead\Portal;

use ErnestDefoe\Masthead\Settings;
use Flarum\Extension\ExtensionManager;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;

/**
 * A picture for a story, from what the forum already stores.
 *
 * Read from the opening post's stored XML, never by rendering it: a Markdown
 * or BBCode image is an IMG element, a fof/upload image a UPL-IMAGE-PREVIEW,
 * and an editor that stores HTML keeps a lowercase img. No request, no
 * rendering, no column of Masthead's own.
 */
final class Images
{
    public function __construct(protected ExtensionManager $extensions, protected Container $container, protected Settings $settings)
    {
    }

    /**
     * A story's picture, as the Pictures setting asks: its own picture with
     * the author's Backdrop cover behind it (the default), either one alone,
     * or none (the striped placeholder).
     *
     * The cover is only looked up when the mode can use it.
     */
    public function pick(?string $own, ?User $author): ?string
    {
        return match ($this->settings->pictures()) {
            'off' => null,
            'image' => $own,
            'backdrop' => $this->cover($author),
            default => $own ?? $this->cover($author),
        };
    }

    public function first(string $xml): ?string
    {
        if ($xml === '' || ! preg_match('/<(?:IMG|img|UPL-IMAGE-PREVIEW)\b[^>]*?\s(?:src|url)="([^"]+)"/', $xml, $match)) {
            return null;
        }

        return self::safe(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5));
    }

    /**
     * The author's Backdrop profile cover, the picture of last resort.
     *
     * A column on the users row that is already loaded, so it costs nothing.
     */
    public function cover(?User $user): ?string
    {
        $class = 'Ernestdefoe\\Backdrop\\CoverStore';

        if (! $user || ! $user->getAttribute('backdrop_cover') || ! $this->extensions->isEnabled('ernestdefoe-backdrop') || ! class_exists($class)) {
            return null;
        }

        $url = $this->container->make($class)->url($user->getAttribute('backdrop_cover'));

        return is_string($url) ? self::safe($url) : null;
    }

    /**
     * 🚨 It lands in a style attribute's url() and in an img src. Only an
     * http(s) address or a path on this forum gets through, and never one with
     * a quote, bracket or backslash that could close the value.
     */
    public static function safe(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '' || preg_match('/["\'()\\\\\s<>]/', $url)) {
            return null;
        }

        return preg_match('#^(https?://|/(?!/))#i', $url) ? $url : null;
    }
}
