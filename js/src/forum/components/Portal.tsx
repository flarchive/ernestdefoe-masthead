import app from 'flarum/forum/app';
import Page from 'flarum/common/components/Page';
import ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import Hero from './Hero';
import { TagChips, TagGrid } from './TagGrid';
import LatestPanel from './LatestPanel';
import WidgetSlot from './WidgetSlot';
import Games from './Games';
import ComposeFab from './ComposeFab';
import { getPortal, loadPortal } from '../portalData';
import { has, isPhone, markPortal } from '../util';

/**
 * The front page: the hero, the forum's sections, the latest discussions and
 * a sidebar.
 *
 * Extensions add to the sidebar through `sidebarItems()`, exactly as they add
 * to any of core's ItemLists, or to `WidgetSlot` to appear on the discussion
 * page as well:
 *
 *   extend(MastheadPortal.prototype, 'sidebarItems', (items) => items.add('mine', <Mine />, 50));
 */
export default class MastheadPortal extends Page {
  oninit(vnode: Mithril.Vnode<any, this>) {
    super.oninit(vnode);

    this.bodyClass = 'App--masthead-portal';
    app.history.push('masthead.portal', app.forum.attribute<string>('title'));
    app.setTitle('');
    app.setTitleCount(0);

    loadPortal();
  }

  view() {
    const data = getPortal();
    const phone = isPhone();

    return (
      <div className="MastheadPortal">
        <div className="container">
          {data ? (
            <Hero stories={data.hero} style={app.forum.attribute<string>('mastheadHero') || 'overlay'} />
          ) : (
            <div className="MastheadHero MastheadHero--loading" aria-hidden="true" />
          )}

          {has('flarum-tags') ? phone ? <TagChips /> : <TagGrid limit={app.forum.attribute<number>('mastheadTagCount') || 12} /> : null}

          <div className="MastheadColumns">
            <LatestPanel />
            <aside className="MastheadSidebar">{this.sidebarItems().toArray()}</aside>
          </div>
        </div>

        {phone ? <ComposeFab /> : null}
      </div>
    );
  }

  sidebarItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();
    const games = getPortal()?.games;

    // Nothing between seasons: an empty schedule on a front page reads as
    // broken (Picks' own leaderboard block hides when empty for the same reason).
    if (games && games.length) items.add('games', <Games games={games} />, 100);

    // The slot the discussion page shares: top posters, and whatever other
    // extensions put there.
    items.add('slot', <WidgetSlot where="portal" />, 50);

    return items;
  }
}

markPortal(MastheadPortal);
