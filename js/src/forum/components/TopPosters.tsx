import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import Link from 'flarum/common/components/Link';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import stringToColor from 'flarum/common/utils/stringToColor';
import { getPosters, loadPosters } from '../portalData';
import { t } from '../util';

/**
 * The week's five busiest posters.
 *
 * The same component on the portal, in a Bespoke zone and in a
 * fof/forum-widgets-core section; the data behind it is one cached query on
 * the server and one shared copy here.
 */
export default class TopPosters extends Component<{ heading?: boolean }> {
  oninit(vnode: any) {
    super.oninit(vnode);
    if (!getPosters()) loadPosters();
  }

  view() {
    const posters = getPosters();

    return (
      <div className="MastheadPosters">
        {this.attrs.heading === false ? null : <h2 className="MastheadWidget-title">{t('top_posters.title')}</h2>}
        {!posters ? (
          <LoadingIndicator size="small" />
        ) : posters.length ? (
          <ol className="MastheadPosters-list">
            {posters.map((p, i) => (
              <li key={p.id}>
                <Link className="MastheadPoster" href={app.route('user', { username: p.slug })}>
                  <span className="MastheadPoster-rank">{i + 1}</span>
                  {p.avatarUrl ? (
                    <img className="Avatar MastheadPoster-avatar" src={p.avatarUrl} alt="" loading="lazy" />
                  ) : (
                    <span className="Avatar MastheadPoster-avatar" style={{ '--avatar-bg': '#' + stringToColor(p.displayName) }}>
                      {p.displayName.charAt(0).toUpperCase()}
                    </span>
                  )}
                  <span className="MastheadPoster-name">{p.displayName}</span>
                  <span className="MastheadPoster-count">{t('top_posters.posts', { count: p.posts })}</span>
                </Link>
              </li>
            ))}
          </ol>
        ) : (
          <p className="MastheadEmpty">{t('top_posters.empty')}</p>
        )}
      </div>
    );
  }
}
