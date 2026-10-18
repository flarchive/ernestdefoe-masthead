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

    // serialised to the forum, drawn by the masthead and the footer
    public const TAGLINE = self::PREFIX.'tagline';
    public const MONOGRAM = self::PREFIX.'monogram';
    public const FOOTER_LINKS = self::PREFIX.'footer_links';
    public const POWERED_BY = self::PREFIX.'powered_by';
    public const SHOW_NAME = self::PREFIX.'show_name';

    // serialised to the forum, read by the portal's TagGrid
    public const TAG_COUNT = self::PREFIX.'tag_count';

    // serialised to the forum, read by the article's lead image (topic/index.tsx)
    public const LEAD_IMAGE = self::PREFIX.'lead_image';

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
     * @return array{accent: string, bar: string, strip: string, hero: string, corners: string, ground: string, groundDark: string}
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
        $look['groundDark'] = Presets::hex($this->settings->get(self::GROUND_DARK)) ?? self::DEFAULT_GROUND_DARK;

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

    public function heroTag(): int
    {
        return (int) $this->settings->get(self::HERO_TAG, 0);
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
     * path on this forum, an http(s) address or mailto survives.
     *
     * @return list<array{label: string, url: string}>
     */
    public function footerLinks(): array
    {
        $decoded = json_decode((string) $this->settings->get(self::FOOTER_LINKS, '[]'), true);
        $links = [];

        foreach (is_array($decoded) ? $decoded : [] as $link) {
            $label = trim((string) ($link['label'] ?? ''));
            $url = self::safeUrl($link['url'] ?? '');

            if ($label !== '' && $url !== null) {
                $links[] = ['label' => mb_substr($label, 0, 60), 'url' => $url];
            }
        }

        return array_slice($links, 0, 12);
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

        return null;
    }
}
