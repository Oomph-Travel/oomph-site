/**
 * The public route inventory — single source of truth for the page-smoke and
 * schema specs. One entry per page *type*.
 *
 * - `h1`      : exact/substring text to match when the H1 is hardcoded in the
 *               template. Omitted where the H1 comes from ACF (dynamic content) —
 *               those routes only assert that a visible, non-empty H1 exists.
 * - `types`   : JSON-LD @type values that must appear in the page's combined
 *               @graph. TravelAgency + Person are sitewide and asserted globally,
 *               so they are not repeated here. BreadcrumbList appears on every
 *               singular page (i.e. everything except the archive/blog listings).
 * - `isDiscoveryDest`: the sitewide "Start a conversation" CTA points here, so
 *               this page is exempt from the CTA-presence check.
 * - `noSitewideCta`: pages that intentionally run their own funnel instead of the
 *               sitewide CTA — the Journal archive, the Cabin Quiz (its results
 *               step has a "Book a 30-minute call" CTA), and the Cruise Trends
 *               lead magnet. Exempt from the CTA-presence check.
 */

export interface RouteFixture {
  path: string;
  name: string;
  h1?: string | RegExp;
  types: string[];
  isDiscoveryDest?: boolean;
  noSitewideCta?: boolean;
}

export const ROUTES: RouteFixture[] = [
  {
    path: '/',
    name: 'Home',
    types: ['BreadcrumbList'],
  },
  {
    path: '/about/',
    name: 'About',
    types: ['BreadcrumbList'],
  },
  {
    path: '/luxury-cruise-planning/',
    name: 'Service — Luxury Cruise Planning',
    types: ['Service', 'BreadcrumbList'],
  },
  {
    path: '/custom-italy-travel/',
    name: 'Service — Custom Italy',
    types: ['Service', 'BreadcrumbList'],
  },
  {
    path: '/multi-generational-travel-planning/',
    name: 'Service — Multi-Generational',
    types: ['Service', 'BreadcrumbList'],
  },
  {
    path: '/discovery-call/',
    name: 'Discovery Call',
    types: ['BreadcrumbList'],
    isDiscoveryDest: true,
  },
  {
    path: '/journal/',
    name: 'Journal (archive)',
    h1: /Field notes/i,
    types: [], // listing page — no BreadcrumbList
    noSitewideCta: true,
  },
  {
    path: '/client-stories/',
    name: 'Client Stories',
    types: ['BreadcrumbList'],
  },
  {
    path: '/trip-quiz/',
    name: 'Cabin Quiz',
    h1: /cruise cabin match/i,
    types: ['BreadcrumbList'],
    noSitewideCta: true,
  },
  {
    path: '/cruise-travel-trends/',
    name: 'Cruise Trends (lead magnet)',
    h1: /worth watching at sea/i,
    types: ['BreadcrumbList'],
    noSitewideCta: true,
  },
  {
    path: '/group-cruises/',
    name: 'Group Cruises (archive)',
    h1: /sailings worth booking/i,
    types: [], // CPT archive — no BreadcrumbList
  },
];
