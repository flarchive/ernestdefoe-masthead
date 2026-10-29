import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import Link from 'flarum/common/components/Link';
import Icon from 'flarum/common/components/Icon';
import humanTime from 'flarum/common/helpers/humanTime';
import { t } from '../util';

/**
 * The forum's sections, from the tags the forum payload already carries.
 *
 * 🚨 No request and no query: flarum/tags sends every top-level tag the actor
 * may see, with its count and last activity, in the payload of every page.
 * Drawing from the store is what keeps this flat at 1 tag or 40.
 */
export function primaryTags(): any[] {
  return (app.store.all('tags') as any[])
    .filter((tag) => tag.position() !== null && !tag.isChild() && !tag.isHidden())
    .sort((a, b) => a.position() - b.position());
}

export function tone(tag: any) {
  return tag.color() ? { '--mh-tag': tag.color() } : undefined;
}

export function tagHref(tag: any) {
  return app.route('tag', { tags: tag.slug() });
}

export function glyph(tag: any) {
  return (
    <span className="MastheadTile-icon" aria-hidden="true">
      {tag.icon() ? <Icon name={tag.icon()} /> : (tag.name() || '?').charAt(0)}
    </span>
  );
}

export class TagGrid extends Component<{ limit: number }> {
  view() {
    const tags = primaryTags();

    if (!tags.length) return null;

    return (
      <section className="MastheadSection MastheadBrowse" aria-labelledby="masthead-browse">
        <div className="MastheadSection-head">
          <h2 id="masthead-browse" className="MastheadHeading">
            {t('browse.title')}
          </h2>
          {app.routes.tags ? (
            <Link className="MastheadSection-more" href={app.route('tags')}>
              {t('browse.all')}
            </Link>
          ) : null}
        </div>

        <div className="MastheadTiles">
          {tags.slice(0, this.attrs.limit).map((tag) => {
            const last = tag.lastPostedAt();

            return (
              <Link className="MastheadTile" href={tagHref(tag)} style={tone(tag)}>
                {glyph(tag)}
                <span className="MastheadTile-text">
                  <span className="MastheadTile-name">{tag.name()}</span>
                  {tag.description() ? <span className="MastheadTile-desc">{tag.description()}</span> : null}
                  <span className="MastheadTile-meta">
                    {t('browse.count', { count: tag.discussionCount() || 0 })}
                    {last ? [' · ', t('browse.latest', { time: humanTime(last) })] : null}
                  </span>
                </span>
              </Link>
            );
          })}
        </div>
      </section>
    );
  }
}

/** The phone's version: one scrolling row of chips. */
export class TagChips extends Component {
  view() {
    const tags = primaryTags();

    if (!tags.length) return null;

    return (
      <nav className="MastheadChips" aria-label={t('browse.title')}>
        {tags.map((tag) => (
          <Link className="MastheadChip" href={tagHref(tag)} style={tone(tag)}>
            {tag.name()}
          </Link>
        ))}
      </nav>
    );
  }
}
