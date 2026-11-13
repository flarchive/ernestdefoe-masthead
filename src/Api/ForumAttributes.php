<?php

/*
 * This file is part of ernestdefoe/masthead.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Masthead\Api;

use ErnestDefoe\Masthead\ActiveTheme;
use ErnestDefoe\Masthead\Portal\OnlineCount;
use ErnestDefoe\Masthead\Settings;
use Flarum\Api\Context;
use Flarum\Api\Schema;

/**
 * What every page's masthead and footer draw from.
 *
 * 🚨 Getters, not values: they run only when the forum resource is actually
 * serialised (a page load, GET /api), never on the other API requests that
 * merely construct the resource. The one that touches the database is cached
 * for a minute.
 */
final class ForumAttributes
{
    public function __invoke(): array
    {
        return [
            // read by the utility strip; not even the cached count for a
            // visitor Wardrobe is serving another theme
            Schema\Integer::make('mastheadOnline')
                ->visible(fn ($forum, Context $context) => resolve(ActiveTheme::class)->for($context->request))
                ->get(fn () => resolve(OnlineCount::class)->get()),

            // read by the portal's Hero, as a modifier class
            Schema\Str::make('mastheadHero')
                ->get(fn () => resolve(Settings::class)->look()['hero']),

            // read by the footer
            Schema\Arr::make('mastheadFooterLinks')
                ->get(fn () => resolve(Settings::class)->footerLinks()),
        ];
    }
}
