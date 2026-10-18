import app from 'flarum/admin/app';
import Button from 'flarum/common/components/Button';
import Icon from 'flarum/common/components/Icon';
import type Stream from 'flarum/common/utils/Stream';

type Row = { label: string; url: string };

const t = (key: string) => app.translator.trans(`ernestdefoe-masthead.admin.footer.${key}`);

function read(value: Stream<string>): Row[] {
  try {
    const rows = JSON.parse(value() || '[]');

    return Array.isArray(rows) ? rows : [];
  } catch (e) {
    return [];
  }
}

let dragging: number | null = null;

/**
 * The footer's links, as rows: a name, an address, a drag handle to reorder
 * and a button to remove. Stored as JSON in one setting; the server checks
 * every address before it reaches a page.
 *
 * Reordered by dragging (native HTML drag and drop, no library) — never by
 * up/down arrows.
 */
export default function FooterLinks(value: Stream<string>) {
  const rows = read(value);
  const write = (next: Row[]) => value(JSON.stringify(next));

  return (
    <div className="Form-group MastheadAdmin-links">
      <label>{t('label')}</label>
      <p className="helpText">{t('help')}</p>
      <ol className="MastheadAdmin-linkRows">
        {rows.map((row, i) => (
          <li
            key={i}
            className="MastheadAdmin-linkRow"
            draggable="true"
            ondragstart={(e: DragEvent) => {
              dragging = i;
              e.dataTransfer?.setData('text/plain', String(i));
            }}
            ondragover={(e: DragEvent) => e.preventDefault()}
            ondrop={(e: DragEvent) => {
              e.preventDefault();
              if (dragging === null || dragging === i) return;
              const next = rows.slice();
              const [moved] = next.splice(dragging, 1);
              next.splice(i, 0, moved);
              dragging = null;
              write(next);
            }}
            ondragend={() => (dragging = null)}
          >
            <span className="MastheadAdmin-handle" aria-hidden="true">
              <Icon name="fas fa-grip-vertical" />
            </span>
            <input
              className="FormControl"
              placeholder={t('name')}
              aria-label={t('name')}
              value={row.label}
              oninput={(e: InputEvent) => write(rows.map((r, j) => (j === i ? { ...r, label: (e.target as HTMLInputElement).value } : r)))}
            />
            <input
              className="FormControl"
              placeholder={t('url_placeholder')}
              aria-label={t('url')}
              value={row.url}
              oninput={(e: InputEvent) => write(rows.map((r, j) => (j === i ? { ...r, url: (e.target as HTMLInputElement).value } : r)))}
            />
            <Button
              className="Button Button--icon Button--link"
              icon="fas fa-times"
              aria-label={t('remove')}
              onclick={() => write(rows.filter((_, j) => j !== i))}
            />
          </li>
        ))}
      </ol>
      {rows.length < 12 ? (
        <Button className="Button" icon="fas fa-plus" onclick={() => write([...rows, { label: '', url: '' }])}>
          {t('add')}
        </Button>
      ) : null}
    </div>
  );
}
