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
 * "Last reply by <name>" (Ben, Discord): the featured stories carry who
 * wrote the last reply, eager-loaded for the set; Latest discussions asks
 * core's endpoint for lastPostedUser, as core's own list does.
 */
class LastReplyTest extends MastheadTestCase
{
    private function lastPosters(): void
    {
        // 1 was last replied to by "third" (3), 2 by the normal user (2); 6
        // has no replies.
        $db = $this->database();
        $db->table('discussions')->where('id', 1)->update(['last_posted_user_id' => 3]);
        $db->table('discussions')->where('id', 2)->update(['last_posted_user_id' => 2]);
        $db->table('discussions')->where('id', 6)->update(['last_posted_user_id' => 2, 'comment_count' => 1]);
    }

    private function setLastReply(string $value): void
    {
        $this->app()->getContainer()->make(SettingsRepositoryInterface::class)->set('ernestdefoe-masthead.show_last_reply', $value);
    }

    /** @return array<int, mixed> story id => lastReply */
    private function heroLastReply(?int $actor = null): array
    {
        [$status, $body] = $this->get('/api/masthead/portal', $actor);
        $this->assertSame(200, $status, json_encode($body));

        return array_column($body['hero'], 'lastReply', 'id');
    }

    private function queries(callable $run): int
    {
        $db = $this->database();
        $db->flushQueryLog();
        $db->enableQueryLog();
        $run();
        $n = count($db->getQueryLog());
        $db->disableQueryLog();

        return $n;
    }

    #[Test]
    public function the_featured_stories_say_who_replied_last()
    {
        $this->app();
        $this->lastPosters();

        [, $forum] = $this->get('/api');
        $this->assertTrue($forum['data']['attributes']['mastheadLastReply']);

        $last = $this->heroLastReply();
        $this->assertSame('third', $last[1]['name']);
        $this->assertSame('third', $last[1]['slug']);
        $this->assertNotEmpty($last[1]['at']);
        $this->assertSame('normal', $last[2]['name']);
        $this->assertNull($last[6], 'No replies: nobody to name');
    }

    #[Test]
    public function the_setting_turns_it_off()
    {
        $this->app();
        $this->lastPosters();
        $this->setLastReply('0');

        [, $forum] = $this->get('/api');
        $this->assertFalse($forum['data']['attributes']['mastheadLastReply']);
        $this->assertSame([null], array_values(array_unique($this->heroLastReply(), SORT_REGULAR)));
    }

    #[Test]
    public function the_featured_stories_load_the_last_posters_in_one_query()
    {
        $this->app();
        $this->lastPosters();
        $this->setLastReply('0');
        $this->heroLastReply(1);

        $off = $this->queries(fn () => $this->heroLastReply(1));
        $this->setLastReply('1');
        $this->assertSame('third', $this->heroLastReply(1)[1]['name'], 'The setting took effect');
        $on = $this->queries(fn () => $this->heroLastReply(1));

        $this->assertSame($off + 1, $on, 'One query for every story\'s last poster, not one each');
    }

    #[Test]
    public function latest_discussions_costs_the_same_for_one_row_as_for_twenty()
    {
        $this->app();
        $this->lastPosters();

        $list = fn (int $limit) => $this->get('/api/discussions?include=user,tags,lastPostedUser&sort=-lastPostedAt&page[limit]='.$limit, 1);
        $list(1);

        [$status, $body] = $list(20);
        $this->assertSame(200, $status);
        $users = array_column(array_filter($body['included'], fn ($r) => $r['type'] === 'users'), 'attributes', 'id');
        $this->assertSame('third', $users['3']['username']);

        $this->assertSame($this->queries(fn () => $list(1)), $this->queries(fn () => $list(20)));
    }
}
