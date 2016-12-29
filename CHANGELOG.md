**1.0.2**
- max_delay is now configurable.
- possibility to not use the cron system from nextcloud.
- new command nextant:background to be run in cron.
- adding is: function during search (is:deleted)
- bugfixes:
  * pdf from outside the current folder were not opened
  * commit were not triggered in some situation
  * external files were not indexed in some situation
  * missing entries from Live if sql is gone
  
**1.0.1**
- new tool: nextant:pick
- new compat with Bookmarks 0.9.1
- bugfixes:
 * remove files_external in admin if module is not enabled.
 * few glitch in command line tools.
 * cleaner exits on empty live queue.
 * error while indexing on encrypted external storage

**1.0.0**
First release.

