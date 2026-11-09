import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import Link from 'flarum/common/components/Link';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Avatar from 'flarum/common/components/Avatar';
import humanTime from 'flarum/common/helpers/humanTime';
import formatNumber from 'flarum/common/utils/formatNumber';
import { isPhone, t } from '../util';

type Tab = 'latest' | 'top';

const LIMIT = 8;

/*
 * Module-level, like the portal data: switching tabs, or coming back to the
 * front page, shows what was fetched rather than asking again. A list older
 * than a minute is fetched afresh on the next visit.
 */
const lists: Partial<Record<Tab, { items: any[]; at: number }>> = {};
const pending: Partial<Record<Tab, Promise<void>>> = {};
let tab: Tab = 'latest';

function day(offset: number): string {
  const d = new Date();
  d.setDate(d.getDate() + offset);

  return d.toISOString().slice(0, 10);
}

/**
 * 🚨 Core's own discussion endpoint, nothing of Masthead's. It is what the
 * discussion list asks for, so every extension's visibility rules and eager
 * loads already apply, and the rows land in the shared store as real
 * Discussion models.
 */
function load(which: Tab): Promise<void> {
  const cached = lists[which];
  if (cached && Date.now() - cached.at < 60_000) return Promise.resolve();

  const params: any = { page: { limit: LIMIT }, include: 'user,tags' };

  if (which === 'top') {
    params.sort = '-commentCount';
    // "This week": started in the last seven days. Tomorrow is the upper
    // bound because the range compares against midnight.
    params.filter = { created: `${day(-7)}..${day(1)}` };
  } else {
    params.sort = '-lastPostedAt';
  }

  return (pending[which] ||= app.store
    .find<any[]>('discussions', params)
    .then((items) => {
      lists[which] = { items: items as any[], at: Date.now() };
    })
    .finally(() => {
      delete pending[which];
      m.redraw();
    }));
}

function primaryTag(discussion: any): any {
  const tags = (discussion.tags?.() || []).filter(Boolean);

  return tags.find((tag: any) => tag.isChild()) || tags.find((tag: any) => tag.position() !== null) || tags[0] || null;
}

export default class LatestPanel extends Component {
  oninit(vnode: any) {
    super.oninit(vnode);
    load(tab);
  }

  view() {
    const list = lists[tab];
    const phone = isPhone();

    return (
      <section className="MastheadPanel MastheadLatest" aria-labelledby="masthead-latest">
        <div className="MastheadPanel-head">
          <h2 id="masthead-latest" className="MastheadHeading">
            {t('latest.title')}
          </h2>
          <div className="MastheadTabs" role="tablist">
            {(['latest', 'top'] as Tab[]).map((key) => (
              <button
                type="button"
                role="tab"
                aria-selected={tab === key ? 'true' : 'false'}
                className={'MastheadTab' + (tab === key ? ' is-active' : '')}
                onclick={() => {
                  tab = key;
                  load(key);
                }}
              >
                {t(`latest.tab_${key}`)}
              </button>
            ))}
          </div>
        </div>

        {!list ? (
          <LoadingIndicator />
        ) : list.items.length ? (
          <ul className="MastheadRows" role="tabpanel">
            {list.items.map((discussion) => this.row(discussion, phone))}
          </ul>
        ) : (
          <p className="MastheadEmpty">{t(`latest.empty_${tab}`)}</p>
        )}

        <Link className="MastheadPanel-foot" href={app.route('index')}>
          {t('latest.all')}
        </Link>
      </section>
    );
  }

  row(discussion: any, phone: boolean) {
    const tag = primaryTag(discussion);
    const user = discussion.user();
    const replies = Math.max(0, (discussion.commentCount() || 1) - 1);
    const when = humanTime(discussion.lastPostedAt() || discussion.createdAt());

    return (
      <li key={discussion.id()}>
        <Link className="MastheadRow" href={app.route.discussion(discussion)}>
          <Avatar user={user} className="MastheadRow-avatar" />
          <span className="MastheadRow-main">
            <span className="MastheadRow-title">{discussion.title()}</span>
            <span className="MastheadRow-meta">
              {tag ? (
                <span className="MastheadTagChip" style={tag.color() ? { '--mh-tag': tag.color() } : undefined}>
                  {tag.name()}
                </span>
              ) : null}
              <span>
                {user ? [user.displayName(), ' · '] : null}
                {when}
                {phone ? [' · ', t('latest.replies', { count: replies })] : null}
              </span>
            </span>
          </span>
          {phone ? null : (
            <span className="MastheadRow-count">
              {formatNumber(replies)}
              <small>{t('latest.replies_label', { count: replies })}</small>
            </span>
          )}
        </Link>
      </li>
    );
  }
}
