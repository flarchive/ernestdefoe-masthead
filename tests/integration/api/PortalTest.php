<?php

/*
 * This file is part of ernestdefoe/masthead.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Masthead\Tests\integration\api;

use ErnestDefoe\Masthead\Tests\integration\MastheadTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class PortalTest extends MastheadTestCase
{
    private function hero(?int $actor = null): array
    {
        [$status, $body] = $this->get('/api/masthead/portal', $actor);
        $this->assertSame(200, $status, json_encode($body));

        return $body['hero'];
    }

    #[Test]
    public function a_guest_sees_only_what_a_guest_may_open()
    {
        // 3 is in a restricted tag, 4 is hidden, 5 is private.
        $this->assertSame([1, 2, 6], array_column($this->hero(), 'id'));
    }

    #[Test]
    public function an_admin_sees_restricted_stories_but_never_a_hidden_one()
    {
        $ids = array_column($this->hero(1), 'id');

        $this->assertContains(3, $ids);
        $this->assertNotContains(4, $ids, 'A hidden discussion would 404 for everyone else');
    }

    #[Test]
    public function pictures_come_from_markdown_and_fof_upload_and_unsafe_ones_are_dropped()
    {
        $hero = collect($this->hero())->keyBy('id');

        $this->assertSame('https://example.com/a.png?x=1&y=2', $hero[1]['image']);
        $this->assertSame('https://cdn.example.com/b.jpg', $hero[2]['image']);
        $this->assertNull($hero[6]['image'], 'A javascript: address never reaches an img src');
    }

    #[Test]
    #[DataProvider('noOwnPictureModes')]
    public function pictures_off_and_backdrop_only_drop_the_posts_own_pictures(string $mode)
    {
        // Backdrop is not installed here, so "Backdrop only" has nothing to
        // show and must not fall back to the post's picture.
        $this->setting('ernestdefoe-masthead.pictures', $mode);

        $this->assertSame([null], array_values(array_unique(array_column($this->hero(), 'image'))));
    }

    public static function noOwnPictureModes(): array
    {
        return [['off'], ['backdrop']];
    }

    #[Test]
    public function pictures_image_only_keeps_the_posts_own_picture()
    {
        $this->setting('ernestdefoe-masthead.pictures', 'image');

        $this->assertSame('https://example.com/a.png?x=1&y=2', collect($this->hero())->keyBy('id')[1]['image']);
    }

    #[Test]
    public function the_excerpt_is_the_text_without_the_image_address()
    {
        $hero = collect($this->hero())->keyBy('id');

        // The heading is left out; the prose after it is the excerpt.
        $this->assertSame('Rankings.', $hero[1]['excerpt']);
        $this->assertSame(2, $hero[1]['replies']);
        $this->assertSame(['name' => 'General', 'color' => '#1d4b8f'], $hero[1]['tag']);
        $this->assertSame('1-story-1', $hero[1]['slug']);
    }

    #[Test]
    public function the_sticky_source_features_sticky_discussions_only()
    {
        $this->setting('ernestdefoe-masthead.hero_source', 'sticky');

        $this->assertSame([6], array_column($this->hero(), 'id'));
    }

    #[Test]
    public function the_tag_source_draws_from_that_tag_only_when_the_actor_can_see_it()
    {
        $this->setting('ernestdefoe-masthead.hero_source', 'tag');
        $this->setting('ernestdefoe-masthead.hero_tag', '2');

        $this->assertSame([3], array_column($this->hero(1), 'id'));
        $this->assertSame([], $this->hero(), 'Nothing from another tag stands in for it');
    }

    #[Test]
    public function top_posters_count_only_posts_a_guest_could_read()
    {
        [$status, $body] = $this->get('/api/masthead/top-posters');
        $this->assertSame(200, $status);

        // User 2: posts 1 and 6 (4 is hidden). User 3: post 2 (5 is private).
        // User 1: post 3 only, in the restricted tag, so not listed at all.
        $this->assertSame(
            [[2, 2], [3, 1]],
            array_map(fn ($row) => [$row['id'], $row['posts']], $body['topPosters'])
        );
    }

    #[Test]
    public function a_forum_guests_cannot_view_gives_them_nothing()
    {
        $this->database()->table('group_permission')->where('group_id', 2)->where('permission', 'viewForum')->delete();

        [$status] = $this->get('/api/masthead/portal');
        $this->assertSame(403, $status);

        [$status] = $this->get('/api/masthead/top-posters');
        $this->assertSame(403, $status);
    }
}
