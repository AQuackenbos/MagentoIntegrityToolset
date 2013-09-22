<?php 

require_once 'abstract.php';

class Mage_Shell_Integrity extends Mage_Shell_Abstract
{

    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f integrity.php -- [options]

  --compare <dir>	            Compare these files to a separate magento directory 
  help                          This help

USAGE;
    }
	
	
    public function run()
    {
		echo 'Magento Integrity Scanner 0.1'."\n";
		echo '============================='."\n\n";
		echo 'Extensions installed: '."\n";
		echo 'Overwritten classes: '."\n";
		echo 'Local Mage classes: '."\n";
		
		if($this->getArg('compare'))
		{
			echo 'Comparing install to clean copy at "'.$this->getArg('compare')."\"\n";
		}
	}
}

$shell = new Mage_Shell_Integrity();
$shell->run();