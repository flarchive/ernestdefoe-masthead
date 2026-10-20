import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import Link from 'flarum/common/components/Link';
import humanTime from 'flarum/common/helpers/humanTime';
import { glyph, tagHref, tone } from './components/TagGrid';
import { has, t } from './util';

/**
 * The Tags page (/tags) in the portal's section tiles, so the forum's
 * sections look the same on the home page and on their own page.
 *
 * Only core's tile list is swapped: the page, its hero, its sidebar and the
 * secondary-tag cloud under the tiles stay core's, and so does the data —
 * the tags the page has already loaded (with their children and latest
 * discussion), so this costs no request.
 *
 * 🚨 A tile is a <div>, not one big link: the subtags and the latest
 * discussion are links of their own, and a link inside a link is invalid
 * HTML that browsers pull apart. The name's link stretches over the tile
 * (tiles.less), so the whole tile still opens the tag.
 */
export default function tagsPage() {
  if (!has('flarum-tags')) return;

  // The page flarum/tags registered for /tags: its own class, reached through
  // the route rather than a build-time import of another extension.
  const TagsPage = (app.routes as any).tags?.component;
  if (!TagsPage?.prototype?.contentItems) return;

  extend(TagsPage.prototype, 'contentItems', function (this: any, items: any) {
    if (app.forum.attribute('mastheadTagsPage') === false || !items.has('tagTiles')) return;

    const pinned = (this.tags || []).filter((tag: any) => tag.position() !== null);

    items.setContent(
      'tagTiles',
      <div className="MastheadTiles MastheadTiles--page">
        {pinned.map((tag: any) => {
          const children = ((tag.children() || []) as any[]).filter(Boolean).sort((a, b) => a.position() - b.position());
          const last = tag.lastPostedDiscussion();

          return (
            <div className="MastheadTile MastheadTile--page" style={tone(tag)}>
              {glyph(tag)}
              <div className="MastheadTile-text">
                <Link className="MastheadTile-name MastheadTile-link" href={tagHref(tag)}>
                  {tag.name()}
                </Link>
                {tag.description() ? <span className="MastheadTile-desc">{tag.description()}</span> : null}
                {children.length ? (
                  <span className="MastheadTile-children" aria-label={t('browse.subtags') as any}>
                    {children.map((child) => (
                      <Link className="MastheadTagChip" href={tagHref(child)} style={tone(child)}>
                        {child.name()}
                      </Link>
                    ))}
                  </span>
                ) : null}
                <span className="MastheadTile-meta">
                  {t('browse.count', { count: tag.discussionCount() || 0 })}
                  {last ? (
                    <>
                      {' · '}
                      <Link className="MastheadTile-last" href={app.route.discussion(last, last.lastPostNumber())}>
                        {t('browse.last_post', { title: last.title() })}
                      </Link>
                      {' · '}
                      {humanTime(last.lastPostedAt())}
                    </>
                  ) : null}
                </span>
              </div>
            </div>
          );
        })}
      </div>
    );
  });
}
