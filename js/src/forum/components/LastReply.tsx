import app from 'flarum/forum/app';
import Link from 'flarum/common/components/Link';
import humanTime from 'flarum/common/helpers/humanTime';
import { t } from '../util';

/**
 * "Last reply by <name> 3 hours ago", the name a link to their profile.
 *
 * 🚨 The cards it sits in are not links themselves (a link cannot hold
 * another): their title link stretches over the card, and this one is lifted
 * above it (.MastheadCard in portal/layout.less).
 */
export default function lastReply(name: string, slug: string, at: Date | null) {
  return (
    <span className="MastheadLastReply">
      {t('last_reply', {
        name: (
          <Link className="MastheadLastReply-user" href={app.route('user', { username: slug })}>
            {name}
          </Link>
        ),
        time: at ? humanTime(at) : '',
      })}
    </span>
  );
}

/** Whether the admin shows who replied last (serialised as mastheadLastReply). */
export function showLastReply(): boolean {
  return app.forum.attribute('mastheadLastReply') !== false;
}
