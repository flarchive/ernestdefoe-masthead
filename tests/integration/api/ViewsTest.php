<?php

/*
 * This file is part of ernestdefoe/masthead.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Masthead\Tests\integration\api;

use ErnestDefoe\Masthead\Tests\integration\MastheadTestCase;
use Flarum\Settings\SettingsRepositoryInterface;
use PHPUnit\Framework\Attributes\Test;

/**
 * View counts (Ben, Discord): fof/discussion-views keeps them, the Importer
 * fills them, and Masthead shows them beside the reply counts. Masthead
 * counts nothing itself: the featured stories read the column already on
 * each row, and the forum JS reads fof's own `views` attribute.
 */
class ViewsTest extends MastheadTestCase
{
    private function withViews(): void
    {
        $this->extension('fof-discussion-views');
        $this->database()->table('discussions')->where('id', 1)->update(['view_count' => 1234]);
        $this->database()->table('discussions')->where('id', 2)->update(['view_count' => 7]);
    }

    /** Once the app is up (withViews has booted it), straight to the repository. */
    private function setViews(string $value): void
    {
        $this->app()->getContainer()->make(SettingsRepositoryInterface::class)->set('ernestdefoe-masthead.show_views', $value);
    }

    private function forumViews(): bool
    {
        [$status, $body] = $this->get('/api');
        $this->assertSame(200, $status);

        return $body['data']['attributes']['mastheadViews'];
    }

    /** @return array<int, int|null> story id => views */
    private function heroViews(?int $actor = null): array
    {
        [$status, $body] = $this->get('/api/masthead/portal', $actor);
        $this->assertSame(200, $status, json_encode($body));

        return array_column($body['hero'], 'views', 'id');
    }

    #[Test]
    public function without_fof_discussion_views_there_is_nothing_to_show()
    {
        $this->assertFalse($this->forumViews());
        $this->assertSame([null], array_values(array_unique($this->heroViews())));
    }

    #[Test]
    public function the_featured_stories_carry_their_view_counts()
    {
        $this->withViews();

        $this->assertTrue($this->forumViews());

        $views = $this->heroViews();
        $this->assertSame(1234, $views[1]);
        $this->assertSame(7, $views[2]);
        $this->assertSame(0, $views[6], 'A discussion nobody has viewed shows 0, not nothing');
    }

    #[Test]
    public function the_setting_turns_them_off()
    {
        $this->withViews();
        $this->setViews('0');

        $this->assertFalse($this->forumViews());
        $this->assertSame([null], array_values(array_unique($this->heroViews())));
    }

    #[Test]
    public function the_counts_cost_no_query()
    {
        $this->withViews();
        $this->setViews('0');
        $this->heroViews(1);

        $count = function (): int {
            $db = $this->database();
            $db->flushQueryLog();
            $db->enableQueryLog();
            $this->heroViews(1);
            $n = count($db->getQueryLog());
            $db->disableQueryLog();

            return $n;
        };

        $off = $count();
        $this->setViews('1');
        $this->assertSame(1234, $this->heroViews(1)[1], 'The setting took effect');
        $on = $count();

        $this->assertSame($off, $on, 'Showing the counts adds no query to the portal');
    }
}
