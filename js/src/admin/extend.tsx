import app from 'flarum/admin/app';
import Admin from 'flarum/common/extenders/Admin';
import Select from 'flarum/common/components/Select';
import Icon from 'flarum/common/components/Icon';
import { AccentPicker, BarPicker, ColourPicker, PresetPicker, presets } from './components/PresetPicker';
import FooterLinks from './components/FooterLinks';

/**
 * Masthead's settings page.
 *
 * 🚨 Exported from js/src/admin/index.ts and nowhere else: this imports
 * `flarum/admin/app`, which does not exist on the forum.
 *
 * 🚨 Every control has a reader, named beside it. The custom controls are
 * functions called with the settings page as `this`, so they write through
 * the page's own `this.setting()` streams and are saved by its Save button
 * like any other setting.
 */
const K = (key: string) => `ernestdefoe-masthead.${key}`;
const t = (key: string, params?: any) => app.translator.trans(`ernestdefoe-masthead.admin.${key}`, params);
const has = (id: string) => !!(app.data.extensions && (app.data.extensions as any)[id]);

function heading(key: string) {
  return <h3 className="MastheadAdmin-heading">{t(`section.${key}`)}</h3>;
}

/**
 * Every tag, for the hero's tag picker: primary tags in their order, each
 * followed by its children, then the secondary tags by name. Any of them can
 * feed the hero (Portal\Hero matches the tag id, whatever its kind).
 *
 * 🚨 Loaded through the tag list once: the admin's store may already hold a
 * few tags (from another page), and "the store is not empty" is not "every
 * tag is loaded".
 */
let tagsRequested = false;
function tagOptions(): [string, string][] {
  if (!tagsRequested && has('flarum-tags')) {
    tagsRequested = true;
    const list = (app as any).tagList;
    (list ? list.load(['parent']) : app.store.find('tags', { include: 'parent' })).then(() => m.redraw());
  }

  const tags = app.store.all<any>('tags');
  const byPosition = (a: any, b: any) => a.position() - b.position();
  // A list, not an object: an object's numeric keys (tag ids) iterate in
  // number order, which would undo the order built here.
  const options: [string, string][] = [['0', t('hero.tag_none') as any]];
  const add = (tag: any, label: any) => options.push([String(tag.id()), label]);

  tags
    .filter((tag: any) => tag.position() !== null && !tag.parent())
    .sort(byPosition)
    .forEach((tag: any) => {
      add(tag, tag.name());
      tags
        .filter((child: any) => child.parent() === tag)
        .sort(byPosition)
        .forEach((child: any) => add(child, '— ' + child.name()));
    });

  tags
    .filter((tag: any) => tag.position() === null && !tag.parent())
    .sort((a: any, b: any) => a.name().localeCompare(b.name()))
    .forEach((tag: any) => add(tag, t('hero.tag_secondary', { name: tag.name() })));

  // A tag that is none of the above (a child whose parent is not loaded)
  // still shows by name rather than going missing.
  tags.filter((tag: any) => !options.some(([id]) => id === String(tag.id()))).forEach((tag: any) => add(tag, tag.name()));

  return options;
}

export default [
  new Admin()
    /*
     * Read by core: `default_route` is Flarum's own home-page setting (Admin
     * → Basics). Masthead writes to it rather than keeping a second switch
     * that could disagree with the first.
     */
    .customSetting(function (this: any) {
      const route = this.setting('default_route', '/all');
      const known = ['/portal', '/all'].includes(route());

      return (
        <div className="Form-group">
          {heading('home')}
          <label>{t('home.label')}</label>
          <p className="helpText">{t('home.help')}</p>
          <Select
            value={known ? route() : 'other'}
            options={{
              '/portal': t('home.portal'),
              '/all': t('home.list'),
              ...(known ? {} : { other: t('home.other') }),
            }}
            onchange={(v: string) => v !== 'other' && route(v)}
          />
        </div>
      );
    }, 100)

    // Read by Settings::look(), compiled into the stylesheet.
    .customSetting(function (this: any) {
      return (
        <div>
          {heading('look')}
          {PresetPicker(this.setting(K('preset'), 'gameday'))}
        </div>
      );
    }, 90)
    .customSetting(function (this: any) {
      return AccentPicker(this.setting(K('accent'), ''));
    }, 85)
    .customSetting(function (this: any) {
      return BarPicker(this.setting(K('bar'), ''));
    }, 80)
    // Read by Settings::look(): an exact strip colour, else derived from the bar.
    .customSetting(function (this: any) {
      const preset = presets()[app.data.settings[K('preset')] || 'gameday'];

      return ColourPicker(this.setting(K('strip'), ''), 'strip', preset?.strip || '#0a1a30');
    }, 79)
    // Read by Settings::look(), compiled as @masthead-ground / -ground-dark.
    .customSetting(function (this: any) {
      return ColourPicker(this.setting(K('ground'), ''), 'ground', '#f3f4f6');
    }, 78)
    .customSetting(function (this: any) {
      return ColourPicker(this.setting(K('ground_dark'), ''), 'ground_dark', '#111318');
    }, 77)

    // Read by the masthead's Brand component.
    .setting(
      () => ({
        setting: K('tagline'),
        label: t('brand.tagline_label'),
        help: t('brand.tagline_help'),
        type: 'text',
      }),
      75
    )
    .setting(
      () => ({
        setting: K('monogram'),
        label: t('brand.monogram_label'),
        help: t('brand.monogram_help'),
        type: 'text',
      }),
      74
    )
    .setting(
      () => ({
        setting: K('show_name'),
        label: t('brand.show_name_label'),
        help: t('brand.show_name_help'),
        type: 'boolean',
      }),
      73
    )
    // Read by the font stamp in extend.php and less/lib/fonts.less.
    .setting(
      () => ({
        setting: K('fonts'),
        label: t('brand.fonts_label'),
        help: t('brand.fonts_help'),
        type: 'boolean',
      }),
      72
    )

    // Read by Settings::look() → the mastheadHero attribute → Hero's class.
    .customSetting(function (this: any) {
      return (
        <div className="Form-group">
          {heading('hero')}
          <label>{t('hero.style_label')}</label>
          <p className="helpText">{t('hero.style_help')}</p>
          <Select
            value={this.setting(K('hero_style'), '')()}
            options={{
              '': t('hero.style_preset'),
              overlay: t('hero.style_overlay'),
              split: t('hero.style_split'),
              off: t('hero.style_off'),
            }}
            onchange={this.setting(K('hero_style'), '')}
          />
        </div>
      );
    }, 70)

    // Read by Portal\Hero. Each source is offered only when its extension is
    // enabled, so the list never holds a choice that would do nothing.
    .setting(() => {
      const options: Record<string, any> = { latest: t('hero.source_latest') };
      if (has('flarum-tags')) options.tag = t('hero.source_tag');
      if (has('flarum-sticky')) options.sticky = t('hero.source_sticky');
      if (has('ernestdefoe-page-builder')) options.pagebuilder = t('hero.source_pagebuilder');

      return {
        setting: K('hero_source'),
        label: t('hero.source_label'),
        help: t('hero.source_help'),
        type: 'select',
        options,
        default: 'latest',
      };
    }, 65)
    .customSetting(function (this: any) {
      if (!has('flarum-tags') || this.setting(K('hero_source'), 'latest')() !== 'tag') return null;

      return (
        <div className="Form-group">
          <label>{t('hero.tag_label')}</label>
          <span className="Select">
            <select
              className="Select-input FormControl"
              value={this.setting(K('hero_tag'), '0')()}
              onchange={(e: Event) => this.setting(K('hero_tag'), '0')((e.target as HTMLSelectElement).value)}
            >
              {tagOptions().map(([id, label]) => (
                <option value={id}>{label}</option>
              ))}
            </select>
            <Icon name="fas fa-sort" className="Select-caret" />
          </span>
        </div>
      );
    }, 64)
    .customSetting(function (this: any) {
      if (!has('ernestdefoe-page-builder') || this.setting(K('hero_source'), 'latest')() !== 'pagebuilder') return null;

      return (
        <div className="Form-group">
          <label>{t('hero.type_label')}</label>
          <p className="helpText">{t('hero.type_help')}</p>
          <input className="FormControl" bidi={this.setting(K('hero_type'), 'articles')} />
        </div>
      );
    }, 63)

    // Read by Portal\Hero: off, a source with fewer than four keeps to its own.
    // Offered only for a source that can run short.
    .customSetting(function (this: any) {
      if (this.setting(K('hero_source'), 'latest')() === 'latest') return null;

      return this.buildSettingComponent({ setting: K('hero_fill'), label: t('hero.fill_label'), help: t('hero.fill_help'), type: 'boolean' });
    }, 62.7)

    // Read by Portal\Hero (the tag kicker on each story; serialised as mastheadHeroTags).
    .setting(
      () => ({
        setting: K('hero_tags'),
        label: t('hero.tags_label'),
        help: t('hero.tags_help'),
        type: 'boolean',
      }),
      62.5
    )

    // Read by Portal\Images and the discussion page's article (serialised as
    // mastheadPictures).
    .setting(() => {
      const options: Record<string, any> = {
        both: t('pictures.both'),
        image: t('pictures.image'),
        backdrop: t('pictures.backdrop'),
        off: t('pictures.off'),
      };

      return {
        setting: K('pictures'),
        label: t('pictures.label'),
        help: t('pictures.help'),
        type: 'select',
        options,
        default: 'both',
      };
    }, 62)

    // Read by the portal's TagGrid (serialised as mastheadTagCount).
    .setting(() => {
      if (!has('flarum-tags')) return null;

      return {
        setting: K('tag_count'),
        label: t('portal.tag_count_label'),
        help: t('portal.tag_count_help'),
        type: 'number',
        min: 1,
        max: 48,
        default: 12,
      };
    }, 60)

    // Read by the tags page (serialised as mastheadTagsPage).
    .setting(() => {
      if (!has('flarum-tags')) return null;

      return {
        setting: K('tags_page'),
        label: t('portal.tags_page_label'),
        help: t('portal.tags_page_help'),
        type: 'boolean',
      };
    }, 59)

    // Read by the footer (serialised as mastheadFooterLinks, checked first).
    .customSetting(function (this: any) {
      return (
        <div>
          {heading('footer')}
          {FooterLinks(this.setting(K('footer_links'), '[]'))}
        </div>
      );
    }, 50)
    .setting(
      () => ({
        setting: K('powered_by'),
        label: t('footer.powered_by_label'),
        type: 'boolean',
      }),
      45
    ),
];
