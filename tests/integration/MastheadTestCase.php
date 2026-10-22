<?php

/*
 * This file is part of ernestdefoe/masthead.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Masthead\Tests\integration;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Tags\Tag;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;

/**
 * Users: 1 admin, 2 normal, 3 member.
 * Tags: 1 General (public), 2 Staff (restricted to admins).
 * Discussions:
 *   1 General, by 2, a Markdown image and text           (newest)
 *   2 General, by 3, a fof/upload image
 *   3 Staff,   by 1, restricted — never shown to a guest
 *   4 General, by 2, hidden — never featured, even to an admin
 *   5 General, by 3, private — never shown to a guest
 *   6 General, by 2, sticky, plain text                  (oldest).
 */
abstract class MastheadTestCase extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags', 'flarum-sticky', 'ernestdefoe-masthead');

        $at = fn (int $hoursAgo) => Carbon::now()->subHours($hoursAgo);
        $d = fn (int $id, int $user, int $hoursAgo, array $extra = []) => $extra + [
            'id' => $id, 'title' => "Story $id", 'slug' => "story-$id", 'created_at' => $at($hoursAgo), 'last_posted_at' => $at($hoursAgo),
            'user_id' => $user, 'first_post_id' => $id, 'comment_count' => 3, 'participant_count' => 1,
        ];
        $p = fn (int $id, int $user, int $hoursAgo, string $xml, array $extra = []) => $extra + [
            'id' => $id, 'discussion_id' => $id, 'number' => 1, 'created_at' => $at($hoursAgo), 'user_id' => $user, 'type' => 'comment', 'content' => $xml,
        ];

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'third', 'email' => 'third@machine.local', 'is_email_confirmed' => 1, 'last_seen_at' => Carbon::now()],
            ],
            Tag::class => [
                ['id' => 1, 'name' => 'General', 'slug' => 'general', 'position' => 0, 'color' => '#1d4b8f'],
                ['id' => 2, 'name' => 'Staff', 'slug' => 'staff', 'position' => 1, 'is_restricted' => true],
            ],
            'group_permission' => [
                ['group_id' => Group::MEMBER_ID, 'permission' => 'tag1.viewForum'],
                ['group_id' => Group::GUEST_ID, 'permission' => 'tag1.viewForum'],
            ],
            Discussion::class => [
                $d(1, 2, 1),
                $d(2, 3, 2),
                $d(3, 1, 3),
                $d(4, 2, 4, ['hidden_at' => Carbon::now()]),
                $d(5, 3, 5, ['is_private' => true]),
                $d(6, 2, 6, ['is_sticky' => true]),
            ],
            'discussion_tag' => [
                ['discussion_id' => 1, 'tag_id' => 1], ['discussion_id' => 2, 'tag_id' => 1], ['discussion_id' => 3, 'tag_id' => 2],
                ['discussion_id' => 4, 'tag_id' => 1], ['discussion_id' => 5, 'tag_id' => 1], ['discussion_id' => 6, 'tag_id' => 1],
            ],
            Post::class => [
                $p(1, 2, 1, '<r><H2><s>## </s>Week nine</H2><p>Rankings. <IMG src="https://example.com/a.png?x=1&amp;y=2"><s>![](</s>https://example.com/a.png?x=1&amp;y=2<e>)</e></IMG></p></r>'),
                $p(2, 3, 2, '<r><p>Recruiting. <UPL-IMAGE-PREVIEW url="https://cdn.example.com/b.jpg" uuid="u1">[upl-image-preview url=https://cdn.example.com/b.jpg]</UPL-IMAGE-PREVIEW></p></r>'),
                $p(3, 1, 3, '<t><p>Staff only</p></t>'),
                $p(4, 2, 4, '<t><p>Hidden</p></t>'),
                $p(5, 3, 5, '<t><p>Private</p></t>', ['is_private' => true]),
                $p(6, 2, 6, '<r><p>Plain text. <IMG src="javascript:alert(1)"><s>![](</s>javascript:alert(1)<e>)</e></IMG></p></r>'),
            ],
        ]);
    }

    /** @return array{0: int, 1: mixed} */
    protected function get(string $path, ?int $actor = null): array
    {
        $response = $this->send($this->request('GET', $path, $actor ? ['authenticatedAs' => $actor] : []));

        return [$response->getStatusCode(), json_decode((string) $response->getBody(), true)];
    }
}
