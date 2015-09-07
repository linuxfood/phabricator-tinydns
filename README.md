= phabricator-tinydns =

This is a phabricator application that enables storing DNS records in
phabricator and rendering them to tinydns format. It tries to be simple to use.

The UI supports managing many of the tinydns record types, including both text
and raw records for any esoteric needs.

Features:
* UI design sense from the enlightened year of 1998.
* Per-domain view/edit policies: Delegate some responsibility.
* Sophisticated search: allows you to search literally some of the details.
* Read only conduit API.

This is still alpha quality software, but, I'm currently using it in production, so,
at least you know if something goes horribly wrong, it'll get attention.

== Installation ==

1. Get a tgz of the source, or clone it.
2. Place the unpacked source into a directory in your phabricator applications directory. E.g. `$PHABRICATOR_ROOT/src/applications/tinydns`
3. Create the schema: `$PHABRICATOR_ROOT/bin/storage upgrade`
4. Set permissions on the application at `https://phabricator.example.com/applications/view/TinydnsApplication/`
5. Create your first domain `https://phabricator.example.com/tinydns/` and click "Create Domain".
6. Add your records.
7. Use `scripts/phabricator_tinydns.php` to generate your tinydns cdb. Put it in a cron and profit!

== License ==

Apache 2.0, except as otherwise noted.
The code is copyright Brian Smith <brian@linuxfood.net>.
