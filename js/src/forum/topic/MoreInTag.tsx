import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import Link from 'flarum/common/components/Link';
import humanTime from 'flarum/common/helpers/humanTime';
import { t } from '../util';

/*
 * One request per tag, kept for five minutes and shared by every discussion
 * in that tag: reading three threads in News asks once, not three times, and
 * nothing here is ever asked per post.
 */
const lists = new Map<string, { at: number; items: any[] | null; pending?: Promise<void> }>();
const TTL = 5 * 60_000;

function load(slug: string) {
  const entry = lists.get(slug);
  if (entry && (entry.pending || Date.now() - entry.at < TTL)) return;

  const next: { at: number; items: any[] | null; pending?: Promise<void> } = { at: Date.now(), items: entry?.items ?? null };
  next.pending = app.store
    .find<any[]>('discussions', { filter: { tag: slug }, sort: '-lastPostedAt', page: { limit: 6 } })
    .then((items) => {
      next.items = items as any[];
    })
    .catch(() => {
      next.items ||= [];
    })
    .finally(() => {
      delete next.pending;
      m.redraw();
    });
  lists.set(slug, next);
}

/** "More in <tag>": the five latest other discussions in the primary tag. */
export default class MoreInTag extends Component<{ discussion: any; primary: any }> {
  view() {
    // 🚨 `primary`, never `tag`: Mithril reads an attribute called `tag` as a
    // vnode and the component receives nothing.
    const { discussion, primary: tag } = this.attrs;

    load(tag.slug());

    const items = (lists.get(tag.slug())?.items || []).filter((d) => d !== discussion).slice(0, 5);

    if (!items.length) return null;

    return (
      <section className="MastheadWidget MastheadWidget--banded MastheadMore" aria-labelledby="masthead-more">
        <h2 id="masthead-more" className="MastheadWidget-title">
          {t('topic.more_in', { tag: tag.name() })}
        </h2>
        <ul className="MastheadMore-list">
          {items.map((d) => (
            <li key={d.id()}>
              <Link className="MastheadMore-item" href={app.route.discussion(d)}>
                <span className="MastheadMore-title">{d.title()}</span>
                <span className="MastheadMore-meta">
                  {t('latest.replies', { count: Math.max(0, (d.commentCount() || 1) - 1) })} · {humanTime(d.lastPostedAt() || d.createdAt())}
                </span>
              </Link>
            </li>
          ))}
        </ul>
      </section>
    );
  }
}
