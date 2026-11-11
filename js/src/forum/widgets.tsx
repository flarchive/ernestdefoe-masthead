import app from 'flarum/forum/app';
import type Mithril from 'mithril';
import TopPosters from './components/TopPosters';
import { flarumGlobal, has } from './util';

/**
 * Top posters, offered to both widget hosts.
 *
 * 🚨 Bespoke AND fof/forum-widgets-core: not every forum runs Bespoke. Each is
 * resolved at runtime through its own registry, never imported, so the bundle
 * is the same with both, either or neither installed.
 *
 * 🚨 Registering is not placing. Neither host shows the widget until an admin
 * puts it somewhere, and the portal's own sidebar draws it regardless.
 */
export default function registerWidgets() {
  registerFof();
  registerBespoke();
}

function registerFof() {
  if (!has('fof-forum-widgets-core')) return;

  const flarum = flarumGlobal();
  const widgetsMod = flarum.reg?.get?.('fof-forum-widgets-core', 'common/extend/Widgets');
  const widgetMod = flarum.reg?.get?.('fof-forum-widgets-core', 'common/components/Widget');
  const Widgets = widgetsMod?.default ?? widgetsMod;
  const WidgetBase = widgetMod?.default ?? widgetMod;

  // Present but not resolvable: a version whose internals moved. Decline
  // quietly rather than throw out of the initializer.
  if (!Widgets || !WidgetBase) return;

  class MastheadTopPostersWidget extends WidgetBase {
    className() {
      return 'MastheadFofWidget';
    }

    icon() {
      return 'fas fa-trophy';
    }

    title() {
      return app.translator.trans('ernestdefoe-masthead.forum.top_posters.title');
    }

    content() {
      return <TopPosters heading={false} />;
    }
  }

  new Widgets()
    .add({
      key: 'mastheadTopPosters',
      component: MastheadTopPostersWidget,
      isDisabled: false,
      isUnique: true,
      placement: 'end',
      position: 1,
    })
    .extend(app, 'ernestdefoe-masthead');
}

/** The portal draws its own top posters: widget-core's copy would repeat it. */
const OWN_FOF_WIDGET = 'ernestdefoe-masthead:mastheadTopPosters';

/**
 * The widgets an admin placed in one of fof/forum-widgets-core's areas, for
 * the portal: widget-core draws its areas through core's PageStructure, which
 * the portal (a page of its own) doesn't use. Their own section classes keep
 * widget-core's styling. Null without widget-core or with nothing placed.
 */
export function fofSection(placement: string, className: string): Mithril.Children {
  const widgets = (app as any).widgets;
  if (!has('fof-forum-widgets-core') || typeof widgets?.get !== 'function') return null;

  const placed = widgets
    .get(placement)
    .filter((widget: any) => widget.id !== OWN_FOF_WIDGET)
    .sort((a: any, b: any) => (a.position ?? 0) - (b.position ?? 0));

  if (!placed.length) return null;

  return (
    <div className={'MastheadFofSection FofWidgets-WidgetSection ' + className}>
      {placed.map((widget: any) => widget.component.component({ state: widget.state }))}
    </div>
  );
}

function registerBespoke() {
  if (!has('ernestdefoe-bespoke')) return;

  const g = globalThis as any;

  g.BespokeWidgetQueue = g.BespokeWidgetQueue || [];
  g.BespokeWidgetQueue.push({
    type: 'masthead-top-posters',
    // 🚨 A translation KEY, not translated text: Bespoke resolves it itself,
    // and its editor calls string methods on the label.
    label: 'ernestdefoe-masthead.forum.top_posters.title',
    icon: 'fas fa-trophy',
    zones: ['sidebar', 'above-list', 'below-list', 'footer'],
    schema: [],
    component: {
      view() {
        return (
          <div className="MastheadWidget MastheadBespokeWidget">
            <TopPosters />
          </div>
        );
      },
    },
  });
}
