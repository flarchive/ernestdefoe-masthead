import app from 'flarum/admin/app';
import type Stream from 'flarum/common/utils/Stream';

type Look = { accent: string; bar: string; strip: string; hero: string; corners: string };

export const ACCENTS: Record<string, string> = {
  red: '#c8322a',
  orange: '#d97a00',
  green: '#1f7a4d',
  purple: '#6b3fb5',
};

const t = (key: string, params?: any) => app.translator.trans(`ernestdefoe-masthead.admin.${key}`, params);

export function presets(): Record<string, Look> {
  return (app.data as any).mastheadPresets || {};
}

/**
 * The preset cards: each one a miniature masthead in the preset's own
 * colours, so the choice is made by looking, not by reading hex codes.
 */
export function PresetPicker(value: Stream<string>) {
  const all = presets();

  return (
    <div className="Form-group MastheadAdmin-presets">
      <label>{t('preset.label')}</label>
      <p className="helpText">{t('preset.help')}</p>
      <div className="MastheadAdmin-presetGrid" role="radiogroup">
        {Object.keys(all).map((name) => {
          const look = all[name];
          const active = (value() || 'gameday') === name;

          return (
            <button
              type="button"
              role="radio"
              aria-checked={active ? 'true' : 'false'}
              className={'MastheadAdmin-preset' + (active ? ' is-active' : '')}
              onclick={() => value(name)}
            >
              <span className="MastheadAdmin-presetArt" style={{ '--p-bar': look.bar, '--p-strip': look.strip, '--p-accent': look.accent }}>
                <span className="MastheadAdmin-presetStrip" />
                <span className="MastheadAdmin-presetBar">
                  <span className="MastheadAdmin-presetTile" />
                </span>
                <span className={'MastheadAdmin-presetHero MastheadAdmin-presetHero--' + look.hero} />
              </span>
              <span className="MastheadAdmin-presetName">{t(`preset.${name}`)}</span>
              <span className="MastheadAdmin-presetDesc">{t(`preset.${name}_desc`)}</span>
            </button>
          );
        })}
      </div>
    </div>
  );
}

/**
 * The accent: the preset's own, one of four named colours, Flarum's primary
 * colour, or any colour at all. Stored as '' (preset), 'primary' or a hex.
 */
export function AccentPicker(value: Stream<string>) {
  const current = value() || '';
  const isCustom = current !== '' && current !== 'primary' && !Object.values(ACCENTS).includes(current);

  const swatch = (stored: string, label: any, color: string) => (
    <button
      type="button"
      role="radio"
      aria-checked={current === stored ? 'true' : 'false'}
      className={'MastheadAdmin-swatch' + (current === stored ? ' is-active' : '')}
      style={{ '--swatch': color }}
      onclick={() => value(stored)}
    >
      <span className="MastheadAdmin-swatchDot" />
      {label}
    </button>
  );

  return (
    <div className="Form-group">
      <label>{t('accent.label')}</label>
      <p className="helpText">{t('accent.help')}</p>
      <div className="MastheadAdmin-swatches" role="radiogroup">
        {swatch('', t('accent.preset'), 'conic-gradient(#c8322a 0 25%, #d97a00 0 50%, #1f7a4d 0 75%, #6b3fb5 0)')}
        {Object.keys(ACCENTS).map((key) => swatch(ACCENTS[key], t(`accent.${key}`), ACCENTS[key]))}
        {swatch('primary', t('accent.primary'), app.data.settings.theme_primary_color || '#4d698e')}
        <label className={'MastheadAdmin-swatch' + (isCustom ? ' is-active' : '')}>
          <input
            type="color"
            value={isCustom ? current : '#c8322a'}
            aria-label={t('accent.custom')}
            oninput={(e: InputEvent) => value((e.target as HTMLInputElement).value)}
          />
          {t('accent.custom')}
        </label>
      </div>
    </div>
  );
}

/**
 * One exact colour, or "use the default" when empty: the masthead, the
 * utility strip and the page ground (light and dark). `fallback` is what the
 * swatch shows while nothing is set.
 */
export function ColourPicker(value: Stream<string>, key: string, fallback: string) {
  const current = value() || '';

  return (
    <div className="Form-group">
      <label>{t(`${key}.label`)}</label>
      <p className="helpText">{t(`${key}.help`)}</p>
      <div className="MastheadAdmin-bar">
        <input
          type="color"
          value={current || fallback}
          aria-label={t(`${key}.label`)}
          oninput={(e: InputEvent) => value((e.target as HTMLInputElement).value)}
        />
        <input
          className="FormControl MastheadAdmin-hex"
          value={current}
          placeholder={fallback}
          aria-label={t('colour.hex')}
          oninput={(e: InputEvent) => value((e.target as HTMLInputElement).value.trim())}
        />
        {current ? (
          <button type="button" className="Button Button--link" onclick={() => value('')}>
            {t(`${key}.reset`)}
          </button>
        ) : (
          <span className="helpText">{t(`${key}.using_default`)}</span>
        )}
      </div>
    </div>
  );
}

/** The masthead's colour: the preset's, or a colour of the admin's own. */
export function BarPicker(value: Stream<string>) {
  const preset = presets()[app.data.settings['ernestdefoe-masthead.preset'] || 'gameday'];

  return ColourPicker(value, 'bar', preset?.bar || '#0e2340');
}
