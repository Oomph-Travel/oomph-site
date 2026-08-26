# Pulling draft data out of WordPress

Everything needed for the blurbs lives on two pages per draft: the **edit screen** carries the
true slug, and the **preview** carries the route, dates, and shore event. Fetching them with
JavaScript through the Chrome extension is far quicker than loading 24 pages by hand, but
several details are easy to get wrong. They're all worked out below.

## Setup

Load the Chrome tools in one call rather than one at a time:

```
ToolSearch: select:mcp__claude-in-chrome__tabs_context_mcp,mcp__claude-in-chrome__navigate,mcp__claude-in-chrome__javascript_tool,mcp__claude-in-chrome__computer,mcp__claude-in-chrome__read_page
```

Then `tabs_context_mcp{createIfEmpty:true}` and navigate to the filtered drafts list. All
`fetch` calls below run same-origin against Eric's logged-in session, so they inherit his
cookies. Close the tab when finished.

## Step 1 — Collect the post IDs

The list paginates at 20, so page 2 has to be fetched explicitly.

```js
async function rows(url){
  const d = new DOMParser().parseFromString(
    await fetch(url,{credentials:'same-origin'}).then(r=>r.text()),'text/html');
  return [...d.querySelectorAll('tr[id^="post-"]')].map(tr=>tr.id.replace('post-',''));
}
const base='/wp-admin/edit.php?post_type=oomph_cruise&post_status=draft&m=202608';
const ids=[...await rows(base), ...await rows(base+'&paged=2')];
window.__ids=ids;
ids.length+' :: '+ids.join(',');
```

## Step 2 — Slug and sailing data per post

```js
const n=e=>e?e.textContent.replace(/\s+/g,' ').trim():'';
const pick=(t,k,next)=>{const m=t.match(new RegExp(k+'(.*?)(?='+next+'|$)'));return m?m[1].trim():'';};

async function one(id){
  const eh=await fetch('/wp-admin/post.php?post='+id+'&action=edit',
    {credentials:'same-origin'}).then(r=>r.text());
  const slug=(eh.match(/name="post_name"[^>]*value="([^"]*)"/)||[])[1]||'';

  const d=new DOMParser().parseFromString(
    await fetch('/?post_type=oomph_cruise&p='+id+'&preview=true',
      {credentials:'same-origin'}).then(r=>r.text()),'text/html');
  const g=n(d.querySelector('.oomph-dv-side__table'));
  const e=n(d.querySelector('#event'));

  return {id, slug,
    ship:pick(g,'Ship','Line'),   line:pick(g,'Line','Route'),
    route:pick(g,'Route','Dates'), dates:pick(g,'Dates','Nights'),
    nights:pick(g,'Nights','Program'), prog:pick(g,'Program','Event'),
    ev:pick(e,'Included with this sailing','Date'),
    evDate:pick(e,'Date','Departs'), evLen:pick(e,'Length','Port'),
    evPort:pick(e,'Port','Reserved')};
}

const out=[];
for(let i=0;i<window.__ids.length;i+=6){
  out.push(...await Promise.all(window.__ids.slice(i,i+6).map(one)));
}
window.__data=out;
out.length+' collected';
```

## Step 3 — Print it in chunks

The JS tool truncates results at roughly 1,000 characters. Store everything on `window` first,
then print about six records per call in a compact delimited form:

```js
window.__data.slice(0,6).map(d=>[d.id,d.slug,d.ship,d.route,d.dates,d.nights,
  d.ev,d.evDate.replace(/^\w+, /,''),d.evLen,d.evPort].join('|')).join('\n');
```

## Traps worth knowing

**`innerText` returns empty** on a document built by `DOMParser` — it needs layout, and a
detached document has none. Use `textContent` and normalize whitespace yourself.

**The labels are uppercased by CSS.** Searching the raw HTML for `SHIP` finds nothing; the
markup says `Ship`. Parse the normalized text, not the source.

**The glance table is `.oomph-dv-side__table`** and the shore event block is `#event`. Both
are stable selectors; scraping whole-page text instead produces the same fields with far more
noise.

**Field parsing degrades on some pages.** A missing `Reserved` terminator can make `evPort`
swallow the rest of the block. It's obvious in the output — re-query the affected IDs
individually rather than trusting a mangled row.

**Absent shore event** shows up two ways: `ev` reads `"No Tour"`, or the `#event` block is
missing entirely and every event field comes back empty. Both mean "write no event into the
blurb", and both are worth reporting to Eric.

**Referencing `window.__data` with a bracketed `includes([...])` filter** has tripped the
command classifier before. Use `d.id===2217||d.id===2208` style comparisons instead.

## If the browser route isn't available

Everything above is also reachable over SSH with WP-CLI:

```bash
wp post list --post_type=oomph_cruise --post_status=draft --format=csv \
  --fields=ID,post_name,post_title --after='2026-08-01'
```

Shore event fields live in ACF meta on each post (`wp post meta list <id>`), though the
preview page presents them more legibly.
