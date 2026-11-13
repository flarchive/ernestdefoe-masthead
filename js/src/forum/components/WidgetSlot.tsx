import Component from 'flarum/common/Component';
import ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import TopPosters from './TopPosters';

/**
 * The widget slot the portal's sidebar and the discussion page's sidebar
 * share. Another extension fills it once and appears in both:
 *
 *   extend(WidgetSlot.prototype, 'items', function (items) {
 *     items.add('newsletter', <Newsletter />, 30);   // this.attrs.where is 'portal' or 'discussion'
 *   });
 */
export default class WidgetSlot extends Component<{ where: 'portal' | 'discussion' }> {
  view() {
    return this.items().toArray();
  }

  items(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'topPosters',
      <section className="MastheadWidget">
        <TopPosters />
      </section>,
      50
    );

    return items;
  }
}
