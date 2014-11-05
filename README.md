Magento Integrity Toolset
====================

Place repository in root directory of any Magento installation, or just
copy integrity.php and consistency.php into the /shell directory.  Use 
"php -f integrity.php -- help" and "php -f consistency.php -- --help" 
to see a full list of options in the shell.
  
  
## integrity.php

The Magento Integrity Scanner is a tool to help find overwrites, rewrites, and other
changes to a Magento installation that may cause issues.  It is a tool to help debug
and easily identify problem spots in a given install.

If you're having issues running it, try replacing shell/abstract.php with a fresh copy
from Magento's latest community version.

The current version only scans for rewritten blocks and models, as well as Mage files
being overridden in app/code/local and a summated list of installed extensions.  Future 
versions will scan for rewritten controllers as well.

## consistency.php

The Core Consistency Check script builds a report of how your install of Magento compares
to a clean install of the same version.  Use --help to see the default parameters of the 
scan.  It is required that you download a clean install of Magento and place it somewhere
for the script to diff against.  A report of all difference counts and files will be
built, as well as a collection of files representing the differences found.