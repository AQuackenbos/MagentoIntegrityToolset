Magento Integrity Scanner
====================

Usage:  php -f integrity.php -- [options]

  --compare [dir]  Compare these files to a separate magento directory, dir  
  --local [name]				List local classes for a given namespace, name.  Overrides the local Mage, Enterprise, and Zend checks.  
  --output [file]				Save results of this scan to a file, based in the Magento root directory.  Root must be writeable.  
  silent						Hides the echo of the result for this scan.  
  help                          See this usage information.  
  
  
The Magento Integrity Scanner is a tool to help find overwrites, rewrites, and other
changes to a Magento installation that may cause issues.  It is a tool to help debug
and easily identify problem spots in a given install.

If you're having issues running it, try replacing shell/abstract.php with a fresh copy
from Magento's latest community version.

The current version only scans for rewritten blocks and models, as well as Mage files
being overridden in app/code/local.  Future versions will scan for rewritten controllers
as well as allow you to diff your code against a clean installation.