<?php

/*
 * This file is part of ernestdefoe/masthead.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Masthead\Tests\unit;

use ErnestDefoe\Masthead\Portal\Images;
use ErnestDefoe\Masthead\Presets;
use ErnestDefoe\Masthead\Settings;
use Flarum\Settings\OverrideSettingsRepository;
use Flarum\Settings\UninstalledSettingsRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SettingsTest extends TestCase
{
    private function look(array $values): array
    {
        return (new Settings(new OverrideSettingsRepository(new UninstalledSettingsRepository(), $values)))->look();
    }

    #[Test]
    public function the_preset_supplies_everything_by_default()
    {
        $defaults = ['ground' => Settings::DEFAULT_GROUND, 'groundDark' => Settings::DEFAULT_GROUND_DARK, 'darkBrand' => 'off'];

        $this->assertSame(Presets::ALL['gameday'] + $defaults, $this->look([]));
        $this->assertSame(Presets::ALL['royal'] + $defaults, $this->look([Settings::PRESET => 'royal']));
        $this->assertSame(Presets::ALL['gameday'] + $defaults, $this->look([Settings::PRESET => 'nonsense']));
    }

    #[Test]
    public function overrides_lay_over_the_preset()
    {
        $look = $this->look([Settings::PRESET => 'royal', Settings::ACCENT => '#ABCDEF', Settings::BAR => '#123456']);

        $this->assertSame('#abcdef', $look['accent']);
        $this->assertSame('#123456', $look['bar']);
        $this->assertSame('color-mix(in srgb, #123456 78%, #000)', $look['strip']);
        $this->assertSame('overlay', $look['hero']);

        $this->assertSame('var(--primary-color)', $this->look([Settings::ACCENT => 'primary'])['accent']);
    }

    #[Test]
    public function every_defining_colour_takes_an_exact_hex()
    {
        // Mailwright's palette, set colour by colour.
        $look = $this->look([
            Settings::BAR => '#0A1630',
            Settings::STRIP => '#06101F',
            Settings::ACCENT => '#0B57D0',
            Settings::GROUND => '#F5F7FC',
            Settings::GROUND_DARK => '#0B1220',
        ]);

        $this->assertSame(['#0a1630', '#06101f', '#0b57d0', '#f5f7fc', '#0b1220'], [$look['bar'], $look['strip'], $look['accent'], $look['ground'], $look['groundDark']]);
        $this->assertSame('on', $look['darkBrand'], 'An exact dark ground tints the dark-mode cards too');

        // Without an exact strip, it is derived from the masthead colour.
        $this->assertSame('color-mix(in srgb, #0a1630 78%, #000)', $this->look([Settings::BAR => '#0A1630'])['strip']);

        // Nothing but a hex reaches the stylesheet here either.
        $this->assertSame(Settings::DEFAULT_GROUND, $this->look([Settings::GROUND => 'red;} body{display:none'])['ground']);
    }

    #[Test]
    public function nothing_but_a_hex_colour_reaches_the_stylesheet()
    {
        // Interpolated as `@masthead-accent: <value>;` — a value that closed
        // the declaration could append LESS of its own.
        foreach (['red;} body{display:none', '#fff; @import "x"', 'url(x)', '#12345', ''] as $bad) {
            $this->assertSame(Presets::ALL['gameday']['accent'], $this->look([Settings::ACCENT => $bad])['accent'], $bad);
        }
    }

    #[Test]
    public function only_safe_addresses_survive()
    {
        $this->assertSame('/p/1', Settings::safeUrl('/p/1'));
        $this->assertSame('mailto:a@b.c', Settings::safeUrl('mailto:a@b.c'));
        $this->assertNull(Settings::safeUrl('javascript:alert(1)'));
        $this->assertNull(Settings::safeUrl('//evil.example'));
        // Browsers read a backslash after the leading slash as a second slash:
        // "/\\evil.example" is a protocol-relative link off the forum.
        $this->assertNull(Settings::safeUrl('/\\evil.example'));
        $this->assertNull(Settings::safeUrl('data:text/html,x'));

        $this->assertSame('https://x.test/a.png', Images::safe('https://x.test/a.png'));
        $this->assertNull(Images::safe('https://x.test/a.png") , url(evil'));
        $this->assertNull(Images::safe('javascript:alert(1)'));
    }
}
