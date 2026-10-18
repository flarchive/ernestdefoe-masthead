# Masthead

**A portal theme for Flarum 2**, in the style of an IPS 5 or XenForo UI.X
front page. A navy masthead with a utility strip, a featured story with three
beside it, a grid of the forum's sections, the latest discussions and a
sidebar of widgets, on a grey ground of white cards.

![Masthead's portal: the masthead, a featured story and three beside it](screenshots/01-portal.png)

---

## What it does

- **A masthead.** A utility strip with today's date, how many members are
  online and the visitor's own controls; then the logo or a monogram tile,
  the forum's name and a tagline, the main navigation, search and
  **Start a discussion**, with an accent rule underneath.
- **A portal home page.** The forum's front page becomes the portal; the
  discussion list moves to `/all`. Prefer the list? One setting puts it back.
- **Featured stories.** One feature and three side stories, with pictures
  taken from each opening post (Markdown, BBCode or fof/upload images). Stories
  come from the newest discussions, a tag, sticky discussions or Page Builder
  articles.
- **Browse the forum.** A tile for each top-level tag, with its icon,
  description, discussion count and latest activity.
- **Latest discussions** and **Top this week**, with coloured tag chips.
- **Widgets.** *Top posters this week*, and *Upcoming games* when
  [Picks](https://github.com/ernestdefoe/picks) is installed. Top posters can
  also be placed anywhere through Bespoke or fof/forum-widgets-core.
- **A footer** with your own links.
- **Phones** get a compact bar, the feature card, a scrolling row of section
  chips, the latest list and a floating **Start a discussion**.
- **Dark mode**, following Flarum's own light, dark and high-contrast modes.

![The whole portal, top to bottom](screenshots/portal-full.png)

![Browse the forum: a tile for each section](screenshots/tag-grid.png)

<img src="screenshots/widgets.png" alt="The Top posters this week widget" width="300">

![Masthead on an iPhone and an Android phone](screenshots/04-phones.png)

---

## The discussion page

Every discussion reads as an article with a discussion below it.

- **A story header**: the section as a coloured kicker and the other tags
  beside it, the title as a headline, and a byline with the author's group,
  the date, the reading time and the reply count. When
  [fof/best-answer](https://github.com/FriendsOfFlarum/best-answer) has
  marked an answer, there is a link straight to it.
- **The opening post as the article**: a wide lead picture taken from the
  post's own first image (you can turn this off), a comfortable reading
  column, proper pull quotes, and **Copy link** and **Share** under it.
- **Replies as cards** under a "Discussion · N replies" heading, each with
  the author's group and the post number. The best answer gets a green rule.
  Renames, tag changes and the like are a thin line between them.
- **A sidebar** with *About this discussion* (started, last reply, replies,
  who is taking part, Follow and the discussion menu), *More in* the
  discussion's section, and the same widgets as the portal.
- **Live replies look the same.** A reply that arrives while you read gets
  exactly the same treatment as the rest.
- **On phones**, the header, then the article, the replies and *More in* at
  the end, with a floating **Reply** button.

It is Flarum's own discussion page underneath, so mentions, reactions,
likes, best answers and anything else you have installed keep working.

![A discussion in Masthead: the story header, the article and the sidebar](screenshots/02-topic.png)

<p>
<img src="screenshots/phone-topic-ios.png" alt="A discussion on an iPhone" width="260">
<img src="screenshots/phone-topic-android.png" alt="A discussion on Android" width="260">
<img src="screenshots/phone-portal-ios.png" alt="The portal on an iPhone" width="260">
<img src="screenshots/phone-portal-android.png" alt="The portal on Android" width="260">
</p>

---

## Presets

Pick a look in one click. A preset sets the accent, the masthead colour, the
featured-story layout and the corners together:

| Preset | Look |
|---|---|
| **Game Day** | Navy and red, full-width feature |
| **Sunday** | Charcoal and orange, split feature |
| **Evergreen** | Forest green, full-width feature |
| **Royal** | Midnight and purple, full-width feature |
| **Broadsheet** | Black and red, square corners, split feature |

Then change only what you want. The accent can be red, orange, green, purple,
Flarum's own primary colour or any colour you choose; the masthead can take any
colour; the feature can be full width, split or off. Your changes stay when you
switch presets.

![Two presets side by side: Game Day and Broadsheet](screenshots/03-presets.png)

![Masthead in dark mode](screenshots/portal-dark.png)

---

## Settings

![Masthead's settings: presets, accent, masthead colour, featured stories and footer links](screenshots/admin.png)

| Setting | What it does |
|---|---|
| **Home page** | The portal or the discussion list. The same setting as *Home page* in Basics. |
| **Preset**, **Accent colour**, **Masthead colour** | The look, above. |
| **Utility strip colour**, **Page background** (light and dark) | Exact colours for a brand palette. Empty means the default: the strip a shade darker than the masthead, the mockup grey, Flarum's dark background. A dark background you set also tints the dark-mode cards. |
| **Tagline**, **Monogram**, **Show the forum name beside the logo** | The title block. The logo is Flarum's own (Appearance). The masthead is dark in both colour modes, so it shows your **dark-mode logo** whenever you have uploaded one, and your normal logo otherwise. The name shows beside a logo only if you turn it on; without a logo it always shows. |
| **Use the theme's fonts** | Barlow Condensed and Figtree, served from your forum. Off uses system fonts. |
| **Layout**, **Stories from**, **Tag** / **Content type** | The featured stories. |
| **Sections in the grid** | How many tags *Browse the forum* shows. |
| **Show a lead image on discussions** | The opening post's first picture as a banner at the top of the article. |
| **Footer links**, **Show "Powered by Flarum"** | The footer. Drag links to reorder. |

---

## Works with

All optional. Masthead checks for each one and does without it.

- **flarum/tags**: the section grid, tag chips and a tag as the story source.
- **flarum/sticky**: sticky discussions as the story source.
- **fof/upload**: its images are used as story pictures.
- **ernestdefoe/page-builder**: articles as the story source.
- **ernestdefoe/header-nav**: when installed, it decides what the main navigation holds.
- **ernestdefoe/backdrop**: an author's profile cover is the picture of last resort.
- **ernestdefoe/picks**: the upcoming-games card.
- **ernestdefoe/bespoke**, **fof/forum-widgets-core**: place *Top posters* in their zones.
- **ernestdefoe/wardrobe**: offer Masthead as one of several themes members can choose.

---

## Light by design

- **forum.js**: 26 KB (7.9 KB gzipped), the whole theme: portal and discussion page.
- **Stylesheet**: about 22 KB (4.6 KB gzipped) added to the forum's CSS.
- **The portal page** runs 14 database queries in total on a forum with 60+
  extensions enabled, and the same with 7 tags or 32. Masthead's own share is five
  for the featured stories. The top-posters list and the online count are
  cached (10 minutes and 1 minute).
- **The discussion page** adds one request per section (*More in*, kept for
  five minutes) and nothing per post.
- **Fonts** are served from your own forum, never from Google, and only the
  latin subset loads unless a page needs more.
- Tested with a **database table prefix** on MariaDB, PostgreSQL and
  SQLite.

---

## Install

```bash
composer require ernestdefoe/masthead
php flarum cache:clear
```

Enable **Masthead** in the admin panel. The portal becomes the home page unless
you had already chosen a home page other than the discussion list.

## Update

```bash
composer update ernestdefoe/masthead
php flarum cache:clear
```

---

## For developers

Add a widget to the portal's sidebar and the discussion page's sidebar at once
by extending the shared slot:

```js
import { extend } from 'flarum/common/extend';

extend(flarum.reg.get('ernestdefoe-masthead', 'forum/components/WidgetSlot').prototype, 'items', function (items) {
  items.add('myWidget', <MyWidget />, 30);
});
```

## Translations

Every word on screen is in `locale/en.yml`, ready for a language pack.

## Licence

MIT. The bundled fonts, Barlow Condensed and Figtree, are under the SIL Open
Font License (`assets/fonts/`).

☕ If you like the things I have built, consider [buying me a coffee](https://buymeacoffee.com/ernestdefoe).
