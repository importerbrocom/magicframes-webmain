# Requirements status — honest checklist

Legend: ✅ done and tested · 🟡 done differently / partly · ❌ not done · 📝 needs content from the studio

Verification: **81 automated tests** (808 assertions) pass on SQLite **and** on MariaDB 11.4 (MySQL-compatible);
the public pages were also checked in a real browser (Chromium) at desktop and phone width; production mode
(cached config/routes/views, `APP_DEBUG=false`) was boot-tested; backup → wipe → restore was run end to end.

## 1. Design identity
| Requirement | Status |
|---|---|
| Keep theme colours, fonts, typography, branding, layouts | ✅ `public/css/style.css` is unmodified; all additions live in `enhancements.css` using the original colour/font variables. Logo, header layout, buttons, section styling are the original ones. Browser-checked. |

## 2. Technology
| Requirement | Status |
|---|---|
| Laravel, REST API, MySQL, auth, secure uploads | ✅ Laravel 13, `/api/v1`, MySQL/MariaDB verified |
| Filament admin, dashboard, role-based permissions | ✅ Filament 5; roles *super-admin / editor / enquiry-handler* with per-module permissions |
| **React.js, Tailwind, Framer Motion, GSAP** | 🟡 **Not used.** Decision agreed at the start ("option A"): the existing site is static HTML/CSS/JS, so it was kept and made dynamic with Blade templates + vanilla JavaScript + CSS animations. Same look, server-rendered (better for SEO), no migration risk. A React rewrite remains possible later on top of the same API. |

## 3. Graphics & animation
| Item | Status |
|---|---|
| Page transitions, scroll-triggered reveals, animated hero, parallax, counters, loading animation, custom cursor, hover/zoom effects, animated headings | ✅ (original effects kept + page fade, word-by-word headings, gallery hover captions) — all disabled for visitors who prefer reduced motion |
| Image "reveal" effects | ✅ curtain-style photo reveals (gallery, portfolio, blog, about), staggered |
| Extra effects (added on request) | ✅ golden bokeh and glitter sparkles on the hero and soft light on banners, letter-by-letter hero headline, scrolling gallery-name band, camera-viewfinder corners + "View/Play" cursor on photos, card tilt with glow, magnetic buttons with light sweep, section-heading ornaments, scroll progress line + back-to-top ring, preloader curtain, lightbox slide direction, About-photo parallax. All switchable in Admin, off for reduced-motion visitors, lighter on phones |
| "Beautiful background graphics" | ✅ golden sparkles / light particles (canvas), hero vignette, ornaments; GSAP / Framer Motion not used (plain JS, no libraries) |

## 4. Home page
| Item | Status |
|---|---|
| Hero, about, services, featured photography, featured videos, recent projects, testimonials, statistics, contact/enquiry, footer; all manageable and re-orderable | ✅ |
| Instagram section | 🟡 shows a "Follow us" button linking to your Instagram. A live photo feed needs an Instagram API token (not available). |
| Real content (texts, photos, stats, testimonials) | 📝 Intentionally empty until the studio enters genuine content. |

## 5–6. Galleries & videos
| Item | Status |
|---|---|
| Unlimited categories, multi-upload, captions, order, featured, publish, delete, replace | ✅ |
| Masonry + grid layouts, hover animation, lightbox with next/prev/keyboard/swipe/full-screen, captions, category filter, lazy loading, responsive images (WebP 480/960/1600) | ✅ |
| Gallery updates automatically after admin changes | ✅ tested |
| AVIF | ❌ WebP only |
| Videos: upload or YouTube/Vimeo, thumbnails, categories, descriptions, featured, filter, full-screen player, nothing loads until played | ✅ |
| "Video preview" (hover/inline preview) | 🟡 thumbnail + play button only |

## 7. Admin
| Item | Status |
|---|---|
| Secure login/logout, protected routes, password change, forgot-password e-mail | ✅ (reset e-mail needs SMTP configured) |
| Gallery, video, homepage, header, pages, services, testimonials, enquiries management | ✅ |
| Header: logo, menus (labels/links), background, show/hide, sticky, CTA, mobile settings | ✅ mobile menu uses the same menu items plus "mobile-only" items and a social-icons toggle |
| Pages: create/edit, images/videos, SEO, publish | ✅ |
| Enquiry statuses New / Contacted / In Progress / Completed, notes, filters, CSV export | ✅ |

## 8. Pages
Home ✅ · About ✅ · Services ✅ · Portfolio ✅ · Wedding / Pre-wedding / Photography galleries ✅ (generated from categories: `/gallery/wedding-photography`, `/gallery/pre-wedding-photography`, `/gallery`) · Video gallery ✅ · Individual gallery ✅ · Blog + post ✅ · Contact ✅ · Privacy / Terms ✅ pages exist but start **unpublished** 📝 (legal text must come from you) · Custom 404 ✅ · Shop 🟡 kept as an editable page (the template's products were fake; no shop system).

## 9. Backend & database
Tables for users, roles/permissions, settings, headers, navigation, pages, sections, gallery, video, services, testimonials, blog, enquiries, SEO ✅. A separate generic **`media` table is not used** (each model stores its own file paths and cleans them up) 🟡. CRUD, validation, errors, pagination, search/filter, secure uploads ✅. Backup guidance + automated backup/restore ✅.

## 10. Upload system
Select multiple, preview before saving, upload video + thumbnail preview, captions, replace, delete, publish ✅.

## 11. Responsive
A dedicated **mobile layer** (`public/css/enhancements.css`, section "MOBILE LAYER") was added and audited in a real browser (Chromium) at **360, 390 and 768 px** on all 12 public page types: no sideways scrolling anywhere; 44 px+ tap targets (small dots have an enlarged hidden hit area); 16 px form fields (no iPhone zoom); header slimmed on phones; hero uses the visible screen height; hero/banner images are served in a smaller size on phones; notch-safe spacing; menu locks page scroll, hides from keyboards when closed and closes with Esc; lightbox supports swipe; filter chips swipe sideways inside their own row; floating enquiry button moved off the content. Tablet widths and real iPhone Safari / Android Chrome **were not tested on physical devices** 🟡 — see the launch checklist. The admin panel is Filament's own responsive layout plus a few phone tweaks; it was not audited on a phone.

## 12. SEO & performance
Dynamic titles, meta descriptions, canonical, Open Graph / Twitter, alt text, sitemap.xml, robots.txt, clean URLs, lazy loading, WebP, resized images, cached API/pages, security headers ✅. Not measured: Lighthouse / PageSpeed scores (no live server here) 🟡. No strict Content-Security-Policy yet.

## 13. Testing coverage
✅ tested automatically: admin login/logout/permissions, multi-image and video upload, YouTube/Vimeo links, gallery CRUD + filtering, image/file deletion, page/header/service/testimonial/blog management, contact form + validation + spam protection + rate limit, enquiry management, API responses and caching, all internal links, backup/restore, production boot.
❌ not tested: real SMTP delivery, payment/shop (none), load/stress testing, physical mobile devices, browsers other than Chromium, screen-reader audit.

## 14. Content rules
No invented reviews, addresses, phone numbers, awards or statistics. Everything from the template (Florence address, fake team, fake testimonials, fake counters, fake countdown) was removed. 📝 The studio must enter its real information.

## What the studio still has to provide
Real phone / e-mail / address / hours / social links · hero and gallery photos · videos · services text · genuine testimonials · real statistics (or leave hidden) · Privacy Policy and Terms text · SMTP details · domain/hosting access for go-live · a decision on the Shop page.
