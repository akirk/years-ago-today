# TODO

The following list comprises ideas, suggestions, and known issues, all of which are in consideration for possible implementation in future releases.

***This is not a roadmap or a task list.*** Just because something is listed does not necessarily mean it will ever actually get implemented. Some might be bad ideas. Some might be impractical. Some might either not benefit enough users to justify the effort or might negatively impact too many existing users. Or I may not have the time to devote to the task.

* Show more info about post in widget, such as author (if on multi-author site). (Maybe hide by default and show on hover/focus.)
* Add capability to control what users can get the daily email?
* Add way to filter by author
* Allow post listing template to be overridden
* Add widget for front-end use (!! MULTIPLE REQUESTS !!)
* In `get_first_published_year()`/`get_posts()`:
  - Don't use hardcoded post statuses of 'publish' and 'private'. Include latter only if user has relevant caps.
  - Make the statuses filterable for custom post status support
* Unit tests: Add tests for `option_save()`
* Widget could allow specifying a specific date to list posts from that given date
* Email previewer:
  - On a day with no past posts, simulate matching posts by either getting a random selection of posts (and noting in the preamble that no posts were posted on this day so random posts were chosen so that the email can be evaluated) or injecting fake data into the email.
  - Add a form above or below the email preview that allows for customization of the preview, rather than relying on the explicit links provided in the profile or URL hacking.

Feel free to make your own suggestions or champion for something already on the list (via the [plugin's support forum on WordPress.org](https://wordpress.org/support/plugin/years-ago-today/) or on [GitHub](https://github.com/coffee2code/years-ago-today/) as an issue or PR).