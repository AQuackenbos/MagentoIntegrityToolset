Magento Integrity Scanner
====================

Use "php -f integrity.php -- help" to see a full list of options.
  
The Magento Integrity Scanner is a tool to help find overwrites, rewrites, and other
changes to a Magento installation that may cause issues.  It is a tool to help debug
and easily identify problem spots in a given install.

If you're having issues running it, try replacing shell/abstract.php with a fresh copy
from Magento's latest community version.

The current version only scans for rewritten blocks and models, as well as Mage files
being overridden in app/code/local.  Future versions will scan for rewritten controllers
as well as allow you to diff your code against a clean installation.