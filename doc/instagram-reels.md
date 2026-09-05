# Instagram reels in the About Us strip

The "Crafted to Last" strip on the home page can pull the store's latest reels
straight from Instagram. This is the setup for that, start to finish.

## Why a token is needed at all

Instagram no longer lets anyone read an account's posts without permission.
Fetching `instagram.com/<handle>` returns a page with no media in it — the posts
are drawn by script after load — and the old `?__a=1` JSON endpoint is closed.
The Basic Display API that used to cover this was shut down in December 2024.

So there is no way to type in a handle and get its reels. The account has to
grant access, and that grant is an **access token**. Getting one takes about ten
minutes and is a one-off.

## What you need first

1. **A Professional Instagram account.** In the Instagram app:
   *Settings → Account type and tools → Switch to professional account*. Either
   **Creator** or **Business** works. It is free and reversible, and it does not
   change how the account looks to followers.
2. **A Facebook account** to sign in to the Meta developer site. No Facebook
   *Page* is required — this uses "Instagram API with Instagram Login", which is
   the simpler of Meta's two Instagram APIs precisely because it skips that.

## Getting the token

1. Go to <https://developers.facebook.com/apps/> and select **Create app**.
2. Use case: **Other** → app type **Business** → give it any name
   (e.g. "Karmaa Kulture site") → create it.
3. In the app dashboard, find **Instagram** in the product list and press
   **Set up**.
4. Open **API setup with Instagram login**.
5. Under **1. Generate access tokens**, press **Add account** and sign in with
   the Instagram account whose reels you want. Approve the permissions it asks
   for — `instagram_business_basic` is the one that matters.
6. The account now appears in the list with a **Generate token** button. Press
   it and copy the token. It is a long string starting `IG...`.

That token is already long-lived: it lasts **60 days**.

## Putting it into the site

1. Admin → **Homepage → About Reels**.
2. Paste the token into **Access token**.
3. Set **How many reels to show** (6 is a good default for the strip).
4. **Save & connect.** The screen confirms the handle it connected to, or tells
   you what Instagram objected to.
5. Press **Sync reels now**.

The clips are downloaded to this server and served from here. That is
deliberate: Instagram's own media URLs are signed and expire within days, so a
strip that linked to them would work for a week and then quietly go blank.

## Keeping it running

- **Syncing is manual.** This server has no cron and no queue worker, so nothing
  runs on a timer. New reels appear when somebody presses **Sync reels now**.
  The `instagram:sync-reels` command and its daily schedule entry exist for a
  host that does run a scheduler; here they never fire on their own.
- **The token expires after 60 days.** Press **Refresh token** on the same
  screen before then and it is extended by another 60, indefinitely. Instagram
  will not refresh a token that has already expired, or one less than 24 hours
  old — if it has lapsed, generate a new one with the steps above.
- The screen shows the expiry date and how long is left, so this is visible
  rather than something to remember.

## What the sync does and does not touch

- Only **reels**. Photos, carousels and stories in the same feed are skipped.
- It owns only the rows it created. A clip you uploaded by hand keeps its place
  and is never removed, even when it is the only thing left in the strip.
- A reel deleted on Instagram is removed from the strip on the next sync, and
  its files are deleted from this server.
- Hiding a reel here survives a sync — it will not reappear on the home page.
- **Disconnect** removes the token and every synced reel. Uploaded clips stay.

## When something goes wrong

| What the screen says | What it means |
|---|---|
| "Instagram rejected the access token… it may be a Facebook-Login token" | The token came from the Graph API / Facebook Login flow. Use **API setup with Instagram login** as above. |
| "Instagram returned no reels for this account" | The account has posts but no reels, or they are all photos. Only reels are used. |
| "…skipped" in the sync result | One or more clips could not be downloaded — usually over the 64MB ceiling, the same limit the manual upload enforces. The rest were imported. |
| The strip is empty on the home page | Check the reels are marked "On the home page" and not hidden. |
