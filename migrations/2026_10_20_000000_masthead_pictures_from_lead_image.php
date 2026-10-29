<?php

/*
 * This file is part of ernestdefoe/masthead.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Builder;

/*
 * "Show a lead image on discussions" (a yes/no) became Pictures, a choice of
 * four. A forum that had switched the lead image off asked for no pictures,
 * so it gets Off; one that left it on keeps the default, where the post's own
 * picture falls back to the author's Backdrop cover. A forum that never saved
 * it has nothing to carry over.
 *
 * 🚨 The settings table is reached through the schema's connection, so the
 * table prefix is applied (raw SQL would not be).
 */
return [
    'up' => function (Builder $schema) {
        $settings = fn () => $schema->getConnection()->table('settings');
        $old = $settings()->where('key', 'ernestdefoe-masthead.lead_image')->value('value');

        if ($old === null) {
            return;
        }

        if (! $settings()->where('key', 'ernestdefoe-masthead.pictures')->exists()) {
            $settings()->insert([
                'key' => 'ernestdefoe-masthead.pictures',
                'value' => in_array($old, ['0', '', 'false'], true) ? 'off' : 'both',
            ]);
        }

        $settings()->where('key', 'ernestdefoe-masthead.lead_image')->delete();
    },

    'down' => function (Builder $schema) {
        $settings = fn () => $schema->getConnection()->table('settings');
        $mode = $settings()->where('key', 'ernestdefoe-masthead.pictures')->value('value');

        if ($mode === null) {
            return;
        }

        $settings()->updateOrInsert(
            ['key' => 'ernestdefoe-masthead.lead_image'],
            ['value' => $mode === 'off' ? '0' : '1']
        );
        $settings()->where('key', 'ernestdefoe-masthead.pictures')->delete();
    },
];
