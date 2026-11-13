<?php

/*
 * This file is part of ernestdefoe/masthead.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Masthead;

use Flarum\Settings\SettingsRepositoryInterface;

/**
 * Masthead's settings, read once and typed.
 *
 * 🚨 Every key has a reader, named beside it. A setting that is saved and
 * never read is a control that lies.
 */
final class Settings
{
    public const PREFIX = 'ernestdefoe-masthead.';

    // read by the LESS variables in extend.php, through Settings::look()
    public const PRESET = self::PREFIX.'preset';
    public const ACCENT = self::PREFIX.'accent';
    public const BAR = self::PREFIX.'bar';
    public const STRIP = self::PREFIX.'strip';
    public const GROUND = self::PREFIX.'ground';
    public const GROUND_DARK = self::PREFIX.'ground_dark';

    /** The light-mode page ground when none is set: the mockup's grey. */
    public const DEFAULT_GROUND = '#f3f4f6';

    /**
     * The dark-mode page ground when none is set: derived from core's own
     * dark body colour, so a forum that changed that in Appearance keeps its
     * shade rather than Masthead's.
     */
    public const DEFAULT_GROUND_DARK = 'color-mix(in srgb, var(--body-bg) 82%, #000000)';

    // read by Settings::look(), serialised as mastheadHero / mastheadCorners
    public const HERO_STYLE = self::PREFIX.'hero_style';

    // read by Portal\Hero
    public const HERO_SOURCE = self::PREFIX.'hero_source';
    public const HERO_TAG = self::PREFIX.'hero_tag';
    public const HERO_TYPE = self::PREFIX.'hero_type';
    // off: a source with fewer than four keeps to its own; on: the newest fill the rest
    public const HERO_FILL = self::PREFIX.'hero_fill';

    // serialised to the forum, drawn by the masthead and the footer
    public const TAGLINE = self::PREFIX.'tagline';
    public const MONOGRAM = self::PREFIX.'monogram';
    public const FOOTER_LINKS = self::PREFIX.'footer_links';
    public const POWERED_BY = self::PREFIX.'powered_by';
    public const SHOW_NAME = self::PREFIX.'show_name';

    // serialised to the forum, read by the portal's TagGrid
    public const TAG_COUNT = self::PREFIX.'tag_count';

    // serialised to the forum, read by the discussion page's scrubber rail
    public const SCRUBBER = self::PREFIX.'scrubber';

    // serialised to the forum, read by the header: keep the bar in view
    // while the page scrolls, on computers and tablets
    public const PIN_HEADER = self::PREFIX.'pin_header';

    // serialised to the forum: where the opening post's fof/polls polls go
    // on wide screens, "post" (as fof/polls draws them) or "sidebar"
    public const POLLS = self::PREFIX.'polls';

    // serialised to the forum (with fof/discussion-views enabled), read by
    // the portal, the discussion page and the featured stories: view counts
    public const SHOW_VIEWS = self::PREFIX.'show_views';

    // serialised to the forum, read by Latest discussions and the featured
    // stories: who wrote the last reply, and when
    public const SHOW_LAST_REPLY = self::PREFIX.'show_last_reply';

    /**
     * Which pictures the featured stories and a discussion's lead banner use:
     * the post's own picture falling back to the author's Backdrop cover, the
     * post's picture only, the Backdrop cover only, or none.
     *
     * Read by Settings::pictures() (Portal\Images) and serialised to the forum
     * as mastheadPictures (topic/index.tsx). Replaced the lead_image boolean
     * in 1.1.0; the migration carries a saved value over.
     */
    public const PICTURES = self::PREFIX.'pictures';

    public const PICTURE_MODES = ['both', 'image', 'backdrop', 'off'];

    // serialised to the forum, read by the portal's Hero: the tag on each story
    public const HERO_TAGS = self::PREFIX.'hero_tags';

    // serialised to the forum, read by the tags page (tagsPage.tsx)
    public const TAGS_PAGE = self::PREFIX.'tags_page';

    // read by the font stamp in extend.php and the [data-masthead-fonts] rules
    public const FONTS = self::PREFIX.'fonts';

    public const SOURCES = ['latest', 'tag', 'sticky', 'pagebuilder'];

    public function __construct(protected SettingsRepositoryInterface $settings)
    {
    }

    /**
     * The resolved look: the preset, with each override that is set laid
     * over it.
     *
     * Every colour that defines the look can be set to an exact hex: the
     * accent, the masthead, the utility strip, and the page ground in light
     * and dark mode. Anything left empty comes from the preset (or, for the
     * ground, from the theme's defaults).
     *
     * @return array{accent: string, bar: string, strip: string, hero: string, corners: string, ground: string, groundDark: string, darkBrand: string}
     */
    public function look(): array
    {
        $look = Presets::get($this->settings->get(self::PRESET));

        $accent = (string) $this->settings->get(self::ACCENT, '');
        if ($accent === 'primary') {
            // Flarum's own primary colour, chosen in Appearance. A CSS
            // variable, not a value, so it follows that setting (and its dark
            // variant) without Masthead recompiling anything.
            $look['accent'] = 'var(--primary-color)';
        } elseif ($hex = Presets::hex($accent)) {
            $look['accent'] = $hex;
        }

        if ($bar = Presets::hex($this->settings->get(self::BAR))) {
            $look['bar'] = $bar;
            // The utility strip is the masthead, a shade darker. Derived in
            // CSS from the bar so a custom colour never pairs with a preset's
            // strip that was picked for a different one.
            $look['strip'] = 'color-mix(in srgb, '.$bar.' 78%, #000)';
        }

        // An exact strip colour beats the one derived from the masthead.
        if ($strip = Presets::hex($this->settings->get(self::STRIP))) {
            $look['strip'] = $strip;
        }

        $look['ground'] = Presets::hex($this->settings->get(self::GROUND)) ?? self::DEFAULT_GROUND;
        $groundDark = Presets::hex($this->settings->get(self::GROUND_DARK));
        $look['groundDark'] = $groundDark ?? self::DEFAULT_GROUND_DARK;
        // An exact dark ground is a brand colour: the dark-mode surfaces are
        // then derived from it too (tokens.less), so a navy ground does not
        // carry Flarum's neutral grey cards.
        $look['darkBrand'] = $groundDark ? 'on' : 'off';

        $hero = (string) $this->settings->get(self::HERO_STYLE, '');
        if (in_array($hero, Presets::HERO_STYLES, true)) {
            $look['hero'] = $hero;
        }

        return $look;
    }

    public function heroSource(): string
    {
        $source = (string) $this->settings->get(self::HERO_SOURCE, 'latest');

        return in_array($source, self::SOURCES, true) ? $source : 'latest';
    }

    public function pictures(): string
    {
        $mode = (string) $this->settings->get(self::PICTURES, 'both');

        return in_array($mode, self::PICTURE_MODES, true) ? $mode : 'both';
    }

    public function heroTag(): int
    {
        return (int) $this->settings->get(self::HERO_TAG, 0);
    }

    public function heroFill(): bool
    {
        return (bool) $this->settings->get(self::HERO_FILL, false);
    }

    public function showViews(): bool
    {
        return (bool) $this->settings->get(self::SHOW_VIEWS, true);
    }

    public function showLastReply(): bool
    {
        return (bool) $this->settings->get(self::SHOW_LAST_REPLY, true);
    }

    public function heroType(): string
    {
        return (string) ($this->settings->get(self::HERO_TYPE) ?: 'articles');
    }

    /**
     * The footer's links, each one checked before it can reach an href.
     *
     * 🚨 An admin types these, and they are drawn on every page for every
     * visitor. A `javascript:` URL there is script on every page, so only a
     * path on this forum, an http(s) address, mailto or a #fragment (a
     * placeholder while a site is being built, or an anchor on the page)
     * survives.
     *
     * A link to a social site gets that site's brand icon, or the admin's
     * own icon; one with an icon and no name is drawn as the icon alone,
     * named after the site for screen readers.
     *
     * @return list<array{label: string, url: string, icon?: string, iconOnly?: true}>
     */
    public function footerLinks(): array
    {
        $decoded = json_decode((string) $this->settings->get(self::FOOTER_LINKS, '[]'), true);
        $links = [];

        foreach (is_array($decoded) ? $decoded : [] as $link) {
            $label = trim((string) ($link['label'] ?? ''));
            $url = self::safeUrl($link['url'] ?? '');

            if ($url === null) {
                continue;
            }

            [$icon, $site] = self::linkIcon($url, (string) ($link['icon'] ?? ''));

            if ($label === '' && $icon === null) {
                continue;
            }

            $row = ['label' => mb_substr($label !== '' ? $label : $site, 0, 60), 'url' => $url];

            if ($icon !== null) {
                $row['icon'] = $icon;
            }

            if ($label === '') {
                $row['iconOnly'] = true;
            }

            $links[] = $row;
        }

        return array_slice($links, 0, 12);
    }

    /** Sites whose links get their brand icon: host => [icon, name]. */
    private const BRANDS = [
        'facebook.com' => ['fab fa-facebook', 'Facebook'],
        'fb.com' => ['fab fa-facebook', 'Facebook'],
        'x.com' => ['fab fa-x-twitter', 'X'],
        'twitter.com' => ['fab fa-x-twitter', 'X'],
        'instagram.com' => ['fab fa-instagram', 'Instagram'],
        'threads.net' => ['fab fa-threads', 'Threads'],
        'threads.com' => ['fab fa-threads', 'Threads'],
        'bsky.app' => ['fab fa-bluesky', 'Bluesky'],
        'youtube.com' => ['fab fa-youtube', 'YouTube'],
        'youtu.be' => ['fab fa-youtube', 'YouTube'],
        'tiktok.com' => ['fab fa-tiktok', 'TikTok'],
        'discord.gg' => ['fab fa-discord', 'Discord'],
        'discord.com' => ['fab fa-discord', 'Discord'],
        'reddit.com' => ['fab fa-reddit', 'Reddit'],
        'github.com' => ['fab fa-github', 'GitHub'],
        'linkedin.com' => ['fab fa-linkedin', 'LinkedIn'],
        'twitch.tv' => ['fab fa-twitch', 'Twitch'],
        'pinterest.com' => ['fab fa-pinterest', 'Pinterest'],
        't.me' => ['fab fa-telegram', 'Telegram'],
        'telegram.me' => ['fab fa-telegram', 'Telegram'],
        'wa.me' => ['fab fa-whatsapp', 'WhatsApp'],
        'whatsapp.com' => ['fab fa-whatsapp', 'WhatsApp'],
        'snapchat.com' => ['fab fa-snapchat', 'Snapchat'],
        'spotify.com' => ['fab fa-spotify', 'Spotify'],
        'patreon.com' => ['fab fa-patreon', 'Patreon'],
        'steamcommunity.com' => ['fab fa-steam', 'Steam'],
        'steampowered.com' => ['fab fa-steam', 'Steam'],
        'mastodon.social' => ['fab fa-mastodon', 'Mastodon'],
    ];

    /**
     * A link's icon and the name of its site: the admin's icon when it is a
     * Font Awesome class (and nothing else, since it lands in a class
     * attribute), else the brand of a known social site, else none.
     *
     * @return array{0: ?string, 1: string}
     */
    public static function linkIcon(string $url, string $icon): array
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $host = preg_replace('/^(www|m|mobile)\./', '', $host) ?? $host;
        $brand = null;

        foreach (self::BRANDS as $domain => $known) {
            if ($host === $domain || str_ends_with($host, '.'.$domain)) {
                $brand = $known;
                break;
            }
        }

        $icon = trim(preg_replace('/\s+/', ' ', $icon) ?? '');

        if (! preg_match('/^(fa[srb]|fa-(solid|regular|brands)) fa-[a-z0-9-]{1,40}$/', $icon)) {
            $icon = $brand[0] ?? null;
        }

        return [$icon, $brand[1] ?? ($host ?: $url)];
    }

    public static function safeUrl(mixed $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '' || mb_strlen($url) > 500) {
            return null;
        }

        // A path on this forum: one slash, not followed by a second one or by
        // a backslash, which browsers read as a second slash ("/\\x.com" is
        // a link to x.com).
        if (preg_match('#^/(?![/\\\\])#', $url) || preg_match('#^(https?://|mailto:)#i', $url)) {
            return $url;
        }

        // A fragment: "#" alone, or an anchor id. Nothing that could leave
        // the attribute or the page.
        if (preg_match('/^#[A-Za-z0-9_\-:.]*$/', $url)) {
            return $url;
        }

        return null;
    }
}
