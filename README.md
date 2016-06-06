# WordPress Importer Redux
This repository contains the new version of the [WordPress Importer][] currently in development. [Learn more about the rewrite](https://make.wordpress.org/core/?p=15550).

Fast, lightweight, consistent. Pick three. :palm_tree: :sunglasses:

[WordPress Importer]: https://wordpress.org/plugins/wordpress-importer/

## How do I use it?

### Via the Dashboard

1. Install the plugin directly from GitHub. ([Download as a ZIP.](https://github.com/humanmade/WordPress-Importer/archive/master.zip))
2. Activate the plugin (make sure you also deactivate the original Wordpress Importer if you have it installed).
3. Head to Tools &rarr; Import
4. Select "WordPress (v2)"
5. Follow the on-screen instructions.

### Via the CLI

The plugin also includes CLI commands out of the box.

Simply activate the plugin, then run:

```sh
wp wxr-importer import import-file.xml
```

Run `wp help wxr-importer import` to discover what you can do via the CLI.

## Current Status

The current major items are currently missing or suboptimal in the Importer:

* [x] **Web UI** ([#1](https://github.com/humanmade/WordPress-Importer/issues/1)): ~~Right now, there's essentially *no* web interface for the importer. This sucks.~~ Done!

* **Automatic Testing**: There's no unit tests. Boooooo.

## How can I help?

The best way to help with the importer right now is to **try importing and see what breaks**. Compare the old importer to the new one, and find any inconsistent behaviour.

We have a [general feedback thread](https://github.com/humanmade/WordPress-Importer/issues/7) so you can let us know how it goes. If the importer works perfectly, let us know. If something doesn't import the way you think it should, you can file a new issue, or leave a comment to check whether it's intentional first. :)

## License

The WordPress Importer is licensed under the GPLv2 or later.

## Credits

Original plugin created by Ryan Boren, [Jon Cave][duck_] (@joncave), [Andrew Nacin][nacin] (@nacin), and [Peter Westwood][westi] (@westi). Redux project by [Ryan McCue](https://github.com/rmccue) and [contributors](https://github.com/humanmade/WordPress-Importer/graphs/contributors).

[duck_]: https://profiles.wordpress.org/duck_
[nacin]: https://profiles.wordpress.org/nacin
[westi]: https://profiles.wordpress.org/westi
