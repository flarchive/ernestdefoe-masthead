import app from 'flarum/forum/app';
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
