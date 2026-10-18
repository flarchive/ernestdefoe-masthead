<?php

/*
 * This file is part of ernestdefoe/masthead.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use ErnestDefoe\Masthead\ActiveTheme;
use ErnestDefoe\Masthead\Api;
use ErnestDefoe\Masthead\Content;
use ErnestDefoe\Masthead\HomeRoute;
use ErnestDefoe\Masthead\Listener\ForgetTopPosters;
use ErnestDefoe\Masthead\Presets;
use ErnestDefoe\Masthead\Settings;
use Flarum\Api\Resource\ForumResource;
use Flarum\Extend;
use Flarum\Frontend\Document;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Event as UserEvent;
use Illuminate\Contracts\Filesystem\Factory as Filesystem;
use Psr\Http\Message\ServerRequestInterface;

/*
 * The three colours, compiled into the stylesheet.
 *
 * 🚨 Each variable is computed from the WHOLE look (preset plus overrides),
 * not from the one setting it is registered against. Core recompiles the
 * stylesheet only when a key registered as a LESS variable is saved, so the
 * three registrations are really there to put the preset, the accent and the
 * bar on that watch list — and whichever of them is saved, every variable
 * comes out right. Registering the accent against its override alone, and
 * reading only that, would mean a new preset changed nothing until something
 * else happened to force a rebuild.
 */
$look = fn (string $part) => fn () => resolve(Settings::class)->look()[$part];

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less')
        ->route(HomeRoute::PORTAL, 'masthead.portal', Content\Portal::class)
        /*
         * Stamped on <html> by the server, not set from JS after boot, so the
         * first paint already has the right corners and fonts.
         *
         * 🚨 CLOSURES, not strings. Core calls any value is_callable() says
         * yes to, and that is true of a string naming a PHP function — Warren
         * learned it from the word `compact`. A closure can never be mistaken
         * for one.
         */
        ->content(function (Document $document, ServerRequestInterface $request) {
            // A visitor Wardrobe is serving another theme gets none of this.
            if (! resolve(ActiveTheme::class)->for($request)) {
                return;
            }

            $settings = resolve(Settings::class);
            $look = $settings->look();
            $fonts = (bool) resolve(SettingsRepositoryInterface::class)->get(Settings::FONTS, true);

            $document->extraAttributes['data-masthead-corners'] = fn () => $look['corners'];
            $document->extraAttributes['data-masthead-fonts'] = fn () => $fonts ? 'on' : 'off';

            // Mobile browsers tint their own toolbar from theme-color. Core
            // sets it to the primary colour; under a navy masthead that is a
            // green (or whatever) strip glued to the top of the page.
            $document->meta['theme-color'] = $look['bar'];

            if ($fonts) {
                // The two faces every page uses above the fold. The rest load
                // on demand, by unicode-range, only when a page needs them.
                $assets = resolve(Filesystem::class)->disk('flarum-assets');

                foreach (['figtree-latin-wght-normal', 'barlow-condensed-latin-800-normal'] as $face) {
                    $document->head[] = '<link rel="preload" href="'.e($assets->url('extensions/ernestdefoe-masthead/fonts/'.$face.'.woff2')).'" as="font" type="font/woff2" crossorigin>';
                }
            }
        }),

    /*
     * Where the fonts are, as an absolute URL baked into the stylesheet.
     *
     * 🚨 Not a relative url(). The stylesheet is not always served from
     * /assets/: ernestdefoe/wardrobe serves each theme's compiled CSS from
     * its own path, and a relative URL resolved against that 404s every font.
     * The flarum-assets disk knows the real public URL (a CDN, a subfolder).
     */
    (new Extend\Theme())
        ->addCustomLessVariable('masthead-fonts', fn () => '"'.resolve(Filesystem::class)->disk('flarum-assets')->url('extensions/ernestdefoe-masthead/fonts/').'"'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less')
        // The preset table, for the admin's swatches. Sent to the admin page
        // only, from the one copy the stylesheet is compiled from, so the two
        // can never disagree about what "Evergreen" looks like.
        ->content(function (Document $document) {
            $document->payload['mastheadPresets'] = Presets::ALL;
        }),

    new Extend\Locales(__DIR__.'/locale'),

    new HomeRoute(),

    (new Extend\Routes('api'))
        ->get('/masthead/portal', 'masthead.portal', Api\PortalController::class)
        ->get('/masthead/top-posters', 'masthead.top-posters', Api\TopPostersController::class),

    (new Extend\Settings())
        ->default(Settings::PRESET, 'gameday')
        ->default(Settings::FONTS, true)
        ->default(Settings::HERO_SOURCE, 'latest')
        ->default(Settings::HERO_TYPE, 'articles')
        ->default(Settings::POWERED_BY, true)
        ->default(Settings::TAG_COUNT, 12)
        ->default(Settings::LEAD_IMAGE, true)

        // read by less/lib/tokens.less
        ->registerLessConfigVar('masthead-accent', Settings::ACCENT, $look('accent'))
        ->registerLessConfigVar('masthead-bar', Settings::BAR, $look('bar'))
        ->registerLessConfigVar('masthead-strip', Settings::STRIP, $look('strip'))
        ->registerLessConfigVar('masthead-ground', Settings::GROUND, $look('ground'))
        ->registerLessConfigVar('masthead-ground-dark', Settings::GROUND_DARK, $look('groundDark'))
        ->registerLessConfigVar('masthead-dark-brand', Settings::GROUND_DARK, $look('darkBrand'))
        // Unused in the LESS itself; registered so that saving a new preset
        // recompiles the stylesheet (see the note on $look above).
        ->registerLessConfigVar('masthead-preset', Settings::PRESET, fn () => '0')

        // read by the masthead (logo tile and tagline)
        ->serializeToForum('mastheadTagline', Settings::TAGLINE)
        ->serializeToForum('mastheadMonogram', Settings::MONOGRAM)
        // read by the footer
        ->serializeToForum('mastheadPoweredBy', Settings::POWERED_BY, 'boolval')
        // read by Brand
        // null until an admin sets it: the name then shows only when there
        // is no logo. A value saved before 1.0.1 (true or false) still wins.
        ->serializeToForum('mastheadShowName', Settings::SHOW_NAME, fn ($v) => $v === null || $v === '' ? null : (bool) $v)
        // read by the article's lead image
        ->serializeToForum('mastheadLeadImage', Settings::LEAD_IMAGE, 'boolval')
        // read by the portal's TagGrid, clamped there and here
        ->serializeToForum('mastheadTagCount', Settings::TAG_COUNT, fn ($v) => max(1, min(48, (int) $v))),

    (new Extend\ApiResource(ForumResource::class))
        ->fields(Api\ForumAttributes::class),

    (new Extend\Event())
        ->listen(UserEvent\Renamed::class, ForgetTopPosters::class)
        ->listen(UserEvent\AvatarChanged::class, ForgetTopPosters::class)
        ->listen(UserEvent\Deleted::class, ForgetTopPosters::class),
];
