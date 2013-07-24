Austin-Traffic-Scraper
======================

A PHP web-scraper that parses and geocodes traffic events (collisions, mostly) from the Austin-Travis County Traffic Report Page (http://www.ci.austin.tx.us/qact/default.cfm).

# Installation / Usage

- Add your database's information and Google Maps API key in the configuration file, `config.inc.php`.

- There is a PHP install script, `install_sql_table.php`, that will create an SQL table which will be used to hold the geocoded traffic events. If you prefer to DIY, there's also an SQL file, `create_table.sql`.

- Run the scraper, `scrape_traffic.php`. Last time I checked, the Traffic Reports Page updates every five minutes. So I set up a cron job to run that often. Don't go insane checking it every millisecond.

# Notes

- The reports break down traffic events into several categories. While the page does give explanations for some of them, I managed to get descriptions from the APD to fill in the holes. These are included in `crash_codes.txt`.

- The traffic reports page spits out traffic event locations in strange ways. Most often, it outputs intersections, i.e., "Cesar Chavez/IH-35". Look forward to dealing with nonstandard abbreviations, slang, typos, etc. The scraper has a very basic chain of string replacements to make everything more Google Maps-friendly, but it's _far from perfect_. I'd like to improve this in the future.

- I have 2.5 years of Traffic Events logged that I am in the process of cleaning up, re-parsing, etc. It's a total backburner project right now, but if enough people show interest in it, I can put up the raw SQL dump. *Warning: It's ugly as hell!*