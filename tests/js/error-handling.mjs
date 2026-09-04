/**
 * The browser half of the error-handling contract.
 *
 * A Laravel request test can see what the server RENDERED; it cannot see whether
 * the browser sent the request, or whether the message the last response left
 * behind is gone by the time the new one appears. That is where the reported bug
 * lived - one empty email box answered with both
 *
 *     Email Address is required.
 *     The provided credentials do not match our records.
 *
 * - so it is checked here, by driving the real built bundle in a real DOM.
 *
 * Run it against the shipped bundle:
 *
 *     npm run build
 *     node tests/js/error-handling.mjs
 *
 * or against a throwaway one while iterating, which is far quicker:
 *
 *     npx esbuild resources/js/app.js --bundle --format=iife --outfile=/tmp/kk.js
 *     KK_BUNDLE=/tmp/kk.js node tests/js/error-handling.mjs
 *
 * Runs every expectation, prints a PASS/FAIL line for each, and exits non-zero if any
 * failed - so it drops straight into CI without a runner.
 */
import { JSDOM, VirtualConsole } from 'jsdom';
import fs from 'node:fs';
import path from 'node:path';

const REPO = process.env.KK_REPO || path.resolve(path.dirname(new URL(import.meta.url).pathname.replace(/^\/([A-Za-z]:)/, '$1')), '../..');
let codePath = process.env.KK_BUNDLE;
if (!codePath) {
  const buildDir = path.join(REPO, 'public/build/assets');
  const bundle = fs.readdirSync(buildDir).find((f) => /^app-.*\.js$/.test(f));
  if (!bundle) throw new Error('no built app-*.js found in ' + buildDir);
  codePath = path.join(buildDir, bundle);
}
console.log('bundle:', codePath);
const code = fs.readFileSync(codePath, 'utf8');

const results = [];
const check = (name, pass, detail = '') => {
  results.push({ name, pass, detail });
  console.log(`${pass ? 'PASS' : 'FAIL'}  ${name}${detail ? '  -- ' + detail : ''}`);
};

// The markup the fixed login page produces after a rejected sign-in: the input,
// and the server's message rendered by <x-field-error>.
const PAGE = `<!doctype html><html><head><meta name="csrf-token" content="t"></head><body>
  <form id="signin" method="POST" action="/login">
    <div>
      <label for="login_email">Email Address</label>
      <div class="relative">
        <input type="email" name="email" id="login_email" required value="shopper@example.com">
      </div>
      <p class="kk-field-error" data-kk-field-error="email" id="kk-srv-err-email" role="alert">The provided credentials do not match our records.</p>
    </div>
    <div>
      <label for="login_password">Password</label>
      <div class="relative"><input type="password" name="password" id="login_password" required></div>
    </div>
    <button type="submit">Sign In</button>
  </form>
</body></html>`;

const virtualConsole = new VirtualConsole();
virtualConsole.on('jsdomError', (e) => {
  if (!/Not implemented/.test(String(e.message))) console.error('[jsdom]', e.message);
});

const dom = new JSDOM(PAGE, {
  runScripts: 'outside-only',
  pretendToBeVisual: true,
  url: 'https://example.test/login',
  virtualConsole,
});
const { window } = dom;

// Browser APIs jsdom does not ship that Alpine's plugins reach for.
window.IntersectionObserver = class {
  constructor() {}
  observe() {}
  unobserve() {}
  disconnect() {}
};
window.matchMedia = window.matchMedia || ((q) => ({
  matches: false, media: q, onchange: null,
  addListener() {}, removeListener() {}, addEventListener() {}, removeEventListener() {}, dispatchEvent() { return false; },
}));
window.scrollTo = () => {};
window.HTMLElement.prototype.scrollIntoView = function () {};
window.fetch = async () => ({ ok: true, status: 200, json: async () => ({}) });

// Forms never actually navigate in jsdom; record the attempt instead so the
// "was the request sent?" question has an answer.
let submissionsSent = 0;
window.HTMLFormElement.prototype.submit = function () { submissionsSent++; };

dom.window.eval(code);

// Alpine.start() and the module's DOMContentLoaded work run on the next ticks.
await new Promise((r) => setTimeout(r, 300));

const doc = window.document;
const form = doc.getElementById('signin');
const email = doc.getElementById('login_email');
const password = doc.getElementById('login_password');
const serverNote = () => doc.querySelector('[data-kk-field-error="email"]');
const clientNotes = () => Array.from(doc.querySelectorAll('.kk-field-error'));

// A real submit attempt: dispatch the event the way a click on the button does,
// including the browser's own constraint pass, which jsdom does not run for us.
function attemptSubmit(theForm) {
  const invalid = Array.from(theForm.elements).filter(
    (el) => el.willValidate && !el.disabled && el.type !== 'hidden' && !el.checkValidity(),
  );

  if (invalid.length && !theForm.hasAttribute('novalidate')) {
    // This is what a browser does: fire `invalid` on each failing control and
    // abandon the submission WITHOUT firing `submit`.
    invalid.forEach((el) => el.dispatchEvent(new window.Event('invalid', { bubbles: false, cancelable: true })));
    return { sent: false };
  }

  const ev = new window.Event('submit', { bubbles: true, cancelable: true });
  theForm.dispatchEvent(ev);
  if (!ev.defaultPrevented) submissionsSent++;
  return { sent: !ev.defaultPrevented };
}

// ---------------------------------------------------------------------------
// 0. The server's message is adopted on load: outlined, and linked for a
//    screen reader.
// ---------------------------------------------------------------------------
check('server message is adopted on load (aria-invalid)', email.getAttribute('aria-invalid') === 'true');
check('server message is linked with aria-describedby',
  email.getAttribute('aria-describedby') === 'kk-srv-err-email',
  email.getAttribute('aria-describedby') || '(none)');
check('field carries the invalid outline', email.classList.contains('kk-input-invalid'));

// ---------------------------------------------------------------------------
// 1. THE REPORTED BUG. Empty the email box and submit again.
//    Expected: "Email Address is required." and NOTHING else.
// ---------------------------------------------------------------------------
email.value = '';
email.dispatchEvent(new window.Event('input', { bubbles: true }));

check('editing the field retires the stale server message', serverNote() === null,
  serverNote() ? serverNote().textContent : '(gone)');

const before = submissionsSent;
const attempt = attemptSubmit(form);

check('the request is NOT sent when a required field is empty',
  attempt.sent === false && submissionsSent === before);

const notes = clientNotes();
const forField = (name) => notes.filter((n) => n.previousElementSibling?.querySelector?.(`[name="${name}"]`)
  || n.parentElement?.querySelector?.(`[name="${name}"]`));
check('the email field carries exactly ONE message', forField('email').length === 1,
  forField('email').map((n) => n.textContent).join(' | ') || '(none)');
check('and it is the field validation message, not the credentials error',
  forField('email')[0]?.textContent === 'Email Address is required.',
  forField('email')[0]?.textContent || '(none)');
check('no field carries two messages',
  new Set(notes.map((n) => n.textContent)).size === notes.length,
  notes.map((n) => n.textContent).join(' | '));
check('the credentials message is gone from the page',
  !doc.body.textContent.includes('do not match our records'));

// ---------------------------------------------------------------------------
// 2. Correcting the field clears its message (brief section 6).
// ---------------------------------------------------------------------------
email.value = 'shopper@example.com';
email.dispatchEvent(new window.Event('input', { bubbles: true }));
check('correcting the field clears ITS message (the password box keeps its own)',
  !clientNotes().some((n) => n.textContent === 'Email Address is required.'),
  clientNotes().map((n) => n.textContent).join(' | ') || '(none)');

// ---------------------------------------------------------------------------
// 3. A DIFFERENT field failing shows one message, for that field only.
// ---------------------------------------------------------------------------
attemptSubmit(form);
const pwNotes = clientNotes();
check('a valid email + empty password reports the password only',
  pwNotes.length === 1 && pwNotes[0].textContent === 'Password is required.',
  pwNotes.map((n) => n.textContent).join(' | ') || '(none)');

// ---------------------------------------------------------------------------
// 4. A stale FORM-LEVEL banner is retired by a new submission too.
// ---------------------------------------------------------------------------
const banner = doc.createElement('div');
banner.setAttribute('data-kk-form-error', 'default');
banner.className = 'kk-form-error';
banner.textContent = 'Your changes were not saved.';
form.parentNode.insertBefore(banner, form);

password.value = '';
attemptSubmit(form);
check('a stale form banner is retired when the next submission starts',
  doc.querySelector('[data-kk-form-error]') === null);

// ---------------------------------------------------------------------------
// 5. Double submit: the second one is refused (brief section 16).
// ---------------------------------------------------------------------------
email.value = 'shopper@example.com';
password.value = 'CorrectHorse!9xy';
const firstGo = attemptSubmit(form);
const secondGo = attemptSubmit(form);
check('a valid form submits once', firstGo.sent === true);
check('a second submit while one is in flight is refused', secondGo.sent === false);

await new Promise((r) => setTimeout(r, 20));
check('the submit button is disabled during the request',
  form.querySelector('button[type="submit"]').disabled === true);

// ---------------------------------------------------------------------------
// 6. The centralised API error mapper (brief sections 12, 15, 22).
// ---------------------------------------------------------------------------
const api = window.kkApiError;
check('kkApiError is exposed', typeof api === 'function');

if (typeof api === 'function') {
  const cases = [
    ['no response at all is a network error', api(new TypeError('Failed to fetch')).message.includes('internet connection')],
    ['401 maps to a sign-in prompt', api({ status: 401 }).message === 'Please sign in to continue.'],
    ['403 maps to permission', api({ status: 403 }).message.includes('permission')],
    ['404 maps to not found', api({ status: 404 }).message.includes('could not find')],
    ['409 maps to conflict', api({ status: 409 }).message.includes('already been done')],
    ['429 maps to too many attempts', api({ status: 429 }).message.includes('too many attempts')],
    ['422 field errors are unpacked, first message only',
      api({ status: 422 }, { errors: { email: ['Email is already registered', 'second'] } }).fields.email === 'Email is already registered'],
    ['a 4xx server message is preferred over the canned one',
      api({ status: 409 }, { message: 'That coupon has expired.' }).message === 'That coupon has expired.'],
    ['a 500 NEVER shows the server text',
      api({ status: 500 }, { message: 'SQLSTATE[42S02]: Base table not found: users_old' }).message
        === 'Something went wrong at our end. Please try again in a moment.'],
    ['a 503 is reported as briefly unavailable', api({ status: 503 }).message.includes('briefly unavailable')],

    // An AxiosError, which is the shape MOST of this app's callers produce.
    //
    // Axios copies the status onto the error itself, so an AxiosError satisfies
    // a "does it look like a fetch Response" test just as well as a Response
    // does. When that branch was tried first, the body was never read: every
    // axios failure lost its 422 field map and its 4xx sentence and came back as
    // the generic line. These four hold the branch order in place.
    ['an axios 422 yields its field messages',
      api({ status: 422, response: { status: 422, data: { message: 'The given data was invalid.', errors: { quantity: ['Only 2 item(s) available in stock.'] } } } })
        .fields.quantity === 'Only 2 item(s) available in stock.'],
    ['an axios 4xx yields the servers own sentence',
      api({ status: 422, response: { status: 422, data: { message: 'Only 2 item(s) available in stock.' } } })
        .message === 'Only 2 item(s) available in stock.'],
    ['an axios 419 is reported as an expired page',
      api({ status: 419, response: { status: 419, data: { message: 'CSRF token mismatch.' } } })
        .message.includes('session expired')],
    ['an axios 500 still never shows the server text',
      api({ status: 500, response: { status: 500, data: { message: 'SQLSTATE[42S02]: Base table not found' } } })
        .message === 'Something went wrong at our end. Please try again in a moment.'],
    ['an axios network failure (no response) is a network error',
      api({ message: 'Network Error', request: {} }).message.includes('internet connection')],
  ];
  cases.forEach(([name, pass]) => check(name, pass));
}

// ---------------------------------------------------------------------------
// 7. A form that validates ITSELF must hear nothing from this module.
//
//    novalidate used to be honoured by the submit handler alone; the blur,
//    character-filter, password and invalid handlers all tested only
//    data-no-validate. So the Create Account panel, the header sign-in modal and
//    both popups - every form with a validator of its own - got a second,
//    differently worded paragraph injected under the box the component was
//    already writing to.
// ---------------------------------------------------------------------------
const own = doc.createElement('div');
own.innerHTML = "\n"
  + '<form id="selfvalidating" novalidate>'
  + '<label for="own_name">Full Name</label>'
  + '<input type="text" name="full_name" id="own_name" required minlength="2" autocomplete="name">'
  + '<p class="kk-field-error component-owned">Please enter your full name.</p>'
  + '<input type="tel" name="own_phone" id="own_phone" data-kk-chars="phone">'
  + '<button type="submit">Create Account</button>'
  + '</form>';
doc.body.appendChild(own);

const ownField = doc.getElementById('own_name');
const ownForm = doc.getElementById('selfvalidating');
const injected = () => Array.from(ownForm.querySelectorAll('.kk-field-error:not(.component-owned)'));

ownField.value = 'x';
ownField.dispatchEvent(new window.Event('input', { bubbles: true }));
ownField.value = '';
ownField.dispatchEvent(new window.Event('input', { bubbles: true }));
ownField.dispatchEvent(new window.Event('blur', { bubbles: false }));
check('blur on a novalidate form injects nothing', injected().length === 0,
  injected().map((n) => n.textContent).join(' | ') || '(none)');

attemptSubmit(ownForm);
check('submit on a novalidate form injects nothing', injected().length === 0,
  injected().map((n) => n.textContent).join(' | ') || '(none)');

check("the component's own message is left alone",
  ownForm.querySelector('.component-owned') !== null
  && ownForm.querySelector('.component-owned').textContent === 'Please enter your full name.');

// The typing-level help survives the opt-out: the letter is still refused, it
// just does not get a paragraph of its own.
const ownPhone = doc.getElementById('own_phone');
ownPhone.value = '98a76';
ownPhone.dispatchEvent(new window.Event('input', { bubbles: true }));
check('the character filter still strips on a novalidate form', ownPhone.value === '9876', ownPhone.value);
check('but prints no note of its own', injected().length === 0,
  injected().map((n) => n.textContent).join(' | ') || '(none)');

const failed = results.filter((r) => !r.pass);
console.log(`\n${results.length - failed.length}/${results.length} checks passed`);
process.exit(failed.length ? 1 : 0);
