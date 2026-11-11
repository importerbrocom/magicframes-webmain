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

- **Hero slides can play a video.** In Admin > Homepage > Hero slides, open a slide and add a *Background video* (MP4 with H.264 video + AAC audio, or WebM, max 50 MB; H.265/HEVC phone videos will not play in most browsers). It starts by itself, muted and looping, behind the headline. Keep it short (10-30 seconds) and compressed. Keep a Hero image on the slide too (it is used if the video cannot play). Only the slide that is showing plays its video.
- **Every upload box shows its maximum file size** (top right of the box): hero image 15 MB, hero video 50 MB, gallery photos 20 MB, gallery category cover 10 MB, services 10 MB, home sections / page banners / blog covers 15 MB, social sharing images 5 MB, testimonial photos 5 MB, logo 2 MB, videos 500 MB (thumbnails 20 MB).
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

A round green WhatsApp button floats at the bottom-right of every page, just above the "back to top" arrow. It opens a WhatsApp chat with the number set in **Website > Site settings > Contact details > WhatsApp number** (with a ready "Hello… I would like to know more about your photography services" message). If that field is empty the button is not shown. Enter the number with the country code, e.g. +91 98765 43210 (a plain 10-digit Indian number gets +91 added automatically).
