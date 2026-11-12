# Accessibility check

What was checked to make the site usable by people with disabilities, what was found
and fixed, and what still needs a person to test. The target is WCAG 2.2 level AA,
the usual standard for government websites. Checked on 2026-11-12.

## Pages checked

| Page | Address |
|---|---|
| Home | `/` |
| A guide | `/guides/register-under-business-incentive-policy` |
| A journey (list of guides for one goal) | `/journeys/become-bidder` |
| Log in | `/user/login` |

Each page was checked on the live site first (<https://nsn.somsinha.net>), then on the
local copy after the fixes (<https://gnwt-navigator.ddev.site>). The fixes went
live with release 1.3.0 on 2026-11-12.

## How it was checked

| Check | Tool or method |
|---|---|
| Automatic score | Lighthouse 12.8.2, accessibility section only, in Microsoft Edge 157 without a window ("headless") |
| Automatic rules | axe-core 4.14.0 (the rule set inside Lighthouse) run directly, with the WCAG 2.0 to 2.2 A and AA rules and best-practice rules, at a wide screen (1280 pixels) and a phone width (375 pixels) |
| Colour contrast | Text and background colours read from the browser and the contrast worked out with the WCAG formula. axe cannot measure text on a colour gradient, so this was done by hand for the blue header |
| Skip link, landmarks, headings, link text | A script listed each page's landmarks (header, navigation, main, footer), headings in order, and any link with no clear text |
| Visible focus | A script pressed Tab through each page and recorded how each focused item is marked, with screenshots of the header |
| Without JavaScript | The same pages loaded with JavaScript turned off, at both widths |
| Code | The theme's stylesheet and page template, and the Olivero styles they build on, were read |

The Tab-key check was done by a script in a browser, not by a person.

## Results before the fixes (live site)

| Page | Lighthouse score | axe problems | axe "needs review" |
|---|---|---|---|
| Home | 100 | none | 7 wide, 5 phone (all colour contrast) |
| Guide | 100 | none | 7 wide, 6 phone |
| Journey | 100 | none | 10 wide, 9 phone |
| Log in | 100 | 1: "Primary tabs" heading outside every landmark | 7 wide, 6 phone |

A score of 100 did not mean there were no problems. The tools could not judge the
blue header, and they do not check how focus is shown. The checks by hand found:

| Problem | Where | Measured | Needed |
|---|---|---|---|
| White text on the light end of the header's blue | Slogan (13 pixels), menu links, "Menu" button: every page | 3.1:1 to 3.5:1 | 4.5:1 for normal-size text |
| Focused menu link marked with a light blue box on the blue header | "Home" and "Log in" in the header, wide screens | 1.6:1 | 3:1 |
| Focus ring on buttons in a light blue | "Go to the official page", "Log in" button | 2.3:1 | 3:1 |
| Focus ring on tabs in a light blue | "Log in" and "Reset your password" tabs | 2.9:1 | 3:1 |
| Tabs and messages outside the main content | Log in page (and any page with messages) | | Inside a landmark, so screen reader users can find them |

These passed:

| Check | Result |
|---|---|
| Skip link | "Skip to main content" is the first item on every page, shows when focused, and its target exists |
| Landmarks | Every page has a header, navigation (main and account menus, breadcrumb), one main area and a footer |
| Headings | One main heading (h1) per page, and no levels skipped |
| Link text | Every link says where it goes. No "click here" or empty links |
| Page language and titles | `lang="en"`, and each page has its own title |
| Grey note text (`#5f7581` on white) | 4.8:1, passes |
| Footer text | 13:1, passes |
| Links in the content (`#1475ad` on white) | 5.0:1, passes |
| Focus on links in the content and footer | A 2-pixel outline, 5:1 on white and white on the dark footer |
| Without JavaScript | All four pages show their full content and both menu links, at both widths. On a phone the menu shows as a white box under the site name instead of behind a "Menu" button: plain, but it works |

## What was fixed

All in the theme, `web/themes/custom/nsn_theme`:

| Fix | File |
|---|---|
| The header's blue now runs from Olivero's `primary-40` to the darker `primary-30`, instead of from the lighter `primary-50`. White text on it is now 5.0:1 to 6.5:1 | `css/navigator.css` |
| A focused menu link in the header is marked with a white box (5.0:1 or more) | `css/navigator.css` |
| Focus rings on buttons and tabs use `primary-40` (5.0:1 on white, 4.7:1 on the light grey tabs). The open tab's marker uses the same colour | `css/navigator.css` |
| Messages and tabs moved inside `<main>`. On the Log in page the tabs now sit just below the breadcrumb instead of above it | `templates/page.html.twig` |

## Results after the fixes (local site)

| Page | Lighthouse score | axe problems | axe "needs review" |
|---|---|---|---|
| Home | 100 | none | 7 wide, 5 phone |
| Guide | 100 | none | 7 wide, 6 phone |
| Journey | 100 | none | 10 wide, 9 phone |
| Log in | 100 | none | 7 wide, 6 phone |

The "needs review" items are all colour contrast that axe could not measure: text on
the blue header and the dark footer (both gradients), and links with Olivero's
underline effect, which is drawn as a background. Each was checked by hand: the
lowest contrast is now 5.0:1, on the header.

## Not checked

- **A person using a screen reader** (such as VoiceOver, NVDA or JAWS). The landmarks
  and headings were checked in the code, but nobody has listened to the pages.
- **A person moving through the pages with a keyboard.** A script pressed Tab and
  recorded the focus styles. A person may still find an order or a focus mark that is
  hard to follow.
- **Real phones and tablets.** Phone width was checked in a desktop browser only.
- **Zoom to 200 percent and larger text settings**, and Windows high contrast mode.
- **Pages other than these four**, including the editor screens (which use the Gin
  admin theme, not this one) and error pages.
- **A full re-scan of the live site after the fixes.** The fixes are live (the darker
  header and the Log in page's tabs were confirmed there), but the scans themselves
  were only run on the local copy.
- **Plain language and reading level** of the guides themselves.
