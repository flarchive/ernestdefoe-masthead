<?php

/*
 * This file is part of ernestdefoe/masthead.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Masthead;

/**
 * The named looks an admin picks from.
 *
 * A preset is a starting point, not a lock: each value it sets can be
 * overridden on its own (accent, masthead colour, hero layout), and an
 * override that is cleared falls back to the preset again. That is what lets
 * one dropdown restyle the whole forum without wiping the one colour an admin
 * changed by hand.
 *
 * 🚨 Every key here has a reader. `accent`, `bar` and `strip` are compiled into
 * the stylesheet as LESS variables (extend.php); `hero` and `corners` reach the
 * page as forum attributes and become classes on the portal and on <html>.
 */
final class Presets
{
    public const DEFAULT = 'gameday';

    /** @var array<string, array{accent: string, bar: string, strip: string, hero: string, corners: string}> */
    public const ALL = [
        // The mockup: navy masthead, red rule, a full-bleed featured story.
        'gameday' => ['accent' => '#c8322a', 'bar' => '#0e2340', 'strip' => '#0a1a30', 'hero' => 'overlay', 'corners' => 'soft'],
        // Charcoal and orange; the feature sits beside its text, not under it.
        'sunday' => ['accent' => '#d97a00', 'bar' => '#1b1f24', 'strip' => '#121519', 'hero' => 'split', 'corners' => 'soft'],
        // Deep green, for clubs and outdoors communities.
        'evergreen' => ['accent' => '#1f7a4d', 'bar' => '#0f2a22', 'strip' => '#0a1e18', 'hero' => 'overlay', 'corners' => 'soft'],
        // Purple on midnight, for gaming and creative boards.
        'royal' => ['accent' => '#6b3fb5', 'bar' => '#1d1640', 'strip' => '#140f2e', 'hero' => 'overlay', 'corners' => 'soft'],
        // A newspaper: black masthead, square corners, a split feature.
        'broadsheet' => ['accent' => '#c8322a', 'bar' => '#14161a', 'strip' => '#0b0c0e', 'hero' => 'split', 'corners' => 'square'],
    ];

    public const HERO_STYLES = ['overlay', 'split', 'off'];

    public static function get(?string $name): array
    {
        return self::ALL[$name] ?? self::ALL[self::DEFAULT];
    }

    /**
     * A colour the stylesheet can take verbatim.
     *
     * 🚨 These values are interpolated into LESS as `@name: <value>;`. Only a
     * plain #rgb / #rrggbb survives this, so nothing an admin types can close
     * the declaration and append LESS of its own.
     */
    public static function hex(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i', trim($value)) ? strtolower(trim($value)) : null;
    }
}
