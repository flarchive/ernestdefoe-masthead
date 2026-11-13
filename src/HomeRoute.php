<?php

/*
 * This file is part of ernestdefoe/masthead.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Masthead;

use Flarum\Extend\ExtenderInterface;
use Flarum\Extend\LifecycleInterface;
use Flarum\Extension\Extension;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;

/**
 * The portal becomes the home page when Masthead is enabled, and stops being
 * it when Masthead is disabled.
 *
 * 🚨 Flarum's own home-page setting (Admin → Basics) is the switch, not a
 * setting of Masthead's. There is one place that decides what `/` shows, and
 * the "keep the discussion list" choice on Masthead's page writes to it.
 *
 * Only the stock value is replaced: a forum whose home is the tags page, or a
 * Page Builder page, keeps it. And disabling hands `/all` back, because a
 * home route that no longer exists would leave `/` on core's fallback rather
 * than on the page the admin last chose. (Core does fall back safely — this
 * just puts it back explicitly.)
 */
final class HomeRoute implements ExtenderInterface, LifecycleInterface
{
    public const PORTAL = '/portal';
    public const LIST = '/all';

    public function extend(Container $container, ?Extension $extension = null): void
    {
    }

    public function onEnable(Container $container, Extension $extension): void
    {
        $settings = $container->make(SettingsRepositoryInterface::class);

        if (in_array($settings->get('default_route'), [null, '', self::LIST], true)) {
            $settings->set('default_route', self::PORTAL);
        }
    }

    public function onDisable(Container $container, Extension $extension): void
    {
        $settings = $container->make(SettingsRepositoryInterface::class);

        if ($settings->get('default_route') === self::PORTAL) {
            $settings->set('default_route', self::LIST);
        }
    }
}
