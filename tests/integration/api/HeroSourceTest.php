<?php

/*
 * This file is part of ernestdefoe/masthead.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Masthead\Tests\integration\api;

use Carbon\Carbon;
use ErnestDefoe\Masthead\Tests\integration\MastheadTestCase;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Tags\Tag;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * A chosen source features only its own stories (RelicSystem, d/40113/7:
 * the forum's one discussion, not in "Frontpage", led a Frontpage hero).
 *
 * The fixture's discussions 1, 2 and 6 are visible to guests, none of them
 * in tag 3. The stories added here are 10 onwards, newest first.
 */
class HeroSourceTest extends MastheadTestCase
{
    private function portal(?int $actor = null): array
    {
        [$status, $body] = $this->get('/api/masthead/portal', $actor);
        $this->assertSame(200, $status, json_encode($body));

        return $body;
    }

    private function ids(?int $actor = null): array
    {
        return array_column($this->portal($actor)['hero'], 'id');
    }

    /** "Frontpage" as a primary, child or secondary tag, open to guests. */
    private function frontpage(string $kind): void
    {
        $this->prepareDatabase([
            Tag::class => [[
                'id' => 3, 'name' => 'Frontpage', 'slug' => 'frontpage',
                'position' => $kind === 'secondary' ? null : 0,
                'parent_id' => $kind === 'child' ? 1 : null,
            ]],
        ]);
        $this->setting('ernestdefoe-masthead.hero_source', 'tag');
        $this->setting('ernestdefoe-masthead.hero_tag', '3');
    }

    /** @return list<int> the ids added, newest first */
    private function stories(int $count, array $tags, array $extra = []): array
    {
        $ids = [];

        for ($i = 0; $i < $count; $i++) {
            $id = 10 + $i;
            // Minutes ago, so every added story is newer than the fixture's.
            $at = Carbon::now()->subMinutes($i + 1);
            $ids[] = $id;

            $this->prepareDatabase([
                Discussion::class => [$extra + [
                    'id' => $id, 'title' => "Added $id", 'slug' => "added-$id", 'created_at' => $at, 'last_posted_at' => $at,
                    'user_id' => 2, 'first_post_id' => $id, 'comment_count' => 1, 'participant_count' => 1,
                ]],
                Post::class => [['id' => $id, 'discussion_id' => $id, 'number' => 1, 'created_at' => $at, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>Added</p></t>']],
                'discussion_tag' => array_map(fn ($tag) => ['discussion_id' => $id, 'tag_id' => $tag], $tags),
            ]);
        }

        return $ids;
    }

    public static function tagMatrix(): array
    {
        $cases = [];

        foreach (['primary', 'child', 'secondary'] as $kind) {
            foreach ([0, 1, 3, 5] as $count) {
                $cases["$kind tag, $count matching"] = [$kind, $count];
            }
        }

        return $cases;
    }

    #[Test]
    #[DataProvider('tagMatrix')]
    public function the_tag_source_features_only_that_tag(string $kind, int $count)
    {
        $this->frontpage($kind);
        // A child tag's discussions also carry the parent, as Flarum stores them.
        $ids = $this->stories($count, $kind === 'child' ? [1, 3] : ($kind === 'secondary' ? [1, 3] : [3]));
        $expected = array_slice($ids, 0, 4);

        $this->assertSame($expected, $this->ids(), 'guest');
        $this->assertSame($expected, $this->ids(1), 'admin');
    }

    #[Test]
    public function an_empty_tag_hides_the_hero_and_tells_only_an_admin()
    {
        $this->frontpage('primary');

        $guest = $this->portal();
        $this->assertSame([], $guest['hero']);
        $this->assertNull($guest['heroEmpty']);

        $this->assertNull($this->portal(2)['heroEmpty'], 'A member is not told either');

        $admin = $this->portal(1);
        $this->assertSame([], $admin['hero']);
        $this->assertSame(['source' => 'tag', 'name' => 'Frontpage'], $admin['heroEmpty']);
    }

    #[Test]
    public function a_deleted_tag_features_nothing_and_says_so_to_an_admin()
    {
        $this->setting('ernestdefoe-masthead.hero_source', 'tag');
        $this->setting('ernestdefoe-masthead.hero_tag', '99');

        $this->assertSame([], $this->ids());
        $this->assertSame(['source' => 'tag', 'name' => null], $this->portal(1)['heroEmpty']);
    }

    #[Test]
    public function a_restricted_tag_is_never_featured_to_someone_who_cannot_see_it()
    {
        $this->setting('ernestdefoe-masthead.hero_source', 'tag');
        $this->setting('ernestdefoe-masthead.hero_tag', '2');

        $this->assertSame([], $this->ids(), 'guest');
        $this->assertSame([], $this->ids(2), 'member');
        $this->assertSame([3], $this->ids(1), 'admin');
    }

    #[Test]
    #[DataProvider('counts')]
    public function the_sticky_source_features_only_sticky_discussions(int $count)
    {
        $this->setting('ernestdefoe-masthead.hero_source', 'sticky');
        // Unstick the fixture's sticky discussion so the count is exact.
        $ids = $this->stories($count, [1], ['is_sticky' => true]);
        $this->database()->table('discussions')->where('id', 6)->update(['is_sticky' => false]);

        $this->assertSame(array_slice($ids, 0, 4), $this->ids());
    }

    public static function counts(): array
    {
        return ['0' => [0], '1' => [1], '3' => [3], '5' => [5]];
    }

    #[Test]
    public function the_newest_fill_the_gaps_only_when_the_admin_asks()
    {
        $this->frontpage('primary');
        $this->setting('ernestdefoe-masthead.hero_fill', '1');
        [$id] = $this->stories(1, [3]);

        $this->assertSame([$id, 1, 2, 6], $this->ids());
    }

    #[Test]
    public function an_empty_source_is_filled_when_the_admin_asks()
    {
        $this->frontpage('primary');
        $this->setting('ernestdefoe-masthead.hero_fill', '1');

        $this->assertSame([1, 2, 6], $this->ids());
    }

    #[Test]
    public function a_source_whose_extension_is_off_is_the_newest_discussions()
    {
        // Page Builder is not installed here: the choice cannot be honoured.
        $this->setting('ernestdefoe-masthead.hero_source', 'pagebuilder');

        $this->assertSame([1, 2, 6], $this->ids());
        $this->assertNull($this->portal(1)['heroEmpty']);
    }

    #[Test]
    public function a_tag_source_with_no_tag_picked_is_the_newest_discussions()
    {
        $this->setting('ernestdefoe-masthead.hero_source', 'tag');

        $this->assertSame([1, 2, 6], $this->ids());
    }
}
