import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import HeaderSecondary from 'flarum/forum/components/HeaderSecondary';
import { isPhone, t } from '../util';

/**
 * The utility strip above the masthead: today's date, how many members are
 * online, and the visitor's own controls (log in / sign up, or the
 * notifications and user menu).
 *
 * 🚨 The controls are core's own HeaderSecondary, drawn here with `strip` set
 * so it keeps everything except the search. The masthead row draws the same
 * component without the flag and keeps ONLY the search — one list, split in
 * two, so an extension that adds a header control lands in the strip
 * without knowing Masthead exists, and nothing is drawn twice.
 *
 * On a phone there is no strip: core moves its header into the drawer, and
 * HeaderSecondary keeps every item there.
 */
export default class Strip extends Component {
  view() {
    if (isPhone()) return null;

    const online = app.forum.attribute<number>('mastheadOnline') || 0;

    return (
      <div className="MastheadStrip">
        <div className="container MastheadStrip-inner">
          <span className="MastheadStrip-date">{dayjs().format('dddd, LL')}</span>
          {online ? (
            <span className="MastheadStrip-online">
              <span className="MastheadStrip-dot" aria-hidden="true" />
              {t('strip.online', { count: online })}
            </span>
          ) : null}
          <div className="MastheadStrip-controls">
            <HeaderSecondary strip />
          </div>
        </div>
      </div>
    );
  }
}
