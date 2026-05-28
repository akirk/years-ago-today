# TODO

The following list comprises ideas, suggestions, and known issues, all of which are in consideration for possible implementation in future releases.

***This is not a roadmap or a task list.*** Just because something is listed does not necessarily mean it will ever actually get implemented. Some might be bad ideas. Some might be impractical. Some might either not benefit enough users to justify the effort or might negatively impact too many existing users. Or I may not have the time to devote to the task.

* Show more info about post in widget, such as author (if on multi-author site). (Maybe hide by default and show on hover/focus.)
* Add capability to control what users can get the daily email?
* Add way to filter by author
* Allow post listing template to be overridden
* In `get_posts()`:
  - Make post statuses filterable for custom post status support; include `private` only if user has relevant caps (when/if private posts get included in listings)
* Unit tests: Add tests for `option_save()`
* Widget could allow specifying a specific date to list posts from that given date
* Consider `get_post_types()` defaulting to all public post types (minus attachments and pages)?
* Add UI for recently added features that have filters (cron email time, supported post types, email from address)
* Add control for email frequency - weekly digest instead of daily.
* Add ability for widget to include a +N day range to either side of today. (See support forum request.)
* Front-end daily archive? - a public (or optionally private) page (/years-ago-today/) that shows the posts each day, respecting post status and user caps.
* Omit author in post info within emails for single-author sites?
* Embolden site name and date in header of HTML emails?
* Email previewer:
  - On a day with no past posts, simulate matching posts by either getting a random selection of posts (and noting in the preamble that no posts were posted on this day so random posts were chosen so that the email can be evaluated) or injecting fake data into the email.
  - Add a form above or below the email preview that allows for customization of the preview, rather than relying on the explicit links provided in the profile or URL hacking.
* Add filter to `get_email_body()`
  - Run filter twice, one per type rather than on the full return value. Example usage: Blank out HTML body to force plaintext emails.
    - Add as FAQ?
  - In `cron_email()`, the plaintext is turned into simple HTML via wpautop(). Remove that fallback?
  - Relatedly: in `cron_email()`, don't call isHTML and set the multi-parts if there is no HTML.
    - What if there is no plaintext? Send without defining it? Strip tags from HTML version?

Feel free to make your own suggestions or champion for something already on the list (via the [plugin's support forum on WordPress.org](https://wordpress.org/support/plugin/years-ago-today/) or on [GitHub](https://github.com/coffee2code/years-ago-today/) as an issue or PR).