# Admin guide — for the studio team

Sign in at **`/admin`**. Everything visitors see is controlled from here; changes appear on the website within seconds.

## First 30 minutes
1. **Change your password**: click your name (top right) → *Profile*.
2. **Site settings** (Website → Site settings): studio name, footer text, phone, WhatsApp, e-mail (enquiry alerts go here), address, opening hours, Google Maps link, Instagram / Facebook / YouTube / Pinterest links, default SEO description.
3. **Header settings** (Header & Menus): logo, sticky on/off, background (white / cream / transparent over banner), call-to-action button text and link, floating enquiry button.
4. **Homepage → Hero slides**: upload large landscape photos (1900 px wide or more) with a headline.
5. **Homepage → Home sections**: edit each block (About, Quote, Portfolio…), turn blocks on/off, drag to reorder. Empty blocks never show.
6. **Gallery → Categories**, then **Gallery → Images** (select many photos at once). Mark favourites as *Featured* to show them on the home page.
7. **Videos**: paste a YouTube/Vimeo link or upload a file (add a thumbnail for uploads). Mark *Featured* for the home page.
8. **Website → Pages**: write the *Privacy Policy* and *Terms and Conditions*, then switch *Published* on.
9. **Services**, **Testimonials**, **Statistics**: add only real, accurate information.
10. Send a test enquiry from the Contact page and check **Enquiries**.

## Everyday tasks
| I want to… | Go to |
|---|---|
| Add photos | Gallery → Images → *New* → choose category → select files → *Create* |
| Reorder photos / categories / services | open the list and drag the rows (⋮⋮ handle) |
| Hide something without deleting | switch **Published** off |
| Replace a photo | open it → remove the file → upload the new one → Save |
| Write a blog post | Website → Blog posts → *New* (use *Publish date* to schedule) |
| Change the menu | Header & Menus → Menu items (left / right / mobile only / footer) |
| Add a new page | Website → Pages → *New* (title, banner, content, optional blocks, SEO tab) |
| Answer an enquiry | Enquiries → open → set status (New → Contacted → In Progress → Completed), add notes; *Export CSV* for a spreadsheet |
| Add a team member | Administration → Users (roles: *super-admin* everything, *editor* all content, *enquiry-handler* enquiries only) |

## Motion effects
The website has gentle motion: golden sparkles on the home banner, photos that reveal as you scroll, viewfinder corners and a "View" cursor on photos, a scrolling band of your gallery names under the banner, cards that tilt under the mouse, and a thin progress line at the top. It is on by default.
* Switch it **Off** under *Website > Site settings > Decorative motion effects* if you want the simplest, fastest pages. The pages then look exactly as before.
* Visitors who set "reduce motion" on their phone or computer never see any of it, and phones get a lighter version automatically.
* The gallery band shows every gallery that has at least one published photo (needs two or more).

## Good practice
* Photos: JPG/PNG/WebP up to 20 MB each — upload the best quality; the site creates small fast versions automatically.
* Always fill **alt text** (describe the photo) — it helps accessibility and Google.
* Page **SEO tab**: a unique title (under 60 characters) and description (120–160 characters) per page.
* Videos: for long films prefer a YouTube/Vimeo link.
* Never publish testimonials, awards or numbers that are not genuine.
* Enquiries contain personal data — delete old ones you no longer need and mention this in your Privacy Policy.

## Hero video and upload size limits

- **Hero slides can play a video.** In Admin > Homepage > Hero slides, open a slide and add a *Background video* (MP4 with H.264 video + AAC audio, or WebM, max 100 MB; H.265/HEVC phone videos will not play in most browsers). It starts by itself, muted and looping, behind the headline. Keep it short (10-30 seconds) and compressed. Keep a Hero image on the slide too (it is used if the video cannot play). Only the slide that is showing plays its video.
- **Every upload box shows its maximum file size** (top right of the box): hero image 15 MB, hero video 100 MB, gallery photos 20 MB, gallery category cover 10 MB, services 10 MB, home sections / page banners / blog covers 15 MB, social sharing images 5 MB, testimonial photos 5 MB, logo 2 MB, videos 500 MB (thumbnails 20 MB).
- Your hosting must allow at least as much: `upload_max_filesize` and `post_max_size` in PHP (see DEPLOYMENT.md). After deploying run `php artisan migrate --force` (adds the hero video column).
- **Video by link.** Instead of uploading, paste a YouTube, Vimeo or Google Drive link in *…or a video link* on the hero slide (an uploaded file wins if both are set). YouTube and Vimeo play automatically, muted and looping. Google Drive: share the file as "Anyone with the link"; Drive shows its own player, so autoplay is not guaranteed there.
- **Video as the first slide, with sound controls.** Put the video slide first (drag to reorder in Hero slides). When a slide has a video, only the video shows (no picture behind it). A round sound button and a volume slider appear at the bottom-left of the home banner. Browsers do not allow a page to start with sound before the visitor interacts, so the video starts playing at once and the sound switches on at the visitor's first click, tap or key press (or immediately where the browser allows it). Visitors can mute, unmute and change the volume at any time. On phones only the round button shows (they use the phone's volume keys). With several slides, a video plays once to its end and then the next slide appears (a YouTube / Vimeo video too; Google Drive videos stay for 30 seconds). Over a video slide the golden sparkles are shown at half strength (fewer, with a lighter tint) so the video stays clear. Google Drive videos use Drive's own player, so the sound controls are not shown for them.


## Instagram feed (latest posts on the home page)

The "On Instagram" block can show your latest posts as a picture grid (each picture opens the post on Instagram) above the Follow button. Instagram only allows this with an access token from **your own** account:

1. The Instagram account must be a **Professional** account (Business or Creator). Switch in the Instagram app: Settings > Account type and tools. It is free.
2. Go to https://developers.facebook.com, log in and create an app (type: Business). Add the product **Instagram** > **API setup with Instagram login**.
3. Under *Generate access tokens* add your Instagram account (you will be asked to log in to it) and click **Generate token**. Copy the long token.
4. In the admin open **Website > Site settings > Instagram feed**, paste the token, choose how many posts to show and click **Save**. You get a message saying whether Instagram accepted it.

How it behaves: new posts appear automatically about every hour (the site keeps its own copy of the pictures, so the page stays fast, and the last posts stay visible if Instagram is down). The token is renewed automatically before it expires. It is stored encrypted and never shown again. To stop the feed, tick *Disconnect Instagram* and save. If the token ever stops working, the Site settings box tells you; generate a new token and paste it. For the hourly refresh without visitors, the server cron job (`schedule:run`) should be set up; without it the feed still refreshes when visitors open the home page.

## WhatsApp button

A round green WhatsApp button floats at the bottom-right of every page. At the top of the page it sits in the corner; once you scroll and the "back to top" arrow appears, it moves up to sit just above the arrow. It opens a WhatsApp chat with the number set in **Website > Site settings > Contact details > WhatsApp number** (with a ready "Hello… I would like to know more about your photography services" message). If that field is empty the button is not shown. Enter the number with the country code, e.g. +91 98765 43210 (a plain 10-digit Indian number gets +91 added automatically).

## Adding gallery images with a title

Website > Gallery > Images > New: after choosing the category and the pictures you can fill in **Title** (caption in the lightbox), **Alt text** (for Google / screen readers; the title is used if left empty) and **Description**. With several pictures at once the same details are applied to all of them; tick *Number the titles* to get "Title 1, Title 2…". You can open any picture later to give it its own title.

## A page for every service

Every published service now has its own page at `/services/<name>` (the service cards on the home page and the Services page link to it). Edit it in **Website > Services > Edit**, tab by tab:

- **Basics**: title, card text, main image (also the banner of the service page), order, published.
- **Details**: the full writing (rich text) and a short tick list "What is included".
- **Photos**: add photos one by one, each with a title (caption) and alt text; drag to reorder. Visitors open them in the photo viewer.
- **Videos**: add YouTube / Vimeo links or upload video files (with an optional thumbnail). Visitors press play to watch.
- **Extra**: questions and answers (FAQ) shown as an open-and-close list (also helps Google).
- **SEO**: the page address (slug) and the Google title / description.

Each page ends with an "Enquire now" button that opens the Contact page with the service already mentioned in the message, and shows the other services. Unpublished services have no page. After deploying run `php artisan migrate --force`.

## HD photos and videos

- **Photos** are shown in HD: gallery photos get web versions up to 2560 px wide at high quality, and the photo viewer shows the 2560 px version (or your full original if it is smaller). Banners and hero slides use 2560 px on computers and 1280 px on phones. Nothing is ever enlarged beyond your original, so upload the original camera/export files (long side 2500 px or more) rather than WhatsApp or screenshot copies.
- **After updating, run once:** `php artisan images:hd` — it rebuilds the HD versions of photos uploaded before. Your originals are never changed.
- **Videos** play exactly as uploaded (the server does not re-compress them), so upload 1080p (Full HD) MP4 files with H.264 video. Hero background videos may now be up to 100 MB. YouTube / Vimeo choose the quality from the viewer's internet speed and the player size; the video player is now larger so they can show 1080p, and YouTube thumbnails use the HD picture when the video has one.

## "Our team" block (About page or any page)

Website > Pages > About > Edit > tab **Content blocks** > **Add content block** > type **Our team**. Fill in the heading (default "Our Team"), an optional small label and intro text, then **Add team member** for each person: name, position and an optional photo (square photos look best; without a photo the initials are shown). Add as many people as you like and drag to change the order. To place the block after "Our Approach", use the ▲ / ▼ arrow buttons at the top right of each block (or drag the ↑↓ handle) until it sits between "Our Approach" and the next block, then click **Save changes**. Blocks open folded so the order is easy to see; click a block's title to open it. Members are shown 4 per row on computers and 2 per row on phones. After deploying run `php artisan migrate --force`.
