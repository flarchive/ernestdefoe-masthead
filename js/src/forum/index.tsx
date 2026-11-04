import app from 'flarum/forum/app';
import MastheadPortal from './components/Portal';
import header from './header';
import topic from './topic';
import registerWidgets from './widgets';
import tagsPage from './tagsPage';

export { default as MastheadPortal } from './components/Portal';
export { default as WidgetSlot } from './components/WidgetSlot';

/*
 * The portal's route, registered before the app mounts: core decides which
 * route `/` belongs to from `default_route` during mount, so the portal has
 * to exist by then for "the portal is the home page" to work.
 */
app.initializers.add(
  'ernestdefoe-masthead',
  () => {
    app.routes['masthead.portal'] = { path: '/portal', component: MastheadPortal };

    header();
    topic();
    registerWidgets();
    tagsPage();
  },
  // Last, so the header split runs after every extension has added its
  // controls — an item added after the split would land on the wrong row.
  -100
);
