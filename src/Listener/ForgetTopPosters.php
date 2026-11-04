<?php

/*
 * This file is part of ernestdefoe/masthead.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Masthead\Listener;

use ErnestDefoe\Masthead\Portal\TopPosters;

/**
 * The cached top-posters list carries names and avatars. A member who renames
 * themselves, changes their avatar or is deleted should not linger in it
 * under the old one for up to ten minutes.
 */
final class ForgetTopPosters
{
    public function __construct(protected TopPosters $topPosters)
    {
    }

    public function __invoke(): void
    {
        $this->topPosters->forget();
    }
}
