# Schema Markup Library

JSON-LD blocks, one per page type. Drop into the page `<head>` via Rank Math's "Custom Schema" or via the child theme's `functions.php`. Test every block in [Google's Rich Results Test](https://search.google.com/test/rich-results) before merging.

**Universal rules:**
- Use `@id` URLs that match the actual page URL (with `#organization`, `#person`, etc. fragments for entity linking).
- Pages with structured data are 3.2× more likely to be cited in AI Overviews.
- Never mark up content not visible on the page (Google considers this a violation).
- Update `dateModified` on every meaningful edit.

---

## Organization — sitewide (in `<head>` of every page via theme)

```json
{
  "@context": "https://schema.org",
  "@type": "TravelAgency",
  "@id": "https://oomphtravel.com/#organization",
  "name": "Oomph Travel",
  "legalName": "Oomph Travel LLC",
  "url": "https://oomphtravel.com",
  "logo": {
    "@type": "ImageObject",
    "@id": "https://oomphtravel.com/#logo",
    "url": "https://oomphtravel.com/wp-content/uploads/2026/05/Original-Logo-Symbol.png",
    "contentUrl": "https://oomphtravel.com/wp-content/uploads/2026/05/Original-Logo-Symbol.png",
    "width": 1604,
    "height": 1671,
    "caption": "Oomph Travel"
  },
  "image": { "@id": "https://oomphtravel.com/#logo" },
  "description": "Premium and luxury cruises, and custom European journeys, planned by one named advisor.",
  "slogan": "Life is short — travel with Oomph.",
  "founder": { "@id": "https://oomphtravel.com/about/#advisor" },
  "areaServed": {
    "@type": "Country",
    "name": "United States"
  },
  "priceRange": "$$$$",
  "address": {
    "@type": "PostalAddress",
    "addressLocality": "Port Angeles",
    "addressRegion": "WA",
    "addressCountry": "US"
  },
  "contactPoint": {
    "@type": "ContactPoint",
    "contactType": "Customer Service",
    "email": "hello@oomphtravel.com",
    "telephone": "+1-360-775-4644",
    "areaServed": "US",
    "availableLanguage": "English"
  },
  "sameAs": [
    "https://www.linkedin.com/company/oomph-travel",
    "https://www.instagram.com/oomphtravel"
  ],
  "knowsAbout": [
    "Luxury cruises",
    "Silversea Cruises",
    "Custom European travel",
    "Multi-generational travel",
    "Italy travel planning",
    "UK travel planning"
  ]
}
```

---

## Person — advisor bio (on `/about` and referenced sitewide)

```json
{
  "@context": "https://schema.org",
  "@type": "Person",
  "@id": "https://oomphtravel.com/about/#advisor",
  "name": "Eric Hempel",
  "jobTitle": "Travel Advisor",
  "url": "https://oomphtravel.com/about/",
  "image": "https://oomphtravel.com/wp-content/uploads/eric-hempel-portrait.jpg",
  "worksFor": { "@id": "https://oomphtravel.com/#organization" },
  "memberOf": [
    {
      "@type": "Organization",
      "name": "Cruise Lines International Association",
      "url": "https://cruising.org"
    },
    {
      "@type": "Organization",
      "name": "Nexion Travel Group",
      "url": "https://nexion.com"
    }
  ],
  "hasCredential": [
    {
      "@type": "EducationalOccupationalCredential",
      "credentialCategory": "certification",
      "name": "Silversea Ultra-Luxury Specialist",
      "recognizedBy": { "@type": "Organization", "name": "Silversea Cruises" }
    },
    {
      "@type": "EducationalOccupationalCredential",
      "credentialCategory": "certification",
      "name": "BritAgent Pro",
      "recognizedBy": { "@type": "Organization", "name": "VisitBritain" }
    }
  ],
  "knowsLanguage": "en",
  "knowsAbout": ["Silversea Cruises", "Italy travel", "United Kingdom travel", "Multi-generational travel"],
  "sameAs": [
    "https://www.linkedin.com/in/erichempel"
  ]
}
```

---

## Service — one per service hub page

```json
{
  "@context": "https://schema.org",
  "@type": "Service",
  "@id": "https://oomphtravel.com/luxury-cruise-planning/#service",
  "name": "Luxury Cruise Planning",
  "serviceType": "TravelAgency",
  "provider": { "@id": "https://oomphtravel.com/#organization" },
  "areaServed": {
    "@type": "AdministrativeArea",
    "name": "Worldwide"
  },
  "audience": {
    "@type": "Audience",
    "audienceType": "Affluent travelers 50–75 planning premium or ultra-luxury cruises"
  },
  "description": "End-to-end planning for premium and ultra-luxury cruises, with cabin selection, dining, shore time, and pre/post itineraries handled by one named advisor.",
  "url": "https://oomphtravel.com/luxury-cruise-planning/"
}
```

> **No planning fee (locked 2026-07-03, see BUILD-PLAN.md).** Eric does not charge one — never emit an Offer/OfferCatalog implying a fee. Fee framing on pages is commission-based / no-added-cost.

```json
```

---

## FAQPage — required on every service hub and pillar guide

```json
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "@id": "https://oomphtravel.com/luxury-cruise-planning/#faq",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "Why work with a travel advisor for a cruise?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Cruise lines publish thousands of cabins; only a handful are right for you. An advisor knows the deck plans, the noise patterns, the suite categories that overdeliver, and the ones that quietly disappoint."
      }
    },
    {
      "@type": "Question",
      "name": "Do you charge a planning fee?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "No — my planning costs you nothing. Cruise lines pay commissions on the booked fare, and those commissions don't change your price."
      }
    }
  ]
}
```

---

## BreadcrumbList — every interior page

```json
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {
      "@type": "ListItem",
      "position": 1,
      "name": "Home",
      "item": "https://oomphtravel.com/"
    },
    {
      "@type": "ListItem",
      "position": 2,
      "name": "Luxury Cruise Planning",
      "item": "https://oomphtravel.com/luxury-cruise-planning/"
    }
  ]
}
```

---

## Article / BlogPosting — every journal post

```json
{
  "@context": "https://schema.org",
  "@type": "BlogPosting",
  "@id": "https://oomphtravel.com/journal/silver-nova-cabin-guide/#article",
  "mainEntityOfPage": "https://oomphtravel.com/journal/silver-nova-cabin-guide/",
  "headline": "Silver Nova Cabin Guide: Five Suites That Overdeliver",
  "description": "A first-hand walkthrough of the Silversea Silver Nova suite categories, with the five that consistently overdeliver and the two to think twice about.",
  "image": {
    "@type": "ImageObject",
    "url": "https://oomphtravel.com/wp-content/uploads/silver-nova-veranda.webp",
    "width": 1600,
    "height": 900
  },
  "author": { "@id": "https://oomphtravel.com/about/#advisor" },
  "publisher": { "@id": "https://oomphtravel.com/#organization" },
  "datePublished": "2026-03-12",
  "dateModified": "2026-05-10",
  "wordCount": 2400,
  "inLanguage": "en-US",
  "articleSection": "Cruise"
}
```

---

## Review + AggregateRating — testimonials block

```json
{
  "@context": "https://schema.org",
  "@type": "TravelAgency",
  "@id": "https://oomphtravel.com/#organization",
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "5.0",
    "reviewCount": "12",
    "bestRating": "5"
  },
  "review": [
    {
      "@type": "Review",
      "reviewRating": {
        "@type": "Rating",
        "ratingValue": "5",
        "bestRating": "5"
      },
      "author": {
        "@type": "Person",
        "name": "The Hendersons"
      },
      "datePublished": "2025-04-02",
      "reviewBody": "Thirtieth anniversary, Silver Nova, March 2025. Eric had the cabin, the dinner reservations, and the shore time mapped before we even knew what to ask for.",
      "name": "30th anniversary on Silver Nova"
    }
  ]
}
```

Only mark up testimonials that are real, attributable, and on-page.

---

## Event — group cruise landing pages (the most undervalued schema in this niche)

```json
{
  "@context": "https://schema.org",
  "@type": "Event",
  "@id": "https://oomphtravel.com/group-cruises/mediterranean-2027/#event",
  "name": "Hosted Group Cruise · Western Mediterranean 2027",
  "description": "Hosted by Eric Hempel. Twelve cabins, Silver Nova, Barcelona to Rome, October 2027.",
  "startDate": "2027-10-04",
  "endDate": "2027-10-15",
  "eventStatus": "https://schema.org/EventScheduled",
  "eventAttendanceMode": "https://schema.org/OfflineEventAttendanceMode",
  "location": {
    "@type": "Place",
    "name": "Silver Nova · Western Mediterranean",
    "address": {
      "@type": "PostalAddress",
      "addressLocality": "Barcelona",
      "addressCountry": "ES"
    }
  },
  "image": "https://oomphtravel.com/wp-content/uploads/silver-nova-med-2027.webp",
  "organizer": { "@id": "https://oomphtravel.com/#organization" },
  "offers": {
    "@type": "Offer",
    "url": "https://oomphtravel.com/group-cruises/mediterranean-2027/",
    "price": "8950.00",
    "priceCurrency": "USD",
    "availability": "https://schema.org/InStock",
    "validFrom": "2026-05-01"
  }
}
```

---

## TouristDestination — destination guides

```json
{
  "@context": "https://schema.org",
  "@type": "TouristDestination",
  "name": "Puglia",
  "description": "The heel of Italy — orange groves, trulli towns, and the Adriatic coast between Lecce and Polignano a Mare.",
  "geo": {
    "@type": "GeoCoordinates",
    "latitude": 40.7928,
    "longitude": 17.1011
  },
  "touristType": ["Slow travelers", "Couples", "Multi-generational families"],
  "url": "https://oomphtravel.com/destinations/puglia/"
}
```

---

## Implementation notes for Claude Code

When generating a new page, output the JSON-LD into the page's "Custom Schema" panel in Rank Math. Do not hardcode `@id` URLs that include placeholders — always substitute the real published URL. After publishing, paste the rendered URL into the Rich Results Test and confirm zero errors before declaring the page done.
