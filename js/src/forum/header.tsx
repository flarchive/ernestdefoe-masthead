import app from 'flarum/forum/app';
import { extend, override } from 'flarum/common/extend';
import HeaderPrimary from 'flarum/forum/components/HeaderPrimary';
import HeaderSecondary from 'flarum/forum/components/HeaderSecondary';
import IndexSidebar from 'flarum/forum/components/IndexSidebar';
import Footer from 'flarum/forum/components/Footer';
import Navigation from 'flarum/common/components/Navigation';
import GlobalSearch from 'flarum/forum/components/GlobalSearch';
import Button from 'flarum/common/components/Button';
import LinkButton from 'flarum/common/components/LinkButton';
import extractText from 'flarum/common/utils/extractText';
import Strip from './components/Strip';
import Brand from './components/Brand';
import MastheadFooter from './components/MastheadFooter';
import { canStart, startDiscussion } from './components/compose';
import { has, isPhone, onPortal, t } from './util';

/** The keys HeaderSecondary keeps on the masthead row; the rest go to the strip. */
const ROW = ['search', 'mastheadCompose'];

export default function header() {
  /*
   * The strip and the title block are mounted once, next to core's own
   * header mounts. The strip's node is created here: core's template has no
   * slot above the header, and adding one server-side would need a view of
   * Masthead's own that every core update could drift from.
   */
  extend(app as any, 'mount', () => {
    const headerEl = document.getElementById('header');
    const titleEl = headerEl?.querySelector('.Header-title');

    if (headerEl && !document.getElementById('masthead-strip')) {
      const strip = document.createElement('div');
      strip.id = 'masthead-strip';
      headerEl.insertBefore(strip, headerEl.firstChild);
      m.mount(strip, Strip);
    }

    if (titleEl) m.mount(titleEl, Brand);
  });

  extend(HeaderSecondary.prototype, 'items', function (this: HeaderSecondary, items) {
    if (isPhone()) return;

    if ((this.attrs as any).strip) {
      ROW.forEach((key) => items.has(key) && items.remove(key));

      return;
    }

    items.toArray().forEach((item: any) => {
      if (!ROW.includes(item.itemName)) items.remove(item.itemName);
    });

    if (canStart()) {
      items.add(
        'mastheadCompose',
        <Button className="Button Button--primary MastheadCompose" icon="fas fa-pen" onclick={startDiscussion}>
          {t('compose')}
        </Button>,
        -10
      );
    }
  });

  /*
   * The main navigation, when ernestdefoe/header-nav is not installed.
   *
   * Header Nav owns HeaderPrimary when it is there — its editor decides what
   * goes in the header, and drawing Masthead's own links beside its would put
   * everything there twice. Without it, the header carries Home and the
   * forum's own nav links (All Discussions, Tags, whatever extensions add),
   * read from the sidebar's list so they match it exactly.
   */
  extend(HeaderPrimary.prototype, 'items', function (items) {
    if (has('ernestdefoe-header-nav') || isPhone()) return;

    items.add(
      'mastheadHome',
      <LinkButton className="Button Button--link MastheadNav-link" href={app.forum.attribute<string>('basePath') + '/'} icon="fas fa-house">
        {t('nav.home')}
      </LinkButton>,
      100
    );

    let nav: any[] = [];
    try {
      const sidebar = Object.create(IndexSidebar.prototype);
      sidebar.attrs = {};
      nav = IndexSidebar.prototype.navItems.call(sidebar).toArray();
    } catch (e) {
      // A nav extension that cannot be called outside its page must not take
      // the header with it.
    }

    nav
      .filter((v: any) => v?.itemName && typeof v.attrs?.href === 'string' && !/^(separator|moreTags|tag\d+)$/.test(v.itemName))
      // Home already covers the front page.
      .filter((v: any) => v.attrs.href !== app.forum.attribute<string>('basePath') + '/')
      .forEach((v: any, i: number) => {
        items.add(
          'masthead-' + v.itemName,
          <LinkButton className="Button Button--link MastheadNav-link" href={v.attrs.href} icon={v.attrs.icon}>
            {extractText(v.children) || v.itemName}
          </LinkButton>,
          90 - i
        );
      });
  });

  /*
   * The phone's top bar on the portal: the drawer button, then the brand,
   * then search — as the mockup has it. Every other page keeps core's bar,
   * whose middle belongs to the page's own title control.
   */
  extend(Navigation.prototype, 'items', function (this: Navigation, items) {
    if (!isPhone() || !(this.attrs as any).drawer || !onPortal()) return;

    items.add('mastheadBrand', <Brand />, 85);
    items.add(
      'mastheadSearch',
      <div className="MastheadNav-search">
        <GlobalSearch state={app.search.state} />
      </div>,
      -100
    );
  });

  // An override that keeps the original: core's footer is empty, but an
  // extension may have put something in it, and that stays above Masthead's.
  override(Footer.prototype, 'view', function (original) {
    return [original(), <MastheadFooter />];
  });
}
