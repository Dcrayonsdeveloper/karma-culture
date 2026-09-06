# Instagram reels in the About Us strip

The "Crafted to Last" strip on the home page shows the store's own Instagram
reels — a random handful, redrawn on every visit. This is the setup for that,
start to finish.

## How the strip works

Once an account is connected, the strip is **live**. Nobody uploads a clip and
nobody presses a button:

- The account's recent reels are fetched from Instagram and held for **20
  minutes**, so Instagram is asked three times an hour however busy the site is.
- Every page load draws its own random handful out of that list, so a visitor
  rarely sees the same strip twice, and a reel posted this morning is on the
  home page within twenty minutes.
- The clips are played **straight from Instagram's CDN**. That is safe here
  precisely because nothing is stored: their URLs are signed and expire in a day
  or two, and this list is re-signed every time it is refetched. What breaks is
  *saving* one of those URLs, not using a fresh one.

The clips listed under **Homepage → About Reels** are the **fallback**. They are
what the strip shows before an account is connected, and what it drops back to
while Instagram is unreachable, so the section never empties out because a third
party is having a bad morning. **Sync reels now** copies the account's reels onto
this server to stock that fallback — a separate job from the live strip, and the
one place a clip *is* downloaded rather than linked.

A store that would rather curate the strip by hand still can: disconnect
Instagram, and the uploaded clips are all that is left to show.

## Why a token is needed at all

Instagram no longer lets anyone read an account's posts without permission.
Fetching `instagram.com/<handle>` returns a page with no media in it — the posts
are drawn by script after load — and the old `?__a=1` JSON endpoint is closed.
The Basic Display API that used to cover this was shut down in December 2024.

So there is no way to type in a handle and get its reels. The account has to
grant access, and that grant is an **access token**.

## Two kinds of token, and both work

Meta has two Instagram APIs, and a token works against exactly one of them. The
site tells them apart by their prefix, which is reliable, and talks to whichever
one issued the token.

| | Instagram Login | Facebook Login |
|---|---|---|
| Token starts with | `IGAA…` | `EAA…` |
| Where it comes from | The app's "API setup with Instagram login" panel | Meta Business / Graph API Explorer / a system user |
| Needs a Facebook Page | No | **Yes** — the Instagram account must be linked to one |
| Endpoint used | `graph.instagram.com/me/media` | `graph.facebook.com/…/{ig-user-id}/media` |
| Renewed by | **Refresh token** on the admin screen | Meta Business settings — *not* from this site |

If you have neither yet, the Instagram Login route below is the shorter one.

### Getting an Instagram-Login token

1. Switch the account to Professional in the Instagram app:
   *Settings → Account type and tools → Switch to professional account*. Either
   **Creator** or **Business** works. Free, reversible, invisible to followers.
2. Go to <https://developers.facebook.com/apps/> and select **Create app**.
3. Use case **Other** → app type **Business** → any name → create.
4. In the app dashboard, find **Instagram** in the product list → **Set up**.
5. Open **API setup with Instagram login**.
6. Under **1. Generate access tokens**, press **Add account**, sign in with the
   Instagram account, and approve the permissions — `instagram_business_basic`
   is the one that matters.
7. Press **Generate token** and copy it. It is long-lived already: **60 days**.

### Using a Facebook-Login or system-user token

If the business already has a Meta app with a system user, its token works as it
stands. It needs the `instagram_basic` and `pages_show_list` permissions, and the
Instagram account has to be linked to a Facebook Page the token can reach
(*Meta Business settings → Accounts → Instagram accounts*).

Nothing else is required — paste it in the same box. The site finds the account
by listing the Pages the token can see and asking each Page which Instagram
account is linked to it.

> One trap worth recording, because it looks exactly like a missing link:
> asking `/me/accounts` to expand `instagram_business_account` inline returns the
> Pages **without that field**, silently and with no error, for a system-user
> token. The Page node has to be fetched on its own. `discoverLinkedAccount()`
> does the second call for this reason.

## Putting it into the site

1. Admin → **Homepage → About Reels**.
2. Paste the token into **Access token**.
3. Set **How many reels to show** — how many tiles the strip holds, picked at
   random from the account's recent reels. 6 is a good default.
4. **Save & connect.** The screen confirms the handle it connected to, or tells
   you what Instagram objected to.
5. That is it — the strip is live. Optionally press **Sync reels now** to stock
   the offline fallback as well.

`INSTAGRAM_ACCESS_TOKEN` in `.env` does the same job for a deploy that should
arrive already connected. The admin screen wins where both are set, and a token
cleared with **Disconnect** stays cleared — an environment default does not
quietly switch the strip back on.

## Keeping it running

- **The live strip needs no scheduler.** It refreshes itself every 20 minutes as
  visitors arrive. (This server has no cron and no queue worker, so anything
  that *did* need a timer would never fire. Nothing here does.)
- **The fallback copies are manual.** `instagram:sync-reels` and its schedule
  entry exist for a host that runs a scheduler; here, **Sync reels now** is what
  fires them. Pressing it also refreshes the live strip immediately, which is
  the quick way to see a brand-new reel without waiting out the 20 minutes.
- **An Instagram-Login token expires after 60 days.** Press **Refresh token**
  before then and it is extended by another 60, indefinitely. Instagram will not
  refresh a token that has already expired, or one less than 24 hours old.
- **A Facebook-Login token is reissued in Meta's own settings**, not from here.
  The screen says so rather than letting the refresh fail confusingly.
- The screen shows the expiry date and how long is left.

## What the sync does and does not touch

- Only **reels**. Photos, carousels and stories in the same feed are skipped —
  by the live strip as well as by the sync.
- It owns only the rows it created. A clip you uploaded by hand keeps its place
  and is never removed, even when it is the only thing left in the strip.
- A reel deleted on Instagram is removed on the next sync, and its files are
  deleted from this server.
- Hiding a reel here survives a sync — it will not come back into the fallback.
- **Disconnect** removes the token and every synced reel. The strip stops showing
  live reels and falls back to the uploaded clips, which stay.

## When something goes wrong

| What the screen says | What it means |
|---|---|
| "Instagram rejected the access token…" | Expired, revoked, or missing a permission. For an `IGAA…` token, regenerate it as above; for an `EAA…` one, check `instagram_basic` and `pages_show_list` are granted. |
| "No Instagram account is linked to any Page this token can reach" | The token is fine, but the Instagram account is not connected to a Page it can see. Link it in Meta Business settings. |
| "This Facebook token cannot see any Pages" | The token is missing `pages_show_list`. |
| "This is a Facebook-Login token, which is not refreshed from here" | Correct and expected — reissue it in Meta Business settings. |
| "Instagram returned no reels for this account" | The account has posts but no reels, or they are all photos. Only reels are used. |
| "…skipped" in the sync result | One or more clips could not be downloaded — usually over the 64MB ceiling, the same limit the manual upload enforces. The rest were imported. |
| The strip shows uploaded clips, not Instagram | The live fetch failed and it fell back. `storage/logs` will carry "Instagram reels unavailable for the About Us strip" with the reason. The failure is remembered for 5 minutes, so give it that long after fixing the cause. |
| The strip is empty on the home page | No account connected *and* no uploaded clip is marked "On the home page". |

## A note for whoever enables the CSP

`app/Http/Middleware/ContentSecurityPolicy.php` is written but deliberately not
registered. Its `media-src` allows `*.fbcdn.net` and `*.cdninstagram.com`,
because the strip's `<video>` elements point there. Without that the strip is
blank with nothing but a console entry to explain it — the hosts are per-POP
names chosen at request time, and the video URL redirects to a second host
again, so no fixed list of origins would hold.
