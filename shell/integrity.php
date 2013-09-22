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

  --compare <dir>	            Compare these files to a separate magento directory, dir 
  --local <name>				List local classes for a given namespace, name.  Overrides the local Mage, Enterprise, and Zend checks.
  --output <file>				Save results of this scan to a file, based in the Magento root directory.  Root must be writeable.
  silent						Hides the echo of the result for this scan.
  help                          This help

USAGE;
    }
	
	
    public function run()
    {
		$customDirectory = $this->getArg('local');
		$output = '';
		
		$output .= 'Magento Integrity Scanner 0.1'."\n";
		$output .= '============================='."\n\n";
		
		//List installed extensions and flag disabled ones
		$output .= 'Extensions installed: '."\n";
		$output .= "\t".'(Coming Soon)'."\n";
		
		//List rewritten classes
		$output .= 'Rewritten classes: '."\n";
		
		
		//Models
		$models = Mage::getConfig()->getNode('global/models');
		$output .= "\t".'Models:'."\n";
		$output .= $this->_showRewrites($models);
		
		//Blocks
		$blocks = Mage::getConfig()->getNode('global/blocks');
		$output .= "\t".'Blocks:'."\n";
		$output .= $this->_showRewrites($blocks);
		
		
		//Controllers
		$output .= "\t".'Controllers:'."\n";
		$output .= "\t\t".'(Coming Soon)'."\n";
		
		//List local Enterprise and Mage files
		$output .= 'Local classes: '."\n";
		$output .= $this->_readLocalFiles($customDirectory);
		
		if($this->getArg('compare'))
		{
			$output .= 'Comparing install to clean copy at "'.$this->getArg('compare')."\"\n";
			$output .= "\t".'(Coming Soon)'."\n";
		}
		
		if($this->getArg('output'))
		{
			file_put_contents($this->_getRootPath().$this->getArg('output'),$output);
			$output .= 'Saved results to "'.$this->_getRootPath().$this->getArg('output')."\"\n";
		}
		
		if(!$this->getArg('silent'))
		{
			echo $output;
		}
		
	}
	
	protected function _readLocalFiles($dirs = null)
	{
		$result = '';
		$localPath = $this->_getRootPath() . 'app' . DIRECTORY_SEPARATOR . 'code' . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR;
		
		if($dirs == null)
		{
			$dirs = array(
				'Mage',
				'Enterprise',
				'Zend'
			);
		}
		else
		{
			$dirs = array($dirs);
		}
		
		foreach($dirs as $_directory)
		{	
			$result .= implode("\n",$this->_processDirectory($localPath.$_directory))."\n";
		}
		
		if(!$result)
		{
			$result .= "\t".'(None)'."\n";
		}
		
		return $result;
	}
	
	protected function _processDirectory($dir) {
		$localPath = $this->_getRootPath() . 'app' . DIRECTORY_SEPARATOR . 'code' . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR;
		
		if (is_dir($dir)) {
			for ($list = array(),$handle = opendir($dir); (FALSE !== ($file = readdir($handle)));) {
				if (($file != '.' && $file != '..') && (file_exists($path = $dir.'/'.$file))) {
					if (is_dir($path)) {
						$list = array_merge($list, $this->_processDirectory($path, TRUE));
					} else {
						do if (!is_dir($path)) {
							//File
							$entry = "\t";
							$fixedPath = str_replace(array($localPath,'.php'),'',$path);
							$fixedPath = str_replace(array('/','\\'),'_',$fixedPath);
							$entry .= $fixedPath;

							break;
						} else {
							//Directory
							break;
						} while (FALSE);
						$list[] = $entry;
					}
				}
			}
			closedir($handle);
			return $list;
		} else return FALSE;
	}
	
	protected function _showRewrites($nodeHead)
	{
		$rewrites = '';
	
		foreach($nodeHead as $wrapper => $moduledata)
		{
			foreach($moduledata as $_shortname => $module)
			{
				foreach($module as $key => $data)
				{
					if($key != 'rewrite')
					{
						continue;
					}
					
					foreach($data as $_accessor => $class)
					{
						$rewrites .= "\t\t";
					
						$rewrites .= $_shortname.'/'.$_accessor.' => '.$class;
					
						$rewrites .= "\n";
					}
				}
			}
		}
	
		if(!$rewrites)
		{
			$rewrites .= "\t\t(None)\n";
		}
	
		return $rewrites;
	}
}

$shell = new Mage_Shell_Integrity();
$shell->run();